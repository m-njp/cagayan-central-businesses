<?php

use App\Enums\TownMeasure;
use App\Models\Municipality;
use App\Models\TownFact;
use Database\Seeders\MunicipalitySeeder;
use Illuminate\Support\Facades\File;

const TEST_TOWN_CSV = 'storage/framework/testing/town-facts.csv';

/**
 * @param  list<string>  $rows
 */
function writeTownCsv(array $rows): void
{
    File::ensureDirectoryExists(dirname(base_path(TEST_TOWN_CSV)));
    File::put(base_path(TEST_TOWN_CSV), implode("\n", ['psgc_code,town,measure,year,value,source_url,checked_on', ...$rows])."\n");
}

afterEach(fn () => File::delete(base_path(TEST_TOWN_CSV)));

it('replaces every town figure with the rows in the file', function () {
    $town = Municipality::factory()->create(['psgc_code' => '0201501000']);
    TownFact::factory()->for($town)->measure(TownMeasure::BarangayCount, 2019, 1)->create();

    writeTownCsv([
        '0201501000,Abulug,population,2024,35347,https://psa.gov.ph/a,',
        '0201501000,Abulug,poverty_incidence,2023,8.94,https://psa.gov.ph/b,2026-10-20',
    ]);

    $this->artisan('towns:import', ['--path' => TEST_TOWN_CSV])->assertSuccessful();

    $facts = TownFact::query()->orderBy('year')->get();

    expect($facts)->toHaveCount(2)
        ->and($facts[0]->measure)->toBe(TownMeasure::PovertyIncidence)
        ->and($facts[0]->value)->toBe('8.94')
        ->and($facts[0]->checked_on?->toDateString())->toBe('2026-10-20')
        ->and($facts[1]->measure)->toBe(TownMeasure::Population)
        ->and($facts[1]->value)->toBe('35347.00')
        ->and($facts[1]->checked_on)->toBeNull();
});

it('imports nothing when any row is wrong', function (string $row, string $problem) {
    $town = Municipality::factory()->create(['psgc_code' => '0201501000']);
    TownFact::factory()->for($town)->create();

    writeTownCsv(['0201501000,Abulug,population,2024,35347,https://psa.gov.ph/a,', $row]);

    $this->artisan('towns:import', ['--path' => TEST_TOWN_CSV])
        ->expectsOutputToContain("Line 3: {$problem}")
        ->assertFailed();

    expect(TownFact::query()->count())->toBe(1);
})->with([
    'unknown town' => ['0299999000,Nowhere,population,2024,10,https://psa.gov.ph/a,', 'unknown town code 0299999000'],
    'unknown measure' => ['0201501000,Abulug,households,2024,10,https://psa.gov.ph/a,', 'unknown measure households'],
    'value with a comma' => ['0201501000,Abulug,barangay_count,2020,"1,000",https://psa.gov.ph/a,', 'value 1,000 is not a number'],
    'no source' => ['0201501000,Abulug,barangay_count,2020,1000,,', 'source_url is missing or not https'],
    'finances moved to the ledger' => ['0201501000,Abulug,revenue,2024,1000,https://psa.gov.ph/a,', 'unknown measure revenue'],
    'repeated row' => ['0201501000,Abulug,population,2024,1,https://psa.gov.ph/a,', 'repeats line 2'],
]);

it('loads the shipped file for all 29 towns, adding up to the PSA province totals', function () {
    $this->seed(MunicipalitySeeder::class);

    $this->artisan('towns:import')->assertSuccessful();

    $population = fn (int $year) => (int) TownFact::query()
        ->where('measure', TownMeasure::Population)
        ->where('year', $year)
        ->sum('value');

    expect(TownFact::query()->distinct()->count('municipality_id'))->toBe(29)
        ->and($population(2024))->toBe(1_284_676)
        ->and($population(2020))->toBe(1_268_603);
});
