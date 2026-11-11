<?php

use App\Enums\FinanceDocumentKind;
use App\Models\FinanceDocument;
use App\Models\FinanceFigure;
use App\Models\FinanceUpload;
use App\Models\Municipality;
use Inertia\Testing\AssertableInertia as Assert;

// The stored COA report behind a signed-off figure. Only verified figures resolve; the file comes from the ledger, never the request.

function sourceReport(string $kind = 'pdf'): string
{
    $path = base_path('data/towns/zz-source-test/report.'.$kind);
    @mkdir(dirname($path), 0777, true);
    file_put_contents($path, $kind === 'pdf' ? "%PDF-1.4\n%test\n" : 'workbook');

    return 'zz-source-test/report.'.$kind;
}

afterEach(function () {
    array_map('unlink', glob(base_path('data/towns/zz-source-test/*')) ?: []);
    @rmdir(base_path('data/towns/zz-source-test'));
});

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
