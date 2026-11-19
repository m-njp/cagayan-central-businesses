<?php

use App\Enums\TownMeasure;
use App\Models\FinanceFigure;
use App\Models\FinanceLine;
use App\Models\Municipality;
use App\Models\TownFact;
use Illuminate\Support\Facades\DB;

function signed(Municipality $town, string $line, int $year, string $value, array $extra = []): void
{
    FinanceFigure::factory()->verified()->for($town)->line($line, $year, $value)->create($extra);
}

function series(?Municipality $town, string $metric): array
{
    return DB::table('stats_finance_year')
        ->where('metric', $metric)
        ->when($town, fn ($query) => $query->where('municipality_id', $town->id), fn ($query) => $query->whereNull('municipality_id'))
        ->orderBy('fiscal_year')->get()->keyBy('fiscal_year')->all();
}

beforeEach(fn () => $this->town = Municipality::factory()->create());

it('computes surplus and changes only between consecutive comparable years (M19, M26)', function () {
    signed($this->town, 'performance.revenue', 2022, '80.00');
    signed($this->town, 'performance.revenue', 2023, '100.00');
    signed($this->town, 'performance.revenue', 2024, '150.00');
    signed($this->town, 'performance.expenses', 2024, '120.00');
    signed($this->town, 'performance.revenue', 2025, '160.00', ['definition_variant' => 'includes_subsidy_from_other_funds']);

    $this->artisan('metrics:refresh')->assertSuccessful();
    $revenue = series($this->town, 'performance.revenue');

    // 2024: 150 - 100 = 50, and 50 / 100 x 100 = 50.0 percent. 2025 changes definition, so no change.
    expect([$revenue[2024]->change, $revenue[2024]->percent_change])->toBe(['50.00', '50.0'])
        ->and([$revenue[2025]->comparable, $revenue[2025]->change])->toBe([false, null])
        ->and(series($this->town, 'performance.surplus')[2024]->value)->toBe('30.00');
});

it('stores Financial Condition and allotments as reported, with Net Income as Income minus Expenses (M19, M35)', function () {
    foreach (['position.assets' => 'position', 'budget.allotments_continuing' => 'budget'] as $code => $group) {
        FinanceLine::factory()->create(['code' => $code, 'group' => $group]);
    }
    signed($this->town, 'position.assets', 2024, '4909278026.68');
    signed($this->town, 'position.assets', 2025, '5157038108.96');
    signed($this->town, 'budget.allotments_continuing', 2025, '569397448.57');
    signed($this->town, 'performance.revenue', 2025, '1679019473.60');
    signed($this->town, 'performance.expenses', 2025, '1403269517.37');

    $this->artisan('metrics:refresh')->assertSuccessful();

    // Tuguegarao 2025: 5,157,038,108.96 - 4,909,278,026.68 = 247,760,082.28, as the report's change column prints.
    // Net Income: 1,679,019,473.60 - 1,403,269,517.37 = 275,749,956.23, as the report prints.
    expect(series($this->town, 'position.assets')[2025]->change)->toBe('247760082.28')
        ->and(series($this->town, 'budget.allotments_continuing')[2025]->value)->toBe('569397448.57')
        ->and(series($this->town, 'performance.surplus')[2025]->value)->toBe('275749956.23');
});

it('leaves a gap year without change and a zero base without percent (M26)', function () {
    signed($this->town, 'performance.expenses', 2021, '0.00');
    signed($this->town, 'performance.expenses', 2022, '40.00');
    signed($this->town, 'performance.expenses', 2024, '50.00');

    $this->artisan('metrics:refresh')->assertSuccessful();
    $expenses = series($this->town, 'performance.expenses');

    expect([$expenses[2022]->change, $expenses[2022]->percent_change])->toBe(['40.00', null])
        ->and([$expenses[2024]->comparable, $expenses[2024]->change])->toBe([false, null]);
});

it('reads a deeper deficit as a fall and a smaller one as a gain, over the size of the prior value (M26)', function () {
    // Surplus 2022 = -10, 2023 = -20, 2024 = -5, 2025 = +10.
    foreach ([2022 => [90, 100], 2023 => [80, 100], 2024 => [95, 100], 2025 => [110, 100]] as $year => [$revenue, $expenses]) {
        signed($this->town, 'performance.revenue', $year, "{$revenue}.00");
        signed($this->town, 'performance.expenses', $year, "{$expenses}.00");
    }

    $this->artisan('metrics:refresh')->assertSuccessful();
    $surplus = series($this->town, 'performance.surplus');

    // -10 to -20: change -10, and -10 / |-10| x 100 = -100.0, a fall. -20 to -5: +15 / 20 = +75.0, a gain.
    // -5 to +10: +15 / 5 = +300.0.
    expect([$surplus[2023]->change, $surplus[2023]->percent_change])->toBe(['-10.00', '-100.0'])
        ->and([$surplus[2024]->change, $surplus[2024]->percent_change])->toBe(['15.00', '75.0'])
        ->and([$surplus[2025]->change, $surplus[2025]->percent_change])->toBe(['15.00', '300.0']);
});

it('derives only Net Income; the retired shares, rates and per resident figures are never computed', function () {
    TownFact::factory()->verified()->for($this->town)->measure(TownMeasure::Population, 2024, 2000)->create();
    signed($this->town, 'performance.revenue', 2024, '1000.00');
    signed($this->town, 'performance.expenses', 2024, '800.00');

    $this->artisan('metrics:refresh')->assertSuccessful();

    expect(series($this->town, 'performance.surplus')[2024]->value)->toBe('200.00');
    foreach (['revenue.remaining', 'revenue.local_share', 'budget.utilization', 'per_capita.revenue', 'per_capita.expenses'] as $retired) {
        expect(series($this->town, $retired))->toBe([]);
    }
});

it('sums reporting towns and computes change over towns present in both years (M27)', function () {
    $other = Municipality::factory()->create();
    signed($this->town, 'performance.revenue', 2023, '100.00');
    signed($this->town, 'performance.revenue', 2024, '120.00');
    signed($other, 'performance.revenue', 2024, '500.00');

    $this->artisan('metrics:refresh')->assertSuccessful();
    $sum = series(null, 'performance.revenue')[2024];

    // 120 + 500 = 620 from 2 towns; only the first has 2023, so change is 20 over a base of 100 = 20.0 percent.
    expect([$sum->value, $sum->reporting_towns, $sum->matched_towns, $sum->change, $sum->percent_change])
        ->toBe(['620.00', 2, 1, '20.00', '20.0'])
        ->and($sum->total_towns)->toBe(Municipality::query()->count());
});

it('ignores figures that are not signed off', function () {
    FinanceFigure::factory()->for($this->town)->line('performance.revenue', 2024, '999.00')->create();

    $this->artisan('metrics:refresh')->assertSuccessful();

    expect(DB::table('stats_finance_year')->count())->toBe(0);
});
