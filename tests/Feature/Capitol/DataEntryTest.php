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
        ->where('entry.datasets.1.actionHref', route('capitol.businesses.create', absolute: false))
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
        ->where('entry.datasets.1.actionHref', null)
        ->where('entry.datasets.3.id', 'categories')
        ->where('entry.datasets.3.pending', null)
        ->where('entry.review', fn ($rows) => collect($rows)->pluck('item')->sort()->values()->all() === [
            'Abulug Report 2025', 'Kapihan sa Centro', 'Tuguegarao City Report 2025',
        ]))
        ->assertDontSee('Sentinel Owner Reyes')->assertDontSee('09171234567');
    expect(DB::table('record_view_logs')->count())->toBe(0);
});

it('paginates the oldest review items with links to their source records', function () {
    $population = PopulationFigure::factory()->for($this->town)
        ->create(['year' => 2024, 'updated_at' => '2026-09-01 00:00:00']);
    FinanceFigure::query()->where('municipality_id', $this->other->id)->update(['updated_at' => '2026-09-02 00:00:00']);
    foreach (range(2017, 2024) as $year) {
        FinanceFigure::factory()->for($this->town)->line('performance.revenue', $year, '100.00')
            ->create(['updated_at' => '2026-09-03 00:00:00']);
    }
    $this->actingAs(User::factory()->create(['role' => UserRole::SuperAdmin]));

    $this->get(route('capitol.data-entry'))->assertInertia(fn (Assert $page) => $page
        ->has('entry.review', 10)
        ->where('entry.review.0.href', route('capitol.population.show', ['town' => $this->town->psgc_code, 'year' => $population->year], absolute: false))
        ->where('entry.review.1.href', route('capitol.finances.show', ['town' => $this->other->psgc_code, 'year' => 2025], absolute: false))
        ->where('entry.review.2.item', 'Tuguegarao City Report 2017')
        ->where('entry.review.9.item', 'Tuguegarao City Report 2024')
        ->where('entry.pagination.total', 12)
        ->where('entry.pagination.page', 1));

    $this->get(route('capitol.data-entry', ['page' => 2]))->assertInertia(fn (Assert $page) => $page
        ->has('entry.review', 2)
        ->where('entry.pagination.page', 2));

    $this->get(route('capitol.data-entry', ['page' => 99, 'workflow' => 'population']))
        ->assertRedirect(route('capitol.data-entry', ['page' => 2, 'workflow' => 'population']));
});
