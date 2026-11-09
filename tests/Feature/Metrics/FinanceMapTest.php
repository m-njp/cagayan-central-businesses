<?php

use App\Enums\TownMeasure;
use App\Models\FinanceDocument;
use App\Models\FinanceFigure;
use App\Models\FinanceLine;
use App\Models\Municipality;
use App\Models\TownFact;
use App\Queries\FinanceMapQuery;
use App\Services\FinanceSignOff;
use App\Services\RefreshFinanceMetrics;

beforeEach(function () {
    FinanceLine::factory()->create(['code' => 'performance.surplus', 'source' => 'derived']);
});

it('uses one latest paired fiscal year and preserves exact-year gaps', function () {
    $alpha = Municipality::factory()->create();
    $beta = Municipality::factory()->create();
    foreach ([[$alpha, 2024], [$alpha, 2025], [$beta, 2024]] as [$town, $year]) {
        FinanceFigure::factory()->verified()->for($town)->line('performance.revenue', $year, '100.00')->create();
        FinanceFigure::factory()->verified()->for($town)->line('performance.expenses', $year, '120.00')->create();
    }
    FinanceFigure::factory()->verified()->for($beta)->line('performance.revenue', 2026, '0.00')->create();
    app(RefreshFinanceMetrics::class)->handle();

    $map = app(FinanceMapQuery::class)->get();

    expect($map->years)->toBe([2026, 2025, 2024])
        ->and($map->latestYear)->toBe(2025)
        ->and($map->observations[$alpha->psgc_code][2025]['performance.surplus']->value)->toBe('-20.00')
        ->and($map->observations[$beta->psgc_code][2025] ?? [])->toBe([])
        ->and($map->observations[$beta->psgc_code][2026]['performance.revenue']->value)->toBe('0.00')
        ->and($map->observations['0201500000'][2025]['performance.revenue']->coverage['reporting'])->toBe(1);

    FinanceFigure::factory()->verified()->for($beta)->line('performance.expenses', 2026, '0.00')->create();
    app(RefreshFinanceMetrics::class)->handle();
    expect(app(FinanceMapQuery::class)->get()->latestYear)->toBe(2026);
});

it('keeps pending and rejected values and private review metadata out of the map', function () {
    $town = Municipality::factory()->create();
    TownFact::factory()->verified()->for($town)->measure(TownMeasure::Population, 2024, 12345)->create();
    FinanceFigure::factory()->for($town)->line('performance.revenue', 2025, '987654.32')->create();
    FinanceFigure::factory()->for($town)->line('performance.expenses', 2025, '876543.21')->create(['status' => 'rejected', 'reviewed_on' => '2026-10-28']);
    app(RefreshFinanceMetrics::class)->handle();
    $map = app(FinanceMapQuery::class)->get();
    expect($map->years)->toBe([])->and($map->latestYear)->toBeNull();

    FinanceFigure::factory()->verified()->for($town)->line('performance.revenue', 2024, '100.00')->create(['note' => 'PRIVATE REVIEW NOTE']);
    app(RefreshFinanceMetrics::class)->handle();
    $json = json_encode(app(FinanceMapQuery::class)->get());
    expect($json)->not->toContain('987654.32', '876543.21', 'PRIVATE REVIEW NOTE', 'reviewed_by', 'entered_by', 'census:');
});

it('shades signed deficits and zero values in SQL and sums only paired surplus inputs', function () {
    $towns = Municipality::factory()->count(3)->create();
    foreach ($towns as $index => $town) {
        FinanceFigure::factory()->verified()->for($town)->line('performance.revenue', 2024, (string) ($index * 10))->create();
        if ($index < 2) {
            FinanceFigure::factory()->verified()->for($town)->line('performance.expenses', 2024, '10.00')->create();
        }
    }
    app(RefreshFinanceMetrics::class)->handle();
    $map = app(FinanceMapQuery::class)->get();
    $metric = 'performance.surplus';

    // Paired towns contribute -10 and 0; the town with revenue only cannot contribute surplus.
    expect($map->observations['0201500000'][2024][$metric]->value)->toBe('-10.00')
        ->and($map->observations['0201500000'][2024][$metric]->coverage['reporting'])->toBe(2)
        ->and($map->observations['0201500000'][2024]['performance.revenue']->coverage['reporting'])->toBe(3)
        ->and($map->shades[$towns[0]->psgc_code][2024][$metric])->toBe(1)
        ->and($map->shades[$towns[1]->psgc_code][2024][$metric])->toBe(2)
        ->and($map->shades[$towns[2]->psgc_code][2024][$metric] ?? null)->toBeNull();
});

it('uses restated values and exposes both derived input sources without census evidence', function () {
    $town = Municipality::factory()->create();
    $old = FinanceFigure::factory()->verified()->for($town)->line('performance.revenue', 2024, '100.00')->create();
    $later = FinanceDocument::factory()->for($town)->create(['fiscal_year' => 2025]);
    $restated = FinanceFigure::factory()->for($town)->line('performance.revenue', 2024, '110.00')->create(['finance_document_id' => $later->id]);
    $expenses = FinanceFigure::factory()->verified()->for($town)->line('performance.expenses', 2024, '120.00')->create();
    app(FinanceSignOff::class)->verify($restated, null, '2026-10-28');
    app(RefreshFinanceMetrics::class)->handle();

    $map = app(FinanceMapQuery::class)->get();
    $point = $map->observations[$town->psgc_code][2024]['performance.revenue'];
    $surplus = $map->observations[$town->psgc_code][2024]['performance.surplus'];
    expect($point->value)->toBe('110.00')->and($point->restatedIn)->toBe(2025)
        ->and($surplus->value)->toBe('-10.00')
        ->and($surplus->sourceRefs)->toContain($restated->public_id, $expenses->public_id)
        ->and(array_keys($map->sources))->not->toContain($old->public_id);
});
