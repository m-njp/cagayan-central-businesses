<?php

use App\Enums\BusinessStatus;
use App\Enums\UserRole;
use App\Models\Barangay;
use App\Models\Business;
use App\Models\BusinessCategory;
use App\Models\BusinessType;
use App\Models\BusinessYear;
use App\Models\Municipality;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

// A distinctive declared sales value that must never reach any response (P1, P7).
const SALES_SENTINEL = '7654321.98';

beforeEach(function () {
    config(['cagayan.simulated' => true]);
    $town = Municipality::factory()->create(['psgc_code' => '0201529000', 'name' => 'Tuguegarao City']);
    $barangay = Barangay::factory()->for($town)->create();
    // Categories cross industries (D39): each type here sits in its own category.
    $retail = BusinessType::factory()->for(BusinessCategory::factory()->create(['code' => 'retail', 'name' => 'Retail']), 'category')->create(['psic_section' => 'G', 'psic_section_name' => 'Wholesale and retail trade']);
    $farm = BusinessType::factory()->for(BusinessCategory::factory()->create(['code' => 'agriculture', 'name' => 'Agriculture']), 'category')->create(['psic_section' => 'A', 'psic_section_name' => 'Agriculture, forestry and fishing']);
    $salon = BusinessType::factory()->for(BusinessCategory::factory()->create(['code' => 'services', 'name' => 'Services']), 'category')->create(['psic_section' => 'S', 'psic_section_name' => 'Other service activities']);

    $min = config('cagayan.privacy.small_group_min');
    // Retail: enough businesses, so its total is shown. Services: shown on its own but smaller. Farms: one below the threshold.
    foreach (range(1, $min + 1) as $index) {
        $business = Business::factory()->inBarangay($barangay)->ofType($retail)->create();
        BusinessYear::factory()->for($business)->status(BusinessStatus::Renewed, 2024)->sales($index === 1 ? SALES_SENTINEL : '5000000.00')->create();
    }
    foreach (range(1, $min + 1) as $index) {
        $business = Business::factory()->inBarangay($barangay)->ofType($salon)->create();
        BusinessYear::factory()->for($business)->status(BusinessStatus::Renewed, 2024)->sales('1000000.00')->create();
    }
    foreach (range(1, $min - 1) as $index) {
        $business = Business::factory()->inBarangay($barangay)->ofType($farm)->create();
        BusinessYear::factory()->for($business)->status(BusinessStatus::Renewed, 2024)->sales('3000000.00')->create();
    }
    $this->artisan('metrics:refresh')->assertSuccessful();
});

it('sends business counts and no sales figure to the public or the Capitol (P1, P7)', function () {
    $countsOnly = fn ($metrics) => collect($metrics)->pluck('id')->all() === ['active', 'new', 'closed'];

    $this->get(route('public.economic-performance', ['town' => '0201529000']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('performance.catalog.businesses.datasets.industry:G.metrics', $countsOnly)
            ->where('performance.catalog.businesses.datasets.industry:G.rows.0.observations.active.value', (string) (config('cagayan.privacy.small_group_min') + 1)))
        ->assertDontSee(SALES_SENTINEL);
    $this->actingAs(User::factory()->create())->get(route('capitol.analytics'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('catalog.businesses.datasets.category:agriculture.metrics', $countsOnly)
            ->where('catalog.businesses.groups.category.0.label', 'Agriculture'))
        ->assertDontSee(SALES_SENTINEL);
});

it('keeps community wishes out of the Capitol analytics response', function () {
    $this->actingAs(User::factory()->create(['role' => UserRole::Staff]))->get(route('capitol.analytics'))
        ->assertInertia(fn (Assert $page) => $page->missing('wishes')->missing('catalog.wishes'));
});

it('shows a year with no records as not available, never zero and never bridged', function () {
    // Retail trades in 2023 and 2025 but not 2024; the beforeEach groups supply 2024 for the town.
    $retail = BusinessType::query()->where('psic_section', 'G')->sole();
    BusinessYear::query()->whereIn('business_id', Business::query()->where('business_type_id', $retail->id)->select('id'))->delete();
    Business::query()->where('business_type_id', $retail->id)->each(function (Business $business) {
        BusinessYear::factory()->for($business)->status(BusinessStatus::New, 2023)->sales('5000000.00')->create();
        BusinessYear::factory()->for($business)->status(BusinessStatus::Renewed, 2025)->sales('5000000.00')->create();
    });
    $this->artisan('metrics:refresh')->assertSuccessful();

    $this->actingAs(User::factory()->create())->get(route('capitol.analytics'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('catalog.businesses.datasets.industry:G.rows.1.year', 2024)
            ->where('catalog.businesses.datasets.industry:G.rows.1.observations.active.state', 'unavailable')
            ->where('catalog.businesses.datasets.industry:G.rows.1.observations.active.value', null)
            ->where('catalog.businesses.datasets.industry:G.rows.2.observations.active.change', null));
});

it('shows no business figures to anyone while simulated records are off (P13)', function () {
    config(['cagayan.simulated' => false]);
    $this->artisan('metrics:refresh')->assertSuccessful();

    $this->get(route('public.economic-performance'))
        ->assertInertia(fn (Assert $page) => $page->where('performance.catalog.businesses', null))
        ->assertDontSee(SALES_SENTINEL);
    $this->actingAs(User::factory()->create())->get(route('capitol.analytics'))
        ->assertInertia(fn (Assert $page) => $page->where('catalog.businesses', null)->where('simulated', false));
});
