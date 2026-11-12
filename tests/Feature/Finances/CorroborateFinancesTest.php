<?php

use App\Enums\FinanceFigureStatus;
use App\Models\FinanceDocument;
use App\Models\FinanceFigure;
use App\Models\FinanceLine;
use App\Models\Municipality;
use App\Services\FinanceReportIssues;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->evidenceRoot = storage_path('framework/testing/corroboration-'.Str::uuid());
    File::ensureDirectoryExists($this->evidenceRoot.'/issues');
    $this->app->instance(FinanceReportIssues::class, new FinanceReportIssues($this->evidenceRoot));
    $this->town = Municipality::factory()->create(['psgc_code' => '0201501000']);
    $this->report2015 = corroborationReport(2015);
    $this->report2016 = corroborationReport(2016);
});

afterEach(function () {
    if (str_starts_with($this->evidenceRoot, storage_path('framework/testing/corroboration-'))) {
        File::deleteDirectory($this->evidenceRoot);
    }
});

function corroborationReport(int $year): FinanceDocument
{
    return FinanceDocument::factory()->for(test()->town)->create([
        'fiscal_year' => $year, 'file' => "executive-summaries/{$year}/Abulug/report.pdf", 'sha256' => str_repeat((string) ($year % 10), 64),
    ]);
}

/** A figure read from a report: its own year's column, or the prior-year column of the next report. */
function corroborationFigure(FinanceDocument $report, int $fiscalYear, string $value, array $attributes = []): FinanceFigure
{
    return FinanceFigure::factory()->for(test()->town)->line('performance.revenue', $fiscalYear, $value)
        ->create(['finance_document_id' => $report->id, ...$attributes]);
}

/** @param list<array{year: int, severity: string, kind: string, line: string}> $issues */
function corroborationIssues(array $issues): void
{
    $root = test()->evidenceRoot;
    $handle = fopen($root.'/documents.csv', 'w');
    fputcsv($handle, ['psgc_code', 'fiscal_year', 'kind', 'availability', 'file', 'sha256'], escape: '');
    foreach ([test()->report2015, test()->report2016] as $report) {
        fputcsv($handle, [test()->town->psgc_code, $report->fiscal_year, 'executive_summary', 'available', $report->file, $report->sha256], escape: '');
    }
    fclose($handle);
    foreach ([2015, 2016] as $year) {
        $handle = fopen($root."/issues/{$year}.csv", 'w');
        fputcsv($handle, ['psgc_code', 'reported_in_year', 'severity', 'kind', 'family', 'line_code', 'message', 'location', 'document_kind', 'document_sha256', 'fiscal_year'], escape: '');
        foreach (array_filter($issues, fn (array $issue) => $issue['year'] === $year) as $issue) {
            fputcsv($handle, [test()->town->psgc_code, $year, $issue['severity'], $issue['kind'], 'report', $issue['line'], 'Inspect the source.', 'page 1', 'executive_summary', '', ''], escape: '');
        }
        fclose($handle);
    }
}

it('signs off both reports when they print the same amount to the cent and refreshes published statistics', function () {
    corroborationIssues([]);
    $own = corroborationFigure($this->report2015, 2015, '61234567.89');
    $repeated = corroborationFigure($this->report2016, 2015, '61234567.89');

    $this->artisan('finances:corroborate')->assertSuccessful();

    // The 2016 report is the later one, so it is the current figure and the 2015 report's own column is superseded (D35).
    expect($repeated->fresh()->status)->toBe(FinanceFigureStatus::Verified)
        ->and($own->fresh()->status)->toBe(FinanceFigureStatus::Superseded)
        ->and($repeated->fresh()->note)->toBe('Corroborated: the 2015 and 2016 COA reports print the same amount')
        ->and($repeated->fresh()->reviewed_by)->toBeNull();
    $this->assertDatabaseHas('stats_finance_year', ['municipality_id' => $this->town->id, 'fiscal_year' => 2015, 'metric' => 'performance.revenue', 'value' => '61234567.89']);
    $this->assertDatabaseHas('activity_logs', ['event' => 'finance_figures_corroborated', 'actor_id' => null]);
});

it('keeps figures Pending when the reports differ, are not both available or name another definition', function (string $later, ?string $variant, bool $laterExists) {
    corroborationIssues([]);
    $own = corroborationFigure($this->report2015, 2015, '61234567.89');
    $repeated = $laterExists ? corroborationFigure($this->report2016, 2015, $later, ['definition_variant' => $variant]) : null;

    $this->artisan('finances:corroborate')->assertSuccessful();

    expect($own->fresh()->status)->toBe(FinanceFigureStatus::Pending)
        ->and($repeated?->fresh()->status ?? FinanceFigureStatus::Pending)->toBe(FinanceFigureStatus::Pending);
    $this->assertDatabaseCount('stats_finance_year', 0);
})->with([
    'one centavo apart' => ['61234567.90', null, true],
    'restated in the later report' => ['70000000.00', null, true],
    'another definition' => ['61234567.89', 'includes_subsidy_from_other_funds', true],
    'only one report' => ['', null, false],
]);

it('keeps both figures Pending when either report has an error on that line or across the document', function (int $year, string $kind, string $line) {
    corroborationIssues([['year' => $year, 'severity' => 'error', 'kind' => $kind, 'line' => $line]]);
    $own = corroborationFigure($this->report2015, 2015, '61234567.89');
    $repeated = corroborationFigure($this->report2016, 2015, '61234567.89');

    $this->artisan('finances:corroborate')->assertSuccessful();

    expect($own->fresh()->status)->toBe(FinanceFigureStatus::Pending)
        ->and($repeated->fresh()->status)->toBe(FinanceFigureStatus::Pending);
})->with([
    'misprinted amount in the earlier report' => [2015, 'malformed_amount', 'performance.revenue'],
    'readers disagree in the later report' => [2016, 'engine_disagreement', 'performance.revenue'],
    'source arithmetic on the same line' => [2016, 'net_income_mismatch', 'performance.revenue'],
    'header years across the document' => [2016, 'header_year_mismatch', ''],
]);

it('ignores an error that names a different catalog line', function () {
    FinanceLine::factory()->create(['code' => 'performance.expenses']);
    corroborationIssues([['year' => 2016, 'severity' => 'error', 'kind' => 'malformed_amount', 'line' => 'performance.expenses']]);
    corroborationFigure($this->report2015, 2015, '61234567.89');
    $repeated = corroborationFigure($this->report2016, 2015, '61234567.89');

    $this->artisan('finances:corroborate')->assertSuccessful();

    expect($repeated->fresh()->status)->toBe(FinanceFigureStatus::Verified);
});

it('signs off a Pending figure against one that was already verified and leaves reviewed figures untouched', function () {
    corroborationIssues([]);
    $own = corroborationFigure($this->report2015, 2015, '61234567.89');
    $verified = corroborationFigure($this->report2016, 2015, '61234567.89', ['status' => FinanceFigureStatus::Verified, 'reviewed_on' => '2026-10-28', 'note' => 'Signed off by a person']);

    $this->artisan('finances:corroborate')->assertSuccessful();

    expect($own->fresh()->status)->toBe(FinanceFigureStatus::Superseded)
        ->and($verified->fresh()->status)->toBe(FinanceFigureStatus::Verified)
        ->and($verified->fresh()->note)->toBe('Signed off by a person');
});

it('only counts the figures on a dry run', function () {
    corroborationIssues([]);
    $own = corroborationFigure($this->report2015, 2015, '61234567.89');
    corroborationFigure($this->report2016, 2015, '61234567.89');

    $this->artisan('finances:corroborate', ['--dry-run' => true])
        ->expectsOutputToContain('2 Pending figures are corroborated. Nothing was signed off.')
        ->assertSuccessful();

    expect($own->fresh()->status)->toBe(FinanceFigureStatus::Pending);
    expect(DB::table('activity_logs')->count())->toBe(0);
    $this->assertDatabaseCount('stats_finance_year', 0);
});
