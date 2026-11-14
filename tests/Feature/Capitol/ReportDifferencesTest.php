<?php

use App\Enums\UserRole;
use App\Models\FinanceDocument;
use App\Models\FinanceFigure;
use App\Models\FinanceLine;
use App\Models\Municipality;
use App\Models\User;
use App\Services\FinanceReportIssues;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->evidenceRoot = storage_path('framework/testing/report-differences-'.Str::uuid());
    File::ensureDirectoryExists($this->evidenceRoot.'/issues');
    $this->app->instance(FinanceReportIssues::class, new FinanceReportIssues($this->evidenceRoot));
    // One real registered report, so its stored PDF link resolves as it does in production.
    $source = collect(array_map('str_getcsv', file(base_path('data/towns/executive-summaries/sources.csv'), FILE_IGNORE_NEW_LINES)))
        ->first(fn (array $row) => $row[0] === '2017' && $row[2] === 'Lasam');
    $this->town = Municipality::factory()->create(['psgc_code' => $source[1], 'name' => 'Lasam']);
    Municipality::factory()->create(['psgc_code' => '0201501000', 'name' => 'Abulug']);
    $this->document = FinanceDocument::factory()->for($this->town)->create([
        'fiscal_year' => 2017, 'file' => 'executive-summaries/'.$source[3], 'sha256' => $source[6],
    ]);
    FinanceFigure::factory()->for($this->town)->line('performance.revenue', 2017, '126042212.26')
        ->create(['finance_document_id' => $this->document->id]);
    $handle = fopen($this->evidenceRoot.'/issues/2017.csv', 'w');
    fputcsv($handle, ['psgc_code', 'reported_in_year', 'severity', 'kind', 'family', 'line_code', 'message', 'location', 'document_kind', 'document_sha256', 'fiscal_year'], escape: '');
    foreach ([
        ['warning', 'identity_text_conflict', 'report', '', 'The introduction names Lasam, but the audit scope sentence names Abulug', 'page 1', '2017'],
        ['info', 'restated', 'between_reports', 'performance.revenue', '2016 was 112531606.86 in the 2016 report', 'page 1', '2016'],
        ['error', 'engine_disagreement', 'reading', 'performance.revenue', '2017: not confirmed by pdfium', 'page 1', '2017'],
        ['info', 'value_not_reported', 'report', 'performance.revenue', 'Printed dash', 'page 1', '2017'],
        ['error', 'malformed_amount', 'report', '', 'A retired workbook finding', 'Sheet!A1', ''],
    ] as [$severity, $kind, $family, $line, $message, $location, $fiscalYear]) {
        fputcsv($handle, [$this->town->psgc_code, 2017, $severity, $kind, $family, $line, $message, $location,
            $location === 'Sheet!A1' ? 'fs_workbook' : 'executive_summary', '', $fiscalYear], escape: '');
    }
    fclose($handle);
});

afterEach(function () {
    if (str_starts_with($this->evidenceRoot, storage_path('framework/testing/report-differences-'))) {
        File::deleteDirectory($this->evidenceRoot);
    }
});

it('counts Executive Summary findings by family and leaves uncollected reports unavailable', function () {
    $this->actingAs(User::factory()->create(['role' => UserRole::Staff]))
        ->get(route('capitol.report-differences.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('capitol/report-differences/index')
            ->missing('detail')
            ->where('differences.years', [2017])
            ->where('differences.rows.0.town', 'Abulug')
            ->where('differences.rows.0.cells.2017', null)
            // Errors and warnings count; an informational note counts only when it is between reports. Retired workbook rows stay out (D49).
            ->where('differences.rows.1.cells.2017', ['report' => 1, 'between' => 1, 'reading' => 1, 'pending' => 1, 'verified' => 0]));
});

it('loads one report\'s findings only when it is opened, with stored PDF page links', function () {
    $url = route('capitol.report-differences.index', ['town' => $this->town->psgc_code, 'year' => 2017]);

    $this->actingAs(User::factory()->create())->get($url)
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->missing('detail')
            ->reloadOnly('detail', fn (Assert $reload) => $reload
                ->missing('differences')
                ->where('detail.town', 'Lasam')
                ->where('detail.pending', 1)
                ->where('detail.sourceUrl', route('public.source-reports.show', hash('sha256', $this->document->file), absolute: false))
                ->has('detail.items', 3)
                ->where('detail.items.0.kind', 'identity_text_conflict')
                ->where('detail.items.0.sourceUrl', fn (string $link) => str_ends_with($link, '#page=1'))
                ->where('detail.items.1.family', 'between_reports')
                ->where('detail.items.2.line', FinanceLine::query()->where('code', 'performance.revenue')->value('label'))));
});

it('rejects an unknown family or a report without its year', function (array $query, string $field) {
    $this->actingAs(User::factory()->create())
        ->getJson(route('capitol.report-differences.index', $query))
        ->assertUnprocessable()->assertJsonValidationErrors($field);
})->with([
    'unknown family' => [['family' => 'errors'], 'family'],
    'town without a year' => [['town' => '0201501000'], 'year'],
]);

it('is part of the Capitol workspace only', function () {
    $this->get(route('capitol.report-differences.index'))->assertNotFound();
    $this->actingAs(User::factory()->withoutRole()->create())
        ->get(route('capitol.report-differences.index'))->assertForbidden();
});
