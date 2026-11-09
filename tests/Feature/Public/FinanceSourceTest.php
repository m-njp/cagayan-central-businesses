<?php

use App\Models\FinanceDocument;
use App\Models\FinanceFigure;
use App\Models\Municipality;

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

test('report requests are rate limited', function () {
    $limit = (int) config('cagayan.finances.source_requests_per_minute');
    for ($request = 0; $request < $limit; $request++) {
        $this->get(route('public.finance-sources.show', '00000000-0000-4000-8000-000000000000'))->assertNotFound();
    }

    $this->get(route('public.finance-sources.show', '00000000-0000-4000-8000-000000000000'))->assertTooManyRequests();
});
