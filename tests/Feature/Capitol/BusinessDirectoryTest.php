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
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

// Admin-class values that must never reach the directory (P8).
const OWNER_SENTINEL = 'Sentinel Owner Zed';
const CONTACT_SENTINEL = '09998887777';
const DIRECTORY_SALES = '8765432.10';

beforeEach(function () {
    config(['cagayan.simulated' => true]);
    $this->barangay = Barangay::factory()->for(Municipality::factory()->create())->create(['name' => 'Centro 1']);
    $this->tourism = BusinessCategory::factory()->create(['code' => 'tourism', 'name' => 'Tourism', 'sort_order' => 1]);
    $this->lodging = BusinessCategory::factory()->create(['code' => 'lodging', 'name' => 'Lodging and Rentals', 'sort_order' => 2]);
    $this->hotel = BusinessType::factory()->for($this->tourism, 'category')->create(['code' => 'hotel', 'name' => 'Hotel or Inn', 'psic_section' => 'I', 'psic_section_name' => 'Accommodation and food service activities']);
    $this->boarding = BusinessType::factory()->for($this->lodging, 'category')->create(['code' => 'boarding-house', 'name' => 'Boarding House', 'psic_section' => 'I', 'psic_section_name' => 'Accommodation and food service activities']);
});

function directoryBusiness(string $name, BusinessType $type, array $years = [2024 => BusinessStatus::Renewed], array $attributes = []): Business
{
    $business = Business::factory()->inBarangay(test()->barangay)->ofType($type)->create(['business_name' => $name] + $attributes);
    foreach ($years as $year => $status) {
        $row = BusinessYear::factory()->for($business)->status($status, $year);
        ($status->isActive() ? $row->sales(DIRECTORY_SALES) : $row)->create();
    }

    return $business;
}

it('lists registered names under their type and category with public fields only', function () {
    directoryBusiness('Zeta Inn', $this->hotel, attributes: ['owner_name' => OWNER_SENTINEL, 'contact_number' => CONTACT_SENTINEL, 'year_established' => 2015]);
    directoryBusiness('Alpha Boarding', $this->boarding, [2023 => BusinessStatus::Renewed, 2024 => BusinessStatus::Closed]);

    $response = $this->actingAs(User::factory()->create())->get(route('capitol.businesses.index'));

    $response->assertInertia(fn (Assert $page) => $page->component('capitol/businesses')
        ->where('directory.rows.0.name', 'Alpha Boarding')
        ->where('directory.rows.0.type', 'Boarding House')
        ->where('directory.rows.0.category', 'lodging')
        ->where('directory.rows.0.barangay', 'Centro 1')
        ->where('directory.rows.0.status', 'closed')
        ->where('directory.rows.1.name', 'Zeta Inn')
        ->where('directory.rows.1.category', 'tourism')
        ->where('directory.rows.1.yearEstablished', 2015)
        ->where('directory.rows.1.status', 'renewed')
        ->missing('directory.rows.0.owner'));
    $response->assertDontSee(OWNER_SENTINEL)->assertDontSee(CONTACT_SENTINEL)->assertDontSee('8765432');
});

it('filters by category, type, industry, status and name', function () {
    directoryBusiness('Grand Inn', $this->hotel);
    directoryBusiness('Sunrise Boarding', $this->boarding);
    directoryBusiness('Old Boarding', $this->boarding, [2024 => BusinessStatus::NotRenewed]);
    $admin = User::factory()->create();
    $names = function (array $query) use ($admin): array {
        $rows = [];
        $this->actingAs($admin)->get(route('capitol.businesses.index', $query))->assertInertia(function (Assert $page) use (&$rows) {
            $rows = collect($page->toArray()['props']['directory']['rows'])->pluck('name')->all();
        });

        return $rows;
    };

    expect($names(['category' => 'lodging']))->toBe(['Old Boarding', 'Sunrise Boarding'])
        ->and($names(['type' => 'hotel']))->toBe(['Grand Inn'])
        ->and($names(['industry' => 'I']))->toHaveCount(3)
        ->and($names(['status' => 'not_renewed']))->toBe(['Old Boarding'])
        ->and($names(['q' => 'sun']))->toBe(['Sunrise Boarding'])
        ->and($names(['q' => '%']))->toBe([])
        ->and($names(['category' => 'tourism', 'status' => 'closed']))->toBe([]);
});

it('sorts by type and year established and pages 25 at a time', function () {
    directoryBusiness('B One', $this->hotel, attributes: ['year_established' => 2001]);
    directoryBusiness('A Two', $this->boarding, attributes: ['year_established' => 2010]);
    foreach (range(1, 25) as $number) {
        directoryBusiness("Filler {$number}", $this->hotel, attributes: ['year_established' => 1990]);
    }
    $admin = User::factory()->create();

    $this->actingAs($admin)->get(route('capitol.businesses.index', ['sort' => 'established', 'dir' => 'desc']))
        ->assertInertia(fn (Assert $page) => $page->where('directory.rows.0.name', 'A Two')
            ->where('directory.pagination.total', 27)->where('directory.pagination.lastPage', 2)
            ->has('directory.rows', 25));
    $this->actingAs($admin)->get(route('capitol.businesses.index', ['sort' => 'type', 'page' => 2]))
        ->assertInertia(fn (Assert $page) => $page->has('directory.rows', 2)->where('directory.pagination.page', 2));
});

it('runs the same number of queries however many businesses there are', function () {
    $admin = User::factory()->create();
    directoryBusiness('First', $this->hotel);
    DB::flushQueryLog();
    DB::enableQueryLog();
    $this->actingAs($admin)->get(route('capitol.businesses.index'))->assertOk();
    $few = count(DB::getQueryLog());

    foreach (range(1, 20) as $number) {
        directoryBusiness("More {$number}", $number % 2 ? $this->hotel : $this->boarding);
    }
    DB::flushQueryLog();
    $this->actingAs($admin)->get(route('capitol.businesses.index'))->assertOk();

    expect(count(DB::getQueryLog()))->toBe($few);
});

it('lists only registry records while simulated records are off (D37, D40)', function () {
    directoryBusiness('Hidden Inn', $this->hotel);
    directoryBusiness('Real Inn', $this->hotel, attributes: ['is_simulated' => false]);
    config(['cagayan.simulated' => false]);

    $this->actingAs(User::factory()->create())->get(route('capitol.businesses.index'))
        ->assertInertia(fn (Assert $page) => $page->has('directory.rows', 1)->where('directory.rows.0.name', 'Real Inn'));
});

it('opens a review tab with the records waiting in it', function () {
    directoryBusiness('Approved Inn', $this->hotel);
    directoryBusiness('Waiting Inn', $this->hotel, attributes: ['record_status' => 'submitted']);

    $this->actingAs(User::factory()->create())->get(route('capitol.businesses.index', ['record' => 'submitted']))
        ->assertInertia(fn (Assert $page) => $page->has('directory.rows', 1)->where('directory.rows.0.name', 'Waiting Inn')
            ->where('directory.rows.0.recordStatus', 'submitted')->where('directory.rows.0.permitStatus', 'none')
            ->where('directory.recordCounts.submitted', 1));
});

it('opens for staff and admins and stays hidden from guests', function () {
    $this->actingAs(User::factory()->create(['role' => UserRole::Staff]))->get(route('capitol.businesses.index'))->assertOk();
    $this->actingAs(User::factory()->create())->get(route('capitol.businesses.index'))->assertOk();
    auth()->logout();

    $this->get(route('capitol.businesses.index'))->assertNotFound();
});

it('rejects an unknown filter value', function () {
    $this->actingAs(User::factory()->create())->get(route('capitol.businesses.index', ['category' => 'mining', 'sort' => 'owner']))
        ->assertSessionHasErrors(['category', 'sort']);
});
