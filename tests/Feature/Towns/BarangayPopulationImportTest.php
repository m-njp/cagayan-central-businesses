<?php

use App\Models\Barangay;
use Database\Seeders\BarangaySeeder;
use Database\Seeders\MunicipalitySeeder;
use Illuminate\Support\Facades\File;

const TEST_BARANGAY_CSV = 'storage/framework/testing/barangay-population.csv';

afterEach(fn () => File::delete(base_path(TEST_BARANGAY_CSV)));

it('loads the shipped file for all 49 Tuguegarao barangays, adding up to the PSA 2024 city total', function () {
    $this->seed([MunicipalitySeeder::class, BarangaySeeder::class]);

    $this->artisan('barangays:import')->assertSuccessful();

    expect(Barangay::query()->whereNotNull('population')->count())->toBe(49)
        ->and((int) Barangay::query()->sum('population'))->toBe(167_297);
});

it('imports nothing when any row is wrong', function () {
    $barangay = Barangay::factory()->withPopulation(500)->create(['psgc_code' => '0201529001']);

    File::ensureDirectoryExists(dirname(base_path(TEST_BARANGAY_CSV)));
    File::put(base_path(TEST_BARANGAY_CSV), implode("\n", [
        'psgc_code,barangay,year,population,source_url,checked_on',
        '0201529001,Annafunan East,2020,4945,https://psa.gov.ph/a,',
        '0201529999,Nowhere,2020,10,https://psa.gov.ph/a,',
    ])."\n");

    $this->artisan('barangays:import', ['--path' => TEST_BARANGAY_CSV])
        ->expectsOutputToContain('Line 3: unknown barangay code 0201529999')
        ->assertFailed();

    expect($barangay->fresh()?->population)->toBe(500);
});
