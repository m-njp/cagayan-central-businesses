<?php

use App\Data\TownProfile;
use App\Enums\TownMeasure;
use App\Models\FinanceFigure;
use App\Models\Municipality;
use App\Models\TownFact;
use App\Queries\TownAtlasQuery;

/**
 * @param  list<array{TownMeasure, int, int}>  $facts
 */
function townWith(string $name, array $facts): Municipality
{
    $town = Municipality::factory()->create(['name' => $name]);

    foreach ($facts as [$measure, $year, $value]) {
        TownFact::factory()->verified()->for($town)->measure($measure, $year, $value)->create();
    }

    return $town;
}

function signedOff(Municipality $town, string $line, int $year, string $value): FinanceFigure
{
    return FinanceFigure::factory()->verified()->for($town)->line($line, $year, $value)->create();
}

it('uses the newest year of each figure', function () {
    $town = townWith('Alpha', [
        [TownMeasure::Population, 2020, 1000],
        [TownMeasure::Population, 2024, 1100],
    ]);
    signedOff($town, 'performance.revenue', 2023, '4000.00');
    signedOff($town, 'performance.revenue', 2024, '5000.00');

    $alpha = app(TownAtlasQuery::class)->get()->towns[0];

    expect($alpha->population?->value)->toBe('1100.00')
        ->and($alpha->population?->year)->toBe(2024)
        ->and($alpha->revenue?->value)->toBe('5000.00')
        ->and($alpha->revenue?->year)->toBe(2024);
});

it('reports a missing figure as null, never zero', function () {
    townWith('Alpha', []);

    $alpha = app(TownAtlasQuery::class)->get()->towns[0];

    expect($alpha->population)->toBeNull()
        ->and($alpha->expenditure)->toBeNull()
        ->and($alpha->shade)->toBeNull();
});

it('shades towns by population, fewest people first (M18)', function () {
    $shades = config('cagayan.towns.shades');

    foreach (range(1, $shades) as $index) {
        townWith("Town {$index}", [[TownMeasure::Population, 2024, $index * 10]]);
    }

    $atlas = app(TownAtlasQuery::class)->get();

    // One town per shade, so the smallest town gets shade 1 and the largest the top shade.
    expect(collect($atlas->towns)->map(fn (TownProfile $town) => $town->shade)->all())
        ->toBe(range(1, $shades));
});

it('shows a province total only when every town has the figure', function () {
    signedOff(townWith('Alpha', [[TownMeasure::Population, 2024, 100]]), 'performance.revenue', 2024, '5000.00');
    townWith('Beta', [[TownMeasure::Population, 2024, 200]]);

    $province = app(TownAtlasQuery::class)->get()->province;

    expect($province->population?->value)->toBe('300.00')
        ->and($province->revenue)->toBeNull();
});

it('withholds unchecked figures and keeps the latest verified year', function () {
    $town = townWith('Alpha', [[TownMeasure::Population, 2024, 100]]);
    TownFact::factory()->for($town)->measure(TownMeasure::Population, 2025, 200)->create();
    FinanceFigure::factory()->for($town)->line('performance.revenue', 2024, '9999.00')->create();

    $alpha = app(TownAtlasQuery::class)->get()->towns[0];

    expect($alpha->population?->value)->toBe('100.00')
        ->and($alpha->population?->year)->toBe(2024)
        ->and($alpha->revenue)->toBeNull();
});

it('does not combine population from different reference years', function () {
    townWith('Alpha', [[TownMeasure::Population, 2024, 100]]);
    townWith('Beta', [[TownMeasure::Population, 2020, 200]]);

    expect(app(TownAtlasQuery::class)->get()->province->population)->toBeNull();
});

it('does not present town government accounts as provincial government finances', function () {
    foreach (['Alpha', 'Beta'] as $name) {
        $town = townWith($name, []);
        signedOff($town, 'performance.revenue', 2024, '5000.00');
        signedOff($town, 'performance.expenses', 2024, '4000.00');
    }

    $province = app(TownAtlasQuery::class)->get()->province;

    expect($province->revenue)->toBeNull()->and($province->expenditure)->toBeNull();
});

it('shows the newest signed-off finances with their report link, skipping newer pending ones', function () {
    $town = townWith('Alpha', []);
    $verified = signedOff($town, 'performance.expenses', 2023, '4000.00');
    FinanceFigure::factory()->for($town)->line('performance.expenses', 2024, '4500.00')->create();
    FinanceFigure::factory()->for($town)->line('performance.revenue', 2024, '6000.00')->create(['status' => 'rejected', 'reviewed_on' => '2026-10-28']);

    $alpha = app(TownAtlasQuery::class)->get()->towns[0];

    expect($alpha->expenditure?->value)->toBe('4000.00')
        ->and($alpha->expenditure?->year)->toBe(2023)
        ->and($alpha->expenditure?->sourceUrl)->toBe($verified->document?->source_url)
        ->and($alpha->revenue)->toBeNull();
});
