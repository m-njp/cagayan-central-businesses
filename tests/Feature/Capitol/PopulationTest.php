<?php

use App\Enums\FinanceFigureStatus;
use App\Enums\TownMeasure;
use App\Enums\UserRole;
use App\Models\Barangay;
use App\Models\Municipality;
use App\Models\PopulationFigure;
use App\Models\TownFact;
use App\Models\User;
use App\Queries\WorkspaceMapQuery;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;

const PSA_SOURCE = 'https://psa.gov.ph/statistics/population-and-housing/node/1684077888';

beforeEach(function () {
    $this->town = Municipality::factory()->create(['psgc_code' => '0201529000', 'name' => 'Tuguegarao City']);
    TownFact::factory()->verified()->for($this->town, 'municipality')->measure(TownMeasure::Population, 2024, 167297)->create(['source_url' => PSA_SOURCE]);
    $this->barangay = Barangay::factory()->create([
        'municipality_id' => $this->town->id, 'psgc_code' => '0201529001', 'name' => 'Annafunan East',
        'population' => 5000, 'population_year' => 2024, 'population_source' => PSA_SOURCE,
    ]);
    $this->route = route('capitol.population.store', ['town' => '0201529000', 'year' => 2024]);
});

function staffIn(Municipality $town): User
{
    $user = User::factory()->create(['role' => UserRole::Staff]);
    $user->assignedTowns()->attach($town->id);

    return $user;
}

/** One census save; the base is the count the grid showed. */
function censusSave(string $place, string $value, ?string $base, array $shared = []): array
{
    return ['submission' => (string) Str::uuid(), 'source_url' => PSA_SOURCE, ...$shared,
        'changes' => [['place' => $place, 'value' => $value, 'base' => $base]]];
}

it('hides population from guests and from staff of other towns', function () {
    $this->get(route('capitol.population.show', ['town' => '0201529000']))->assertNotFound();

    $outsider = staffIn(Municipality::factory()->create(['psgc_code' => '0201501000', 'name' => 'Abulug']));
    $this->actingAs($outsider)->get(route('capitol.population.show', ['town' => '0201529000', 'year' => 2024]))->assertForbidden();
    $this->actingAs($outsider)->post($this->route, censusSave('0201529001', '5100', '5000', ['reason' => 'Recount']))->assertForbidden();

    expect(PopulationFigure::query()->count())->toBe(0);
});

it('prefills the town and its barangays with the published counts', function () {
    $this->actingAs(User::factory()->create())->get(route('capitol.population.show', ['town' => '0201529000', 'year' => 2024]))
        ->assertInertia(fn (Assert $page) => $page->component('capitol/population/index')
            ->where('population.rows.0.place', 'town')
            ->where('population.rows.0.value', 167297)
            ->where('population.rows.0.status', 'verified')
            ->where('population.rows.1.place', '0201529001')
            ->where('population.rows.1.value', 5000));
});

it('shows a returned count with the reviewer note while the verified count stays published', function () {
    $admin = User::factory()->create();
    $proposal = PopulationFigure::factory()->create(['municipality_id' => $this->town->id, 'barangay_id' => $this->barangay->id, 'year' => 2024, 'value' => 5200, 'entered_by' => User::factory()->create()->id]);
    $this->actingAs($admin)->post(route('capitol.population.reviews.store', ['town' => '0201529000', 'year' => 2024]),
        ['decision' => 'rejected', 'figures' => [$proposal->public_id], 'note' => 'Does not match the PSA table'])->assertRedirect();

    $this->actingAs($admin)->get(route('capitol.population.show', ['town' => '0201529000', 'year' => 2024]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('population.rows.0.returnedNote', null)
            ->where('population.rows.1.value', 5000)
            ->where('population.rows.1.status', 'verified')
            ->where('population.rows.1.returnedNote', 'Does not match the PSA table'));
});

it('saves a correction as a pending proposal and leaves the published count in place', function () {
    $staff = staffIn($this->town);

    $this->actingAs($staff)->post($this->route, censusSave('0201529001', '5100', '5000', ['reason' => 'PSA revised the count']))
        ->assertSessionHasNoErrors()->assertRedirect();

    $proposal = PopulationFigure::query()->sole();
    expect($proposal->status)->toBe(FinanceFigureStatus::Pending)
        ->and($proposal->value)->toBe(5100)
        ->and($proposal->barangay_id)->toBe($this->barangay->id)
        ->and($this->barangay->fresh()->population)->toBe(5000);
});

it('refuses corrections without a reason, stale saves, unofficial sources and repeats', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post($this->route, censusSave('0201529001', '5100', '5000'))->assertSessionHasErrors('reason');
    $this->actingAs($user)->post($this->route, censusSave('0201529001', '5100', '4900', ['reason' => 'Typo']))->assertSessionHasErrors('changes');
    $this->actingAs($user)->post($this->route, censusSave('0201529001', '5100', '5000', ['reason' => 'Typo', 'source_url' => 'https://psa.gov.ph.example.com/x']))
        ->assertSessionHasErrors('source_url');
    $this->actingAs($user)->post($this->route, censusSave('0201529001', '5,100', '5000', ['reason' => 'Typo']))->assertSessionHasErrors('changes.0.value');
    expect(PopulationFigure::query()->count())->toBe(0);

    $save = censusSave('0201529001', '5100', '5000', ['reason' => 'Typo']);
    $this->actingAs($user)->post($this->route, $save)->assertRedirect();
    $this->actingAs($user)->post($this->route, $save)->assertRedirect();
    expect(PopulationFigure::query()->count())->toBe(1);
});

it('publishes a verified count into the map tables, never by the person who entered it', function () {
    $staff = staffIn($this->town);
    $admin = User::factory()->create();
    $this->actingAs($staff)->post($this->route, censusSave('0201529001', '5100', '5000', ['reason' => 'PSA revised the count']));
    $proposal = PopulationFigure::query()->sole();
    $review = route('capitol.population.reviews.store', ['town' => '0201529000', 'year' => 2024]);

    $this->actingAs($staff)->post($review, ['decision' => 'verified', 'figures' => [$proposal->public_id]])->assertForbidden();
    $this->actingAs($admin)->post($review, ['decision' => 'verified', 'figures' => [$proposal->public_id]])->assertRedirect();

    expect($proposal->fresh()->status)->toBe(FinanceFigureStatus::Verified)
        ->and($this->barangay->fresh()->population)->toBe(5100)
        ->and(app(WorkspaceMapQuery::class)->get(null, false)->counts['0201529001']['value'])->toBe(5100)
        // The dashboard draws the barangay from the map file but shows the database count.
        ->and(collect(app(WorkspaceMapQuery::class)->get('0201529000', true)->barangays)->firstWhere('code', '0201529001')?->population?->value)->toBe('5100');
});

it('skips a count the reviewer entered and never lets an older census replace a newer one', function () {
    $admin = User::factory()->create();
    $other = User::factory()->create();
    $own = PopulationFigure::factory()->create(['municipality_id' => $this->town->id, 'barangay_id' => $this->barangay->id, 'year' => 2024, 'value' => 6000, 'entered_by' => $admin->id]);
    $older = PopulationFigure::factory()->create(['municipality_id' => $this->town->id, 'barangay_id' => $this->barangay->id, 'year' => 2020, 'value' => 4000, 'entered_by' => $other->id]);

    $this->actingAs($admin)->post(route('capitol.population.reviews.store', ['town' => '0201529000', 'year' => 2024]), ['decision' => 'verified', 'figures' => [$own->public_id]]);
    $this->actingAs($admin)->post(route('capitol.population.reviews.store', ['town' => '0201529000', 'year' => 2020]), ['decision' => 'verified', 'figures' => [$older->public_id]]);

    expect($own->fresh()->status)->toBe(FinanceFigureStatus::Pending)
        ->and($older->fresh()->status)->toBe(FinanceFigureStatus::Verified)
        ->and($this->barangay->fresh()->population)->toBe(5000)
        ->and($this->barangay->fresh()->population_year)->toBe(2024);
});

it('publishes a verified town count into town facts and needs a reason to return', function () {
    $proposal = PopulationFigure::factory()->create(['municipality_id' => $this->town->id, 'year' => 2024, 'value' => 170000, 'entered_by' => User::factory()->create()->id]);
    $review = route('capitol.population.reviews.store', ['town' => '0201529000', 'year' => 2024]);
    $admin = User::factory()->create();

    $this->actingAs($admin)->post($review, ['decision' => 'rejected', 'figures' => [$proposal->public_id]])->assertSessionHasErrors('note');
    $this->actingAs($admin)->post($review, ['decision' => 'verified', 'figures' => [$proposal->public_id]])->assertRedirect();

    expect((int) TownFact::query()->where('municipality_id', $this->town->id)->where('measure', 'population')->where('year', 2024)->value('value'))->toBe(170000);
});

it('lists pending census counts on the Data Entry hub', function () {
    PopulationFigure::factory()->create(['municipality_id' => $this->town->id, 'barangay_id' => $this->barangay->id, 'year' => 2024, 'value' => 5200]);

    $this->actingAs(User::factory()->create())->get(route('capitol.data-entry'))->assertInertia(fn (Assert $page) => $page
        ->where('entry.datasets.2.id', 'population')
        ->where('entry.datasets.2.pending', 1)
        ->where('entry.review', fn ($rows) => collect($rows)->contains('item', 'Tuguegarao City Census 2024')));
});

it('keeps a pending count off Home and the Dashboard until it is verified', function () {
    $proposal = PopulationFigure::factory()->create([
        'municipality_id' => $this->town->id, 'barangay_id' => $this->barangay->id, 'year' => 2024, 'value' => 5123,
        'entered_by' => User::factory()->create()->id,
    ]);
    $admin = User::factory()->create();
    $dashboard = route('capitol.dashboard', ['town' => '0201529000', 'level' => 'barangays']);

    // While 5123 waits for review, both pages still show the published 5000.
    $this->get(route('home'))->assertInertia(fn (Assert $page) => $page
        ->where('overview.barangays.0.population.value', '5000'));
    $this->actingAs($admin)->get($dashboard)->assertInertia(fn (Assert $page) => $page
        ->where('map.counts.0201529001.value', 5000)
        ->where('map.barangays', fn ($rows) => collect($rows)->firstWhere('code', '0201529001')['population']['value'] === '5000'));

    $this->actingAs($admin)->post(route('capitol.population.reviews.store', ['town' => '0201529000', 'year' => 2024]),
        ['decision' => 'verified', 'figures' => [$proposal->public_id]])->assertRedirect();

    $this->get(route('home'))->assertInertia(fn (Assert $page) => $page
        ->where('overview.barangays.0.code', '0201529001')
        ->where('overview.barangays.0.population.value', '5123'));
    $this->actingAs($admin)->get($dashboard)->assertInertia(fn (Assert $page) => $page
        ->where('map.counts.0201529001.value', 5123)
        ->where('map.barangays', fn ($rows) => collect($rows)->firstWhere('code', '0201529001')['population']['value'] === '5123'));
});
