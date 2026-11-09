<?php

use App\Enums\TownMeasure;
use App\Models\Barangay;
use App\Models\BusinessType;
use App\Models\Municipality;
use App\Models\TownFact;
use App\Models\Wish;
use Inertia\Testing\AssertableInertia as Assert;

test('the home page shows the towns, types, and years behind the data', function () {
    $towns = Municipality::factory()->count(3)->create();
    $connected = Municipality::factory()->connected()->create();
    $types = BusinessType::factory()->count(2)->create();
    Wish::factory()->count(5)->recycle($towns)->recycle($types)->create();

    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('public/home/index')
            ->where('overview.townCount', 4)
            ->where('overview.connectedTownCount', 1)
            ->where('overview.connectedTownCodes', [$connected->psgc_code])
            ->where('overview.businessTypeCount', 2)
            ->where('overview.wishCount', 5)
            ->where('overview.firstYear', config('cagayan.data.first_year'))
            ->where('overview.latestYear', config('cagayan.data.latest_year'))
            ->where('overview.yearCount', config('cagayan.data.latest_year') - config('cagayan.data.first_year') + 1)
            ->where('simulated', false));
});

test('the home page gives every town its public figures', function () {
    $town = Municipality::factory()->create();
    TownFact::factory()->verified()->for($town)->measure(TownMeasure::Population, 2024, 5000)->create();
    TownFact::factory()->verified()->for($town)->measure(TownMeasure::BarangayCount, 2020, 20)->create();
    TownFact::factory()->verified()->for($town)->measure(TownMeasure::IncomeClass, 2024, 2)->create();
    TownFact::factory()->verified()->for($town)->measure(TownMeasure::PovertyIncidence, 2023, 12.5)->create();

    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('overview.atlas.towns.0.code', $town->psgc_code)
            ->where('overview.atlas.towns.0.population.value', '5000.00')
            ->where('overview.atlas.towns.0.revenue', null)
            ->where('overview.atlas.towns.0.barangayCount.value', '20.00')
            ->where('overview.atlas.towns.0.incomeClass.value', '2.00')
            ->where('overview.atlas.towns.0.povertyIncidence.value', '12.50')
            ->where('overview.atlas.province.barangayCount.value', '20.00')
            ->where('overview.atlas.province.population.value', '5000.00'));
});

test('the home page gives Tuguegarao barangays their population, and no others', function () {
    $tuguegarao = Municipality::factory()->create(['psgc_code' => '0201529000']);
    $inCity = Barangay::factory()->for($tuguegarao)->withPopulation(4945)->create();
    Barangay::factory()->withPopulation(100)->create();

    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('overview.barangays', 1)
            ->where('overview.barangays.0.code', $inCity->psgc_code)
            ->where('overview.barangays.0.population.value', '4945'));
});

test('the home page works before any town is loaded', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('overview.townCount', 0)
            ->where('overview.connectedTownCodes', [])
            ->where('overview.atlas.towns', [])
            ->where('overview.atlas.province.population', null));
});

test('the home page gives the sample wish cloud its aggregates and no raw submissions', function () {
    $town = Municipality::factory()->create(['psgc_code' => '0201529000']);
    Wish::factory()->for($town)->create(['specific_business' => 'PRIVATE COMPANY SENTINEL', 'reason' => 'PRIVATE REASON SENTINEL']);

    $this->get(route('home'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('overview.wishes.source', 'Sample Data')
            ->has('overview.wishes.items', 21)
            ->where('overview.wishes.items.0', ['id' => 'types:pharmacy', 'label' => 'Pharmacy', 'count' => 18]))
        ->assertDontSee('PRIVATE COMPANY SENTINEL')->assertDontSee('PRIVATE REASON SENTINEL');
});
