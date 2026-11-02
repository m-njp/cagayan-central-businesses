<?php

use App\Enums\BusinessStatus;
use App\Enums\RecordStatus;
use App\Models\Barangay;
use App\Models\Business;
use App\Models\BusinessYear;
use App\Models\Municipality;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

// Admin-class values that must never reach a public route (P8).
const PUBLIC_OWNER = 'Sentinel Public Owner';
const PUBLIC_OWNER_PHONE = '09990001111';
const PUBLIC_TIN = '987-654-321-000';
const PUBLIC_ADDRESS = '99 Sentinel Street';

beforeEach(function () {
    config(['cagayan.simulated' => true]);
    $town = Municipality::factory()->create(['psgc_code' => config('cagayan.performance.demo_town')]);
    $this->barangay = Barangay::factory()->for($town)->create();
    DB::update("update barangays set boundary = ST_Multi(ST_GeomFromText('POLYGON((121.72 17.61, 121.73 17.61, 121.73 17.62, 121.72 17.62, 121.72 17.61))', 4326)) where id = ?", [$this->barangay->id]);
    $this->business = Business::factory()->inBarangay($this->barangay)->create([
        'business_name' => 'Open Cafe', 'business_phone' => '09170000000', 'owner_name' => PUBLIC_OWNER,
        'contact_number' => PUBLIC_OWNER_PHONE, 'tin' => PUBLIC_TIN, 'street_address' => PUBLIC_ADDRESS,
    ]);
});

it('lists and shows approved businesses with public fields and the business contact only', function () {
    $index = $this->get(route('public.businesses.index', ['town' => config('cagayan.performance.demo_town')]));
    $profile = $this->get(route('public.businesses.show', $this->business));

    $index->assertInertia(fn (Assert $page) => $page->component('public/businesses/index')
        ->where('businesses.rows.0.name', 'Open Cafe')->has('businesses.rows.0.point', 2));
    $profile->assertInertia(fn (Assert $page) => $page->component('public/businesses/show')
        ->where('business.businessPhone', '09170000000')->missing('business.admin'));
    foreach ([$index, $profile] as $response) {
        $response->assertDontSee(PUBLIC_OWNER)->assertDontSee(PUBLIC_OWNER_PHONE)->assertDontSee(PUBLIC_TIN)->assertDontSee(PUBLIC_ADDRESS);
    }
});

it('loads no businesses until a town is chosen', function () {
    $this->get(route('public.businesses.index'))
        ->assertInertia(fn (Assert $page) => $page->where('businesses', null)->where('directory.towns.0.value', config('cagayan.performance.demo_town')));
    $this->get(route('public.businesses.index', ['town' => 'nope']))->assertInertia(fn (Assert $page) => $page->where('businesses', null));
});

it('counts the renewal streak as consecutive active years ending in the latest year (M33)', function () {
    $latest = (int) config('cagayan.data.latest_year');
    // Renewed three years running: streak 3. A non-renewal in the latest year: streak 0.
    foreach ([$latest - 2 => BusinessStatus::New, $latest - 1 => BusinessStatus::Renewed, $latest => BusinessStatus::Renewed] as $year => $status) {
        BusinessYear::factory()->for($this->business)->status($status, $year)->create();
    }
    $lapsed = Business::factory()->inBarangay($this->barangay)->create();
    BusinessYear::factory()->for($lapsed)->status(BusinessStatus::Renewed, $latest - 1)->create();
    BusinessYear::factory()->for($lapsed)->status(BusinessStatus::NotRenewed, $latest)->create();

    $this->get(route('public.businesses.show', $this->business))->assertInertia(fn (Assert $page) => $page->where('business.streak', 3));
    $this->get(route('public.businesses.show', $lapsed))->assertInertia(fn (Assert $page) => $page->where('business.streak', 0));
});

it('awards the highest operating milestone reached by an active business (M34)', function () {
    $latest = (int) config('cagayan.data.latest_year');
    $this->business->update(['year_established' => $latest - 12]);
    BusinessYear::factory()->for($this->business)->status(BusinessStatus::Renewed, $latest)->create();
    $young = Business::factory()->inBarangay($this->barangay)->create(['year_established' => $latest - 3]);
    BusinessYear::factory()->for($young)->status(BusinessStatus::Renewed, $latest)->create();
    $closed = Business::factory()->inBarangay($this->barangay)->create(['year_established' => $latest - 25]);
    BusinessYear::factory()->for($closed)->status(BusinessStatus::NotRenewed, $latest)->create();

    $this->get(route('public.businesses.show', $this->business))->assertInertia(fn (Assert $page) => $page->where('business.milestone', 10));
    $this->get(route('public.businesses.show', $young))->assertInertia(fn (Assert $page) => $page->where('business.milestone', null));
    $this->get(route('public.businesses.show', $closed))->assertInertia(fn (Assert $page) => $page->where('business.milestone', null));
    $this->get(route('public.businesses.index', ['town' => config('cagayan.performance.demo_town')]))
        ->assertInertia(fn (Assert $page) => $page->where('businesses.rows', fn ($rows) => collect($rows)->pluck('milestone')->sort()->values()->all() === [null, null, 10]));
});

it('never shows drafts, submissions or returned records (P15)', function (RecordStatus $status) {
    $hidden = Business::factory()->inBarangay($this->barangay)->status($status)->create(['business_name' => 'Pending Shop']);

    $this->get(route('public.businesses.index', ['town' => config('cagayan.performance.demo_town')]))
        ->assertInertia(fn (Assert $page) => $page->has('businesses.rows', 1));
    $this->get(route('public.businesses.show', $hidden))->assertNotFound();
})->with([RecordStatus::Draft, RecordStatus::Submitted, RecordStatus::Returned]);

it('never sends a single business sales figure (P1)', function () {
    BusinessYear::factory()->for($this->business)->status(BusinessStatus::Renewed, (int) config('cagayan.data.latest_year'))->sales('7712345.67')->create();

    $this->get(route('public.businesses.index', ['town' => config('cagayan.performance.demo_town')]))->assertDontSee('7712345');
    $this->get(route('public.businesses.show', $this->business))->assertDontSee('7712345');
});

it('never sends the point of a home-based business', function () {
    $this->business->update(['is_storefront' => false]);
    $storefront = Business::factory()->inBarangay($this->barangay)->create(['business_name' => 'Zeta Storefront']);

    $this->get(route('public.businesses.show', $storefront))->assertInertia(fn (Assert $page) => $page->has('business.neighbors', 0));

    $this->get(route('public.businesses.index', ['town' => config('cagayan.performance.demo_town')]))
        ->assertInertia(fn (Assert $page) => $page->where('businesses.rows.0.point', null));
    $this->get(route('public.businesses.show', $this->business))->assertInertia(fn (Assert $page) => $page->where('business.location', null));
});

it('hides simulated records while the flag is off (D37)', function () {
    config(['cagayan.simulated' => false]);

    $this->get(route('public.businesses.index', ['town' => config('cagayan.performance.demo_town')]))
        ->assertInertia(fn (Assert $page) => $page->has('businesses.rows', 0));
    $this->get(route('public.businesses.show', $this->business))->assertNotFound();
});
