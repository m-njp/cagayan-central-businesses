<?php

use App\Enums\RecordStatus;
use App\Enums\UserRole;
use App\Models\Barangay;
use App\Models\Business;
use App\Models\FinanceFigure;
use App\Models\Municipality;
use App\Models\PopulationFigure;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->town = Municipality::factory()->create(['psgc_code' => '0201529000', 'name' => 'Tuguegarao City']);
    $this->other = Municipality::factory()->create(['psgc_code' => '0201501000', 'name' => 'Abulug']);
    FinanceFigure::factory()->for($this->town)->line('performance.revenue', 2025, '100.00')->create();
    FinanceFigure::factory()->for($this->other)->line('performance.revenue', 2025, '200.00')->create();
    Business::factory()->inBarangay(Barangay::factory()->create(['municipality_id' => $this->town->id]))
        ->status(RecordStatus::Submitted)->create(['is_simulated' => false, 'business_name' => 'Kapihan sa Centro',
            'owner_name' => 'Sentinel Owner Reyes', 'contact_number' => '09171234567']);
});

it('hides the hub from guests', function () {
    $this->get(route('capitol.data-entry'))->assertNotFound();
});

it('counts only assigned towns for staff and gives them no review list', function () {
    $staff = User::factory()->create(['role' => UserRole::Staff]);
    $staff->assignedTowns()->attach($this->other->id);
    // A census count waiting in a town this Staff member does not cover.
    PopulationFigure::factory()->create(['municipality_id' => $this->town->id, 'year' => 2024]);

    $this->actingAs($staff)->get(route('capitol.data-entry'))->assertInertia(fn (Assert $page) => $page
        ->component('capitol/data-entry/index')
        ->where('entry.datasets.0.id', 'finances')
        ->where('entry.datasets.0.pending', 1)
        ->where('entry.datasets.1.pending', 0)
        ->where('entry.review', null)
        ->where('entry.datasets.2.id', 'population')
        ->where('entry.datasets.2.pending', 0)
        ->missing('entry.datasets.3'));
});

it('keeps owner details out of the dashboard review table and out of Staff responses', function () {
    $this->actingAs(User::factory()->create())->get(route('capitol.dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('waiting', fn ($rows) => collect($rows)->pluck('item')->contains('Kapihan sa Centro')))
        ->assertDontSee('Sentinel Owner Reyes')->assertDontSee('09171234567');

    // A Staff member whose own town holds the submitted business still gets no review list.
    $staff = User::factory()->create(['role' => UserRole::Staff]);
    $staff->assignedTowns()->attach($this->town->id);
    $this->actingAs($staff)->get(route('capitol.data-entry'))->assertInertia(fn (Assert $page) => $page->where('entry.review', null))
        ->assertDontSee('Sentinel Owner Reyes')->assertDontSee('09171234567');
});

it('sends full Philippine time instants so the page can show the hour', function () {
    // 03:30 UTC is 11:30 in Manila on the same day.
    FinanceFigure::query()->update(['updated_at' => '2026-11-08 03:30:00']);

    $this->actingAs(User::factory()->create())->get(route('capitol.data-entry'))->assertInertia(fn (Assert $page) => $page
        ->where('entry.datasets.0.lastChange', '2026-11-08T11:30:00+08:00')
        ->where('entry.review.0.since', fn ($since) => str_ends_with($since, '+08:00') && str_contains($since, 'T')));
});

it('lists finance reports and submitted businesses together for admins', function () {
    $this->actingAs(User::factory()->create())->get(route('capitol.data-entry'))->assertInertia(fn (Assert $page) => $page
        ->where('entry.datasets.0.pending', 2)
        ->where('entry.datasets.1.pending', 1)
        ->where('entry.datasets.3.id', 'categories')
        ->where('entry.review', fn ($rows) => collect($rows)->pluck('item')->sort()->values()->all() === [
            'Abulug Report 2025', 'Kapihan sa Centro', 'Tuguegarao City Report 2025',
        ]))
        ->assertDontSee('Sentinel Owner Reyes')->assertDontSee('09171234567');
    expect(DB::table('record_view_logs')->count())->toBe(0);
});
