<?php

use App\Enums\RecordStatus;
use App\Enums\UserRole;
use App\Models\Barangay;
use App\Models\Business;
use App\Models\FinanceFigure;
use App\Models\Municipality;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->town = Municipality::factory()->create(['psgc_code' => '0201529000', 'name' => 'Tuguegarao City']);
    $this->other = Municipality::factory()->create(['psgc_code' => '0201501000', 'name' => 'Abulug']);
    FinanceFigure::factory()->for($this->town)->line('performance.revenue', 2025, '100.00')->create();
    FinanceFigure::factory()->for($this->other)->line('performance.revenue', 2025, '200.00')->create();
    Business::factory()->inBarangay(Barangay::factory()->create(['municipality_id' => $this->town->id]))
        ->status(RecordStatus::Submitted)->create(['is_simulated' => false, 'business_name' => 'Kapihan sa Centro']);
});

it('hides the hub from guests', function () {
    $this->get(route('capitol.data-entry'))->assertNotFound();
});

it('counts only assigned towns for staff and gives them no review list', function () {
    $staff = User::factory()->create(['role' => UserRole::Staff]);
    $staff->assignedTowns()->attach($this->other->id);

    $this->actingAs($staff)->get(route('capitol.data-entry'))->assertInertia(fn (Assert $page) => $page
        ->component('capitol/data-entry')
        ->where('entry.datasets.0.id', 'finances')
        ->where('entry.datasets.0.pending', 1)
        ->where('entry.datasets.1.pending', 0)
        ->where('entry.review', null)
        ->missing('entry.datasets.2'));
});

it('lists finance reports and submitted businesses together for admins', function () {
    $this->actingAs(User::factory()->create())->get(route('capitol.data-entry'))->assertInertia(fn (Assert $page) => $page
        ->where('entry.datasets.0.pending', 2)
        ->where('entry.datasets.1.pending', 1)
        ->where('entry.datasets.2.id', 'categories')
        ->where('entry.review', fn ($rows) => collect($rows)->pluck('item')->sort()->values()->all() === [
            'Abulug Report 2025', 'Kapihan sa Centro', 'Tuguegarao City Report 2025',
        ])
        ->missing('entry.review.0.owner'));
});
