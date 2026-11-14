<?php

use App\Enums\FinanceDocumentKind;
use App\Models\FinanceDocument;
use App\Models\FinanceFigure;
use App\Models\FinanceUpload;
use App\Models\Municipality;
use App\Services\PublicExecutiveSummaries;
use App\Services\PublicWorkbooks;
use Illuminate\Support\Facades\File;
use Inertia\Testing\AssertableInertia as Assert;

// The stored COA report behind a signed-off figure. Only verified figures resolve; the file comes from the ledger, never the request.

beforeEach(function () {
    $this->app->instance(PublicExecutiveSummaries::class, new PublicExecutiveSummaries(base_path('data/towns/zz-source-test')));
    $this->app->instance(PublicWorkbooks::class, new PublicWorkbooks(base_path('data/towns/zz-source-test')));
});

/** A COA workbook copy registered the way data/towns/financial-statements/index.csv records it. */
function registeredWorkbook(array $overrides = []): array
{
    $root = base_path('data/towns/zz-source-test');
    $relative = $overrides['file'] ?? 'financial-statements/2025/Alcala/Alcala-Financial-Statements-2025.xlsx';
    File::ensureDirectoryExists(dirname($root.'/'.$relative));
    file_put_contents($root.'/'.$relative, 'PKworkbook');
    $row = array_replace([
        'report_year' => '2025', 'municipality' => 'Alcala', 'kind' => 'fs_workbook', 'file' => $relative,
        'sha256' => hash_file('sha256', $root.'/'.$relative),
        'source_url' => 'https://www.coa.gov.ph/download/1/cagayan/2/alcala-annual-audit-report-2025.zip',
        'original_archive_members' => '07-Alcala2025_FS.xlsx', 'original_collection_files' => '',
    ], $overrides);
    $handle = fopen($root.'/financial-statements/index.csv', 'w');
    fputcsv($handle, array_keys($row), escape: '');
    fputcsv($handle, array_values($row), escape: '');
    fclose($handle);

    return ['key' => hash('sha256', $relative), 'path' => $root.'/'.$relative, 'sha256' => $row['sha256']];
}

function sourceReport(string $kind = 'pdf'): string
{
    $path = base_path('data/towns/zz-source-test/report.'.$kind);
    @mkdir(dirname($path), 0777, true);
    file_put_contents($path, $kind === 'pdf' ? "%PDF-1.4\n%test\n" : 'workbook');

    return 'zz-source-test/report.'.$kind;
}

afterEach(function () {
    File::deleteDirectory(base_path('data/towns/zz-source-test'));
});

function registeredExecutiveSummary(array $overrides = []): array
{
    $root = base_path('data/towns/zz-source-test');
    $relative = '2025/Alcala/summary.pdf';
    File::ensureDirectoryExists(dirname($root.'/'.$relative));
    file_put_contents($root.'/'.$relative, "%PDF-1.4\n%test\n");
    $row = array_replace([
        'year' => '2025', 'psgc_code' => '0201502000', 'town' => 'Alcala', 'file' => $relative,
        'pages' => '1', 'bytes' => filesize($root.'/'.$relative),
        'sha256' => hash_file('sha256', $root.'/'.$relative),
        'source_url' => 'https://www.coa.gov.ph/summary.pdf', 'retrieved_on' => '2026-11-12',
    ], $overrides);
    $handle = fopen($root.'/sources.csv', 'w');
    fputcsv($handle, array_keys($row), escape: '');
    fputcsv($handle, array_values($row), escape: '');
    fclose($handle);

    return ['key' => hash('sha256', 'executive-summaries/'.$relative), 'path' => $root.'/'.$relative, 'sha256' => $row['sha256']];
}

test('a signed-off figure opens its stored report as a pdf', function () {
    $town = Municipality::factory()->create();
    $document = FinanceDocument::factory()->for($town)->create(['file' => sourceReport('pdf')]);
    $figure = FinanceFigure::factory()->verified()->for($town)->line('performance.revenue', 2024, '100.00')->create(['finance_document_id' => $document->id]);

    $this->get(route('public.finance-sources.show', $figure->public_id))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf')
        ->assertHeaderMissing('content-disposition', 'attachment');
});

test('a workbook downloads instead of opening', function () {
    $town = Municipality::factory()->create();
    $document = FinanceDocument::factory()->for($town)->create(['file' => sourceReport('xlsx')]);
    $figure = FinanceFigure::factory()->verified()->for($town)->line('performance.revenue', 2024, '100.00')->create(['finance_document_id' => $document->id]);

    $this->get(route('public.finance-sources.show', $figure->public_id))
        ->assertOk()
        ->assertDownload('report.xlsx');
});

test('a pending figure, an entered figure and a missing file answer not found', function () {
    $town = Municipality::factory()->create();
    $document = FinanceDocument::factory()->for($town)->create(['file' => sourceReport('pdf')]);
    $pending = FinanceFigure::factory()->for($town)->line('performance.revenue', 2024, '100.00')->create(['finance_document_id' => $document->id]);
    $entered = FinanceFigure::factory()->manual()->verified()->for($town)->line('performance.expenses', 2024, '50.00')->create(['source_url' => 'https://www.coa.gov.ph/a.pdf']);
    $gone = FinanceDocument::factory()->for($town)->create(['fiscal_year' => 2023, 'file' => 'zz-source-test/gone.pdf']);
    $missing = FinanceFigure::factory()->verified()->for($town)->line('performance.revenue', 2023, '10.00')->create(['finance_document_id' => $gone->id]);

    foreach ([$pending, $entered, $missing] as $figure) {
        $this->get(route('public.finance-sources.show', $figure->public_id))->assertNotFound();
    }
});

test('a file outside the report folder is never served', function () {
    $town = Municipality::factory()->create();
    $document = FinanceDocument::factory()->for($town)->create(['file' => '../../composer.json']);
    $figure = FinanceFigure::factory()->verified()->for($town)->line('performance.revenue', 2024, '100.00')->create(['finance_document_id' => $document->id]);

    $this->get(route('public.finance-sources.show', $figure->public_id))->assertNotFound();
});

test('the public catalog lists only accessible verified report pages and workbooks', function () {
    $town = Municipality::factory()->create(['name' => 'Alcala']);
    $report = FinanceDocument::factory()->for($town)->create(['file' => sourceReport()]);
    $workbook = FinanceDocument::factory()->for($town)->create([
        'kind' => FinanceDocumentKind::FsWorkbook, 'file' => sourceReport('xlsx'),
    ]);
    $missing = FinanceDocument::factory()->for($town)->create(['fiscal_year' => 2023, 'file' => 'zz-source-test/missing.pdf']);
    $private = FinanceDocument::factory()->for($town)->create(['fiscal_year' => 2022, 'file' => FinanceUpload::FOLDER.'/private.pdf']);
    $figure = fn ($document, $line, $location) => FinanceFigure::factory()->for($town)
        ->line($line, $document->fiscal_year, '100.00')
        ->state(['finance_document_id' => $document->id, 'location' => $location]);

    $page = $figure($report, 'performance.revenue', 'page 2')->verified()->create();
    $duplicate = $figure($report, 'performance.expenses', 'pages 2-3')->verified()->create();
    $pending = $figure($report, 'performance.surplus', 'page 9')->create();
    $sheet = $figure($workbook, 'revenue.tax', 'A1!B2')->verified()->create();
    $gone = $figure($missing, 'performance.revenue', 'page 1')->verified()->create();
    $upload = $figure($private, 'performance.revenue', 'page 1')->verified()->create();

    $response = $this->get(route('public.sources'))->assertOk()->assertInertia(fn (Assert $props) => $props
        ->component('public/sources/index')
        ->has('citations', 2)
        ->where('citations.0.town', 'Alcala')
        ->where('citations.0.reportYear', 2024)
        ->where('citations.0.page', 2)
        ->where('citations.0.url', route('public.finance-sources.show', $page->public_id, absolute: false))
        ->missing('citations.0.inputs')
        ->where('citations.1.kind', 'fs_workbook')
        ->where('citations.1.url', route('public.finance-sources.show', $sheet->public_id, absolute: false)));

    foreach ([$duplicate, $pending, $gone, $upload] as $excluded) {
        $response->assertDontSee($excluded->public_id);
    }
});

test('the archive contains one approved copy per report and rejects a changed checksum', function () {
    $town = Municipality::factory()->create(['name' => 'Alcala']);
    $file = sourceReport();
    $checksum = hash('sha256', "%PDF-1.4\n%test\n");
    $document = FinanceDocument::factory()->for($town)->create(['file' => $file, 'sha256' => $checksum]);
    foreach (['performance.revenue', 'performance.expenses'] as $line) {
        FinanceFigure::factory()->verified()->for($town)->line($line, 2024, '100.00')->create(['finance_document_id' => $document->id]);
    }
    $private = FinanceDocument::factory()->for($town)->create(['fiscal_year' => 2023, 'file' => FinanceUpload::FOLDER.'/private.pdf']);
    FinanceFigure::factory()->verified()->for($town)->line('performance.revenue', 2023, '100.00')->create(['finance_document_id' => $private->id]);

    $response = $this->get(route('public.sources-download'))->assertOk()->assertDownload('cagayan-coa-reports.zip');
    $path = $response->baseResponse->getFile()->getPathname();
    $zip = new ZipArchive;
    try {
        expect($zip->open($path))->toBeTrue()
            ->and($zip->numFiles)->toBe(2)
            ->and($zip->getFromName('alcala/2024/report.pdf'))->toBe("%PDF-1.4\n%test\n")
            ->and(json_decode($zip->getFromName('manifest.json'), true))->toBe(['alcala/2024/report.pdf' => $checksum]);
    } finally {
        $zip->close();
        unlink($path);
    }

    file_put_contents(base_path('data/towns/'.$file), '%PDF-replaced');
    $this->get(route('public.sources-download'))->assertServiceUnavailable();
});

test('report requests are rate limited', function () {
    $limit = (int) config('cagayan.finances.source_requests_per_minute');
    for ($request = 0; $request < $limit; $request++) {
        $this->get(route('public.finance-sources.show', '00000000-0000-4000-8000-000000000000'))->assertNotFound();
    }

    $this->get(route('public.finance-sources.show', '00000000-0000-4000-8000-000000000000'))->assertTooManyRequests();
});

test('collected original reports are public without publishing any finance figures', function () {
    $report = registeredExecutiveSummary();

    $this->get(route('public.sources'))->assertOk()->assertInertia(fn (Assert $props) => $props
        ->component('public/sources/index')->has('citations', 1)
        ->where('citations.0.town', 'Alcala')->where('citations.0.reportYear', 2025)
        ->where('citations.0.page', null)
        ->where('citations.0.url', route('public.source-reports.show', $report['key'], absolute: false))
        ->missing('citations.0.path')->missing('citations.0.inputs')->missing('citations.0.value'));

    $this->get(route('public.source-reports.show', $report['key']))
        ->assertOk()->assertHeader('content-type', 'application/pdf');
    $this->assertDatabaseCount('finance_figures', 0);
});

test('unregistered files and altered original PDFs cannot be downloaded', function () {
    $report = registeredExecutiveSummary();
    $this->get(route('public.source-reports.show', str_repeat('a', 64)))->assertNotFound();
    file_put_contents($report['path'], "%PDF-1.4\n%fake\n");
    $this->get(route('public.source-reports.show', $report['key']))->assertNotFound();
    $this->get(route('public.sources-download'))->assertServiceUnavailable();
});

test('invalid source manifest entries are not exposed', function (array $overrides) {
    $report = registeredExecutiveSummary($overrides);
    $this->get(route('public.sources'))->assertOk()->assertInertia(fn (Assert $props) => $props->has('citations', 0));
    $this->get(route('public.source-reports.show', $report['key']))->assertNotFound();
})->with([
    'outside the source root' => [['file' => '../../composer.json']],
    'non-COA provenance' => [['source_url' => 'https://coa.gov.ph.example.com/source.pdf']],
    'missing bytes' => [['bytes' => 1]],
    'invalid checksum' => [['sha256' => 'not-a-checksum']],
    'wrong report year' => [['year' => 2024]],
]);

test('the complete archive includes registered originals with their expected checksums', function () {
    $report = registeredExecutiveSummary();
    $response = $this->get(route('public.sources-download'))->assertOk()->assertDownload('cagayan-coa-reports.zip');
    $path = $response->baseResponse->getFile()->getPathname();
    $zip = new ZipArchive;
    try {
        expect($zip->open($path))->toBeTrue()
            ->and($zip->getFromName('alcala/2025/summary.pdf'))->toBe("%PDF-1.4\n%test\n")
            ->and(json_decode($zip->getFromName('manifest.json'), true))->toBe(['alcala/2025/summary.pdf' => $report['sha256']]);
    } finally {
        $zip->close();
        unlink($path);
    }
});

test('every registered workbook is listed and downloads under its readable name, without publishing a figure', function (string $kind, string $label, string $name) {
    $workbook = registeredWorkbook(['kind' => $kind, 'file' => 'financial-statements/2025/Alcala/'.$name]);

    $this->get(route('public.sources'))->assertOk()->assertInertia(fn (Assert $props) => $props
        ->has('citations', 1)
        ->where('citations.0.kind', $kind)->where('citations.0.label', $label)
        ->where('citations.0.town', 'Alcala')->where('citations.0.reportYear', 2025)
        ->where('citations.0.url', route('public.source-workbooks.show', $workbook['key'], absolute: false)));

    $this->get(route('public.source-workbooks.show', $workbook['key']))->assertOk()
        ->assertDownload($name)
        ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    $this->assertDatabaseCount('finance_figures', 0);
})->with([
    ['fs_workbook', 'Financial Statements', 'Alcala-Financial-Statements-2025.xlsx'],
    ['annex_workbook', 'General Fund Annex', 'Alcala-Annexes-2025.xlsx'],
    ['aapsi_workbook', 'AAPSI', 'Alcala-AAPSI-2025.xlsx'],
    ['appendices_workbook', 'Appendices', 'Alcala-Appendices-2025.xlsx'],
]);

test('an unregistered, altered or non-COA workbook cannot be downloaded', function () {
    $workbook = registeredWorkbook();
    $this->get(route('public.source-workbooks.show', str_repeat('b', 64)))->assertNotFound();
    file_put_contents($workbook['path'], 'PK replaced');
    $this->get(route('public.source-workbooks.show', $workbook['key']))->assertNotFound();

    $other = registeredWorkbook(['source_url' => 'https://coa.gov.ph.example.com/report.zip']);
    $this->get(route('public.source-workbooks.show', $other['key']))->assertNotFound();
});

test('the complete archive includes registered workbooks beside the summaries', function () {
    $report = registeredExecutiveSummary();
    $workbook = registeredWorkbook();
    $response = $this->get(route('public.sources-download'))->assertOk();
    $path = $response->baseResponse->getFile()->getPathname();
    $zip = new ZipArchive;
    try {
        expect($zip->open($path))->toBeTrue()
            ->and(json_decode($zip->getFromName('manifest.json'), true))->toBe([
                'alcala/2025/Alcala-Financial-Statements-2025.xlsx' => $workbook['sha256'],
                'alcala/2025/summary.pdf' => $report['sha256'],
            ]);
    } finally {
        $zip->close();
        unlink($path);
    }
});
