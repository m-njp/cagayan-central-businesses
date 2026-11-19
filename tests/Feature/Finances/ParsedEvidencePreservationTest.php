<?php

use App\Actions\RecordParsedFigures;
use App\Enums\FinanceFigureStatus;
use App\Models\FinanceDocument;
use App\Models\FinanceFigure;
use App\Models\Municipality;

test('an unchanged reviewed amount retains the evidence its reviewer saw during staff reparse', function (FinanceFigureStatus $status) {
    $town = Municipality::factory()->create();
    $document = FinanceDocument::factory()->for($town)->create(['fiscal_year' => 2025]);
    $originalInputs = [['location' => 'page 1', 'value' => '100.00']];
    $figure = FinanceFigure::factory()->for($town)->line('performance.revenue', 2025, '100.00')->create([
        'finance_document_id' => $document->id, 'status' => $status, 'reviewed_on' => '2026-11-11',
        'source_label' => 'Original Income', 'location' => 'page 1', 'extractor_version' => '1.2.0', 'inputs' => $originalInputs,
    ]);

    $waiting = app(RecordParsedFigures::class)->handle($document, [[
        'line_code' => 'performance.revenue', 'fiscal_year' => 2025, 'basis' => 'actual', 'value' => '100.00',
        'source_label' => 'New Income', 'location' => 'page 9', 'inputs' => [['location' => 'page 9', 'value' => '100.00']],
    ]], null, '1.3.0');

    expect($waiting)->toBe(0);
    expect($figure->refresh())->status->toBe($status)->source_label->toBe('Original Income')
        ->location->toBe('page 1')->extractor_version->toBe('1.2.0')->inputs->toEqual($originalInputs);
    expect($figure->reviewed_on?->toDateString())->toBe('2026-11-11');
})->with([FinanceFigureStatus::Superseded, FinanceFigureStatus::Rejected]);
