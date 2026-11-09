<?php

use Database\Seeders\BarangaySeeder;
use Database\Seeders\BusinessTypeSeeder;
use Database\Seeders\MunicipalitySeeder;
use Database\Seeders\SimulatedBusinessSeeder;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->seed([MunicipalitySeeder::class, BarangaySeeder::class, BusinessTypeSeeder::class]);
    $this->artisan('barangays:import')->assertSuccessful();
    $this->seed(SimulatedBusinessSeeder::class);
    $this->artisan('metrics:refresh')->assertSuccessful();
    $this->latest = (int) config('cagayan.data.latest_year');
});

it('keeps every year consistent with the one before it (status rules)', function () {
    $years = DB::table('business_years')->groupBy('year')->orderBy('year')->selectRaw("year,
        count(*) filter (where status in ('new', 'renewed')) as active,
        count(*) filter (where status = 'new') as new, count(*) filter (where status = 'renewed') as renewed,
        count(*) filter (where status = 'not_renewed') as not_renewed, count(*) filter (where status = 'closed') as closed")
        ->get()->keyBy('year');

    foreach ($years as $year => $row) {
        $previous = $years->get($year - 1);
        if ($previous === null) {
            continue;
        }
        expect($row->active)->toBe($previous->active + $row->new - $row->not_renewed - $row->closed)
            ->and($row->renewed + $row->not_renewed + $row->closed)->toBe($previous->active);
    }
});

it('has no rows after a business stops renewing', function () {
    expect(DB::table('business_years as later')
        ->join('business_years as ended', fn ($join) => $join->on('ended.business_id', '=', 'later.business_id')->whereColumn('later.year', '>', 'ended.year'))
        ->whereIn('ended.status', ['not_renewed', 'closed'])->count())->toBe(0);
});

it('includes a barangay below the small-group threshold (seeder requirement 5)', function () {
    $min = config('cagayan.privacy.small_group_min');

    expect(DB::table('stats_area_year')->where('year', $this->latest)->groupBy('barangay_id')
        ->havingRaw('sum(active_count) between 1 and ?', [$min - 1])->selectRaw('barangay_id')->count())->toBeGreaterThan(0);
});

it('includes a barangay where one business dominates its sales (seeder requirement 6)', function () {
    $min = config('cagayan.privacy.small_group_min');
    $share = config('cagayan.privacy.dominance_max_share');

    expect(DB::table('stats_area_year')->where('year', $this->latest)->groupBy('barangay_id')
        ->havingRaw('sum(active_count) >= ? and max(declared_sales_max_single) > ? * sum(declared_sales_total)', [$min, $share])
        ->selectRaw('barangay_id')->count())->toBeGreaterThan(0);
});

it('puts more businesses in populous barangays and every point inside its barangay (requirements 4 and 9)', function () {
    $counts = DB::table('barangays as g')->leftJoin('businesses as b', 'b.barangay_id', '=', 'g.id')
        ->groupBy('g.id', 'g.population')->orderBy('g.population')->selectRaw('count(b.id) as businesses')->pluck('businesses');

    expect($counts->take(10)->avg())->toBeLessThan($counts->take(-10)->avg())
        ->and(DB::table('businesses as b')->join('barangays as g', 'g.id', '=', 'b.barangay_id')
            ->whereRaw('not ST_Contains(g.boundary, b.location)')->count())->toBe(0);
});

it('grows new food businesses near schools and online businesses every year (presentation trends)', function () {
    $trends = config('cagayan.simulation.trends');
    $newBy = fn (array $types, ?array $barangays) => DB::table('business_years as y')
        ->join('businesses as b', 'b.id', '=', 'y.business_id')->join('business_types as t', 't.id', '=', 'b.business_type_id')
        ->join('barangays as g', 'g.id', '=', 'b.barangay_id')->where('y.status', 'new')->whereIn('t.code', $types)
        ->when($barangays, fn ($query) => $query->whereIn('g.name', $barangays))
        ->groupBy('y.year')->orderBy('y.year')->selectRaw('y.year, count(*) as new')->pluck('new', 'year');

    $food = $newBy($trends['food_types'], $trends['school_barangays']);
    $online = $newBy($trends['online_types'], null);

    expect($food->values()->all())->toBe($food->values()->sort()->values()->all())
        ->and($food[$this->latest])->toBeGreaterThan(2 * $food[(int) config('cagayan.data.first_year')])
        ->and($online->values()->all())->toBe($online->values()->sort()->values()->all())
        ->and($online[$this->latest])->toBeGreaterThan(3 * $online[$trends['online_first_year']]);
});

it('includes a barangay whose non-renewals rise two years in a row (seeder requirement 7)', function () {
    $trends = config('cagayan.simulation.trends');

    $nonRenewals = DB::table('stats_area_year as s')->join('barangays as g', 'g.id', '=', 's.barangay_id')
        ->where('g.name', $trends['declining_barangay'])->groupBy('s.year')->orderBy('s.year')
        ->selectRaw('s.year, sum(s.not_renewed_count) as total')->pluck('total', 'year');

    expect((int) $nonRenewals[$this->latest - 2])->toBeGreaterThanOrEqual(3)
        ->and($nonRenewals[$this->latest - 1])->toBeGreaterThan($nonRenewals[$this->latest - 2])
        ->and($nonRenewals[$this->latest])->toBeGreaterThan($nonRenewals[$this->latest - 1]);
});

it('seeds registry records in every review state, with permits and private documents only for the right ones', function () {
    $pending = config('cagayan.simulation.trends.pending');

    expect(DB::table('businesses')->where('record_status', 'submitted')->count())->toBe($pending['submitted'])
        ->and(DB::table('businesses')->where('record_status', 'returned')->whereNotNull('review_note')->count())->toBe($pending['returned'])
        ->and(DB::table('businesses')->where('record_status', 'draft')->count())->toBe($pending['draft'])
        ->and(DB::table('business_years as y')->join('businesses as b', 'b.id', '=', 'y.business_id')->where('b.record_status', '<>', 'approved')->count())->toBe(0)
        ->and(DB::table('business_permits')->count())->toBe(DB::table('business_years')->whereIn('status', ['new', 'renewed'])->count())
        ->and(DB::table('businesses')->whereNotNull('bir_verified_on')->where('record_status', '<>', 'approved')->count())->toBe(0)
        ->and(Storage::disk('local')->exists(DB::table('business_documents')->value('path')))->toBeTrue();
});

it('keeps each barangay within its cap with names unique inside it', function () {
    $perBarangay = DB::table('businesses')->groupBy('barangay_id')->selectRaw('count(*) as total, count(distinct business_name) as names')->get();

    expect($perBarangay->max('total'))->toBeLessThanOrEqual(config('cagayan.simulation.max_per_barangay'))
        ->and($perBarangay->every(fn ($row) => $row->total === $row->names))->toBeTrue();
});
