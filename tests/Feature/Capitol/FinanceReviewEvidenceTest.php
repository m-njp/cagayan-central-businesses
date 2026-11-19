<?php

use App\Enums\FinanceFigureStatus;
use App\Enums\FinanceUploadStatus;
use App\Models\FinanceDocument;
use App\Models\FinanceFigure;
use App\Models\FinanceUpload;
use App\Models\Municipality;
use App\Models\User;
use App\Services\FinanceParsingProgress;
use App\Services\FinanceReportIssues;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->evidenceRoot = storage_path('framework/testing/finance-evidence-'.Str::uuid());
    File::ensureDirectoryExists($this->evidenceRoot.'/issues');
    $this->app->instance(FinanceReportIssues::class, new FinanceReportIssues($this->evidenceRoot));
    $this->town = Municipality::factory()->create(['psgc_code' => '0201501000', 'name' => 'Abulug']);
    $this->document = FinanceDocument::factory()->for($this->town)->create([
        'fiscal_year' => 2025, 'file' => 'executive-summaries/2025/Abulug/evidence.pdf', 'sha256' => str_repeat('a', 64),
    ]);
    $this->figure = FinanceFigure::factory()->for($this->town)->line('performance.revenue', 2025, '100.00')
        ->create(['finance_document_id' => $this->document->id, 'location' => 'page 1']);
    $this->admin = User::factory()->create();
    $this->reviewUrl = route('capitol.finances.reviews.store', ['town' => $this->town->psgc_code, 'year' => 2025]);
});

afterEach(function () {
    if (str_starts_with($this->evidenceRoot, storage_path('framework/testing/finance-evidence-'))) {
        File::deleteDirectory($this->evidenceRoot);
    }
});

/** @return array<string, mixed> */
function sourceEvidenceIssue(string $kind, string $line = '', string $severity = 'error'): array
{
    return ['severity' => $severity, 'code' => $kind, 'line' => $line, 'message' => 'Inspect the source evidence.', 'location' => 'page 1'];
}

/** @param list<array<string, mixed>> $issues */
function sourceEvidenceUpload(FinanceDocument $document, array $issues, int $minutesAgo = 0): FinanceUpload
{
    return FinanceUpload::factory()->create([
        'municipality_id' => $document->municipality_id, 'fiscal_year' => $document->fiscal_year,
        'kind' => $document->kind->value, 'sha256' => $document->sha256, 'status' => FinanceUploadStatus::Done,
        'issues' => $issues, 'updated_at' => now()->subMinutes($minutesAgo),
    ]);
}

/** @param list<array<string, string>> $issues */
function registeredSourceEvidence(array $issues): void
{
    $root = test()->evidenceRoot;
    $documents = FinanceDocument::query()->where('municipality_id', test()->town->id)->get();
    $handle = fopen($root.'/documents.csv', 'w');
    fputcsv($handle, ['psgc_code', 'fiscal_year', 'kind', 'availability', 'file', 'sha256'], escape: '');
    foreach ($documents as $document) {
        fputcsv($handle, [test()->town->psgc_code, $document->fiscal_year, $document->kind->value, 'available', $document->file, $document->sha256], escape: '');
    }
    fclose($handle);
    $handle = fopen($root.'/issues/2025.csv', 'w');
    fputcsv($handle, ['psgc_code', 'reported_in_year', 'severity', 'kind', 'line_code', 'message', 'location', 'document_kind', 'document_sha256'], escape: '');
    foreach ($issues as $issue) {
        fputcsv($handle, [test()->town->psgc_code, '2025', $issue['severity'], $issue['kind'], $issue['line'] ?? '',
            'Inspect the registered source.', 'page 1', $issue['document_kind'] ?? '', $issue['document_sha256'] ?? ''], escape: '');
    }
    fclose($handle);
}

it('blocks hard source evidence atomically but still permits returning a figure', function () {
    $expenses = FinanceFigure::factory()->for($this->town)->line('performance.expenses', 2025, '80.00')
        ->create(['finance_document_id' => $this->document->id]);
    sourceEvidenceUpload($this->document, [sourceEvidenceIssue('engine_disagreement', 'performance.expenses')]);

    $this->actingAs($this->admin)->post($this->reviewUrl, [
        'decision' => 'verified', 'figures' => [$this->figure->public_id, $expenses->public_id],
    ])->assertSessionHasErrors('figures');

    expect($this->figure->fresh()->status)->toBe(FinanceFigureStatus::Pending);
    expect($expenses->fresh()->status)->toBe(FinanceFigureStatus::Pending);
    $this->assertDatabaseCount('activity_logs', 0);
    $this->assertDatabaseCount('stats_finance_year', 0);

    $this->post($this->reviewUrl, ['decision' => 'rejected', 'figures' => [$expenses->public_id], 'note' => 'The source year is ambiguous.'])
        ->assertSessionHasNoErrors()->assertRedirect();
    expect($expenses->fresh()->status)->toBe(FinanceFigureStatus::Rejected);
});

it('keeps unrelated clean lines reviewable when one parsed line has an evidence error', function () {
    FinanceFigure::factory()->for($this->town)->line('performance.expenses', 2025, '80.00')
        ->create(['finance_document_id' => $this->document->id]);
    sourceEvidenceUpload($this->document, [sourceEvidenceIssue('engine_disagreement', 'performance.expenses')]);

    $this->actingAs($this->admin)->post($this->reviewUrl, ['decision' => 'verified', 'figures' => [$this->figure->public_id]])
        ->assertSessionHasNoErrors()->assertRedirect();

    expect($this->figure->fresh()->status)->toBe(FinanceFigureStatus::Verified);
});

it('does not let an unrecognized line narrow an unknown source error out of verification', function () {
    sourceEvidenceUpload($this->document, [sourceEvidenceIssue('future_integrity_error', 'unknown.line')]);

    $this->actingAs($this->admin)->post($this->reviewUrl, ['decision' => 'verified', 'figures' => [$this->figure->public_id]])
        ->assertSessionHasErrors('figures');

    expect($this->figure->fresh()->status)->toBe(FinanceFigureStatus::Pending);
});

it('blocks document-wide and unknown hard evidence findings', function (string $kind, string $severity) {
    sourceEvidenceUpload($this->document, [sourceEvidenceIssue($kind, '', $severity)]);

    $this->actingAs($this->admin)->post($this->reviewUrl, ['decision' => 'verified', 'figures' => [$this->figure->public_id]])
        ->assertSessionHasErrors('figures');

    expect($this->figure->fresh()->status)->toBe(FinanceFigureStatus::Pending);
})->with([
    'wrong document year' => ['header_year_mismatch', 'error'],
    'unconfirmed second reader' => ['digits_unconfirmed', 'warning'],
    'assumed header year' => ['header_years_missing', 'warning'],
    'future hard error' => ['new_integrity_failure', 'error'],
]);

it('leaves source arithmetic findings visible and permits the existing no-note human sign-off', function (string $kind) {
    sourceEvidenceUpload($this->document, [sourceEvidenceIssue($kind)]);
    $this->actingAs($this->admin)->get(route('capitol.finances.show', ['town' => $this->town->psgc_code, 'year' => 2025]))
        ->assertInertia(fn (Assert $page) => $page->where('report.issues.0.kind', $kind));

    $this->post($this->reviewUrl, ['decision' => 'verified', 'figures' => [$this->figure->public_id]])
        ->assertSessionHasNoErrors()->assertRedirect();

    expect($this->figure->fresh()->status)->toBe(FinanceFigureStatus::Verified);
    expect($this->figure->fresh()->note)->toBeNull();
})->with(['balance_mismatch', 'net_income_mismatch', 'change_mismatch']);

it('does not hide older PDF findings behind newer unrelated clean uploads', function () {
    sourceEvidenceUpload($this->document, [sourceEvidenceIssue('engine_disagreement', 'performance.revenue')], 5);
    FinanceUpload::factory()->for($this->town)->count(6)->create([
        'fiscal_year' => 2025, 'status' => FinanceUploadStatus::Done, 'issues' => [],
    ]);

    $this->actingAs($this->admin)->get(route('capitol.finances.show', ['town' => $this->town->psgc_code, 'year' => 2025]))
        ->assertInertia(fn (Assert $page) => $page->where('report.issues.0.kind', 'engine_disagreement'));
    $this->post($this->reviewUrl, ['decision' => 'verified', 'figures' => [$this->figure->public_id]])->assertSessionHasErrors('figures');
    expect($this->figure->fresh()->status)->toBe(FinanceFigureStatus::Pending);
});

it('allows a successful reparse of the same source to replace obsolete findings', function () {
    $upload = sourceEvidenceUpload($this->document, [sourceEvidenceIssue('engine_disagreement')], 5);
    $upload->update(['issues' => [], 'updated_at' => now()]);

    $this->actingAs($this->admin)->get(route('capitol.finances.show', ['town' => $this->town->psgc_code, 'year' => 2025]))
        ->assertInertia(fn (Assert $page) => $page->has('report.issues', 0));
    $this->post($this->reviewUrl, ['decision' => 'verified', 'figures' => [$this->figure->public_id]])->assertSessionHasNoErrors();
    expect($this->figure->fresh()->status)->toBe(FinanceFigureStatus::Verified);
});

it('does not erase hard findings when a same-source retry is unfinished or failed', function (FinanceUploadStatus $status) {
    $upload = sourceEvidenceUpload($this->document, [sourceEvidenceIssue('engine_disagreement')], 5);
    $upload->update(['status' => $status, 'issues' => null]);

    $this->actingAs($this->admin)->get(route('capitol.finances.show', ['town' => $this->town->psgc_code, 'year' => 2025]))
        ->assertInertia(fn (Assert $page) => $page->where('report.issues.0.kind', 'parser_incomplete'));
    $this->post($this->reviewUrl, ['decision' => 'verified', 'figures' => [$this->figure->public_id]])->assertSessionHasErrors('figures');

    expect($this->figure->fresh()->status)->toBe(FinanceFigureStatus::Pending);
})->with([
    'queued retry' => [FinanceUploadStatus::Queued],
    'running retry' => [FinanceUploadStatus::Running],
    'failed retry' => [FinanceUploadStatus::Failed],
    'missing final findings' => [FinanceUploadStatus::Done],
]);

it('binds registered issues to the exact document hash instead of just municipality and year', function () {
    registeredSourceEvidence([['severity' => 'error', 'kind' => 'header_year_mismatch']]);
    $this->actingAs($this->admin)->post($this->reviewUrl, ['decision' => 'verified', 'figures' => [$this->figure->public_id]])
        ->assertSessionHasErrors('figures');

    $this->document->update(['file' => 'finance-uploads/different-source.pdf', 'sha256' => str_repeat('b', 64)]);
    sourceEvidenceUpload($this->document, []);
    $this->post($this->reviewUrl, ['decision' => 'verified', 'figures' => [$this->figure->public_id]])->assertSessionHasNoErrors();
    expect($this->figure->fresh()->status)->toBe(FinanceFigureStatus::Verified);
});

it('fails closed when registered source diagnostics are missing or malformed', function (string $problem) {
    registeredSourceEvidence([['severity' => 'error', 'kind' => 'header_year_mismatch']]);
    $path = $this->evidenceRoot.'/issues/2025.csv';
    $contents = File::get($path);
    $header = substr($contents, 0, strpos($contents, "\n") + 1);
    if ($problem === 'not a file') {
        // Windows can keep a deleted file pending while another process holds it open.
        File::move($path, $this->evidenceRoot.'/previous-issues.csv');
        File::ensureDirectoryExists($path);
    } else {
        match ($problem) {
            'missing' => File::delete($path),
            'bad header' => File::put($path, "not,the,diagnostic,columns\n"),
            'truncated row' => File::put($path, $header."0201501000,2025,error\n"),
            'unterminated quote' => File::put($path, $header."0201501000,2025,error,engine_disagreement,,\"unfinished message,page 1,,\n"),
            'incomplete final record' => File::put($path, rtrim($contents, "\n")),
            'invalid severity' => File::put($path, str_replace(',error,', ',fatal,', $contents)),
            'wrong report year' => File::put($path, str_replace(',2025,', ',2024,', $contents)),
        };
    }

    $this->actingAs($this->admin)->get(route('capitol.finances.show', ['town' => $this->town->psgc_code, 'year' => 2025]))
        ->assertInertia(fn (Assert $page) => $page->where('report.issues.0.kind', 'diagnostics_unavailable'));
    $this->post($this->reviewUrl, ['decision' => 'verified', 'figures' => [$this->figure->public_id]])->assertSessionHasErrors('figures');

    expect($this->figure->fresh()->status)->toBe(FinanceFigureStatus::Pending);
    $this->assertDatabaseCount('stats_finance_year', 0);
})->with(['missing', 'not a file', 'bad header', 'truncated row', 'unterminated quote', 'incomplete final record', 'invalid severity', 'wrong report year']);

it('accepts fresh successful checks of the same source when registered diagnostics are unavailable', function () {
    registeredSourceEvidence([['severity' => 'error', 'kind' => 'header_year_mismatch']]);
    File::delete($this->evidenceRoot.'/issues/2025.csv');
    sourceEvidenceUpload($this->document, []);

    $this->actingAs($this->admin)->post($this->reviewUrl, ['decision' => 'verified', 'figures' => [$this->figure->public_id]])
        ->assertSessionHasNoErrors()->assertRedirect();

    expect($this->figure->fresh()->status)->toBe(FinanceFigureStatus::Verified);
});

it('keeps a year-specific missing prior observation from blocking the clean current cell', function () {
    $prior = FinanceFigure::factory()->for($this->town)->line('performance.revenue', 2024, '90.00')
        ->create(['finance_document_id' => $this->document->id]);
    sourceEvidenceUpload($this->document, [sourceEvidenceIssue('previous_figure_not_reproduced', 'performance.revenue') + ['fiscal_year' => 2024]]);

    $this->actingAs($this->admin)->post($this->reviewUrl, ['decision' => 'verified', 'figures' => [$this->figure->public_id]])
        ->assertSessionHasNoErrors()->assertRedirect();
    $this->post($this->reviewUrl, ['decision' => 'verified', 'figures' => [$prior->public_id]])->assertSessionHasErrors('figures');

    expect($this->figure->fresh()->status)->toBe(FinanceFigureStatus::Verified);
    expect($prior->fresh()->status)->toBe(FinanceFigureStatus::Pending);
});

it('blocks legacy existing parsed lines that a stricter reparse cannot find', function () {
    sourceEvidenceUpload($this->document, [sourceEvidenceIssue('line_not_found', 'performance.revenue', 'warning')]);

    $this->actingAs($this->admin)->post($this->reviewUrl, ['decision' => 'verified', 'figures' => [$this->figure->public_id]])
        ->assertSessionHasErrors('figures');

    expect($this->figure->fresh()->status)->toBe(FinanceFigureStatus::Pending);
});

it('preserves document-bound issue evidence through upload normalization and blocks mismatched evidence', function () {
    $normalized = FinanceParsingProgress::issue([
        'severity' => 'error', 'kind' => 'engine_failed', 'code' => '', 'message' => 'C:\\private\\source.pdf could not be read',
        'document_kind' => 'executive_summary', 'document_sha256' => str_repeat('b', 64), 'page' => 1,
        'fiscalYear' => 2024,
    ]);
    expect($normalized['document_kind'])->toBe('executive_summary');
    expect($normalized['document_sha256'])->toBe(str_repeat('b', 64));
    expect($normalized['fiscal_year'])->toBe(2024);
    expect($normalized['message'])->not->toContain('private');
    sourceEvidenceUpload($this->document, [$normalized]);

    $this->actingAs($this->admin)->post($this->reviewUrl, ['decision' => 'verified', 'figures' => [$this->figure->public_id]])
        ->assertSessionHasErrors('figures');
    expect($this->figure->fresh()->status)->toBe(FinanceFigureStatus::Pending);
});
