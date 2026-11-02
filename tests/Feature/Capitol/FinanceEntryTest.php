<?php

use App\Enums\FinanceFigureStatus;
use App\Models\FinanceFigure;
use App\Models\FinanceLine;
use App\Models\Municipality;
use App\Models\User;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->town = Municipality::factory()->create(['psgc_code' => '0201529000', 'name' => 'Tuguegarao City']);
    $this->published = FinanceFigure::factory()->verified()->for($this->town)->line('performance.revenue', 2025, '1679019473.60')->create();
    FinanceLine::factory()->create(['code' => 'performance.expenses', 'group' => 'totals', 'source' => 'executive_summary']);
    $this->route = route('capitol.finances.figures.store', ['town' => '0201529000', 'year' => 2025]);
});

/** A report grid save with one or more changed cells. */
function gridSave(array $changes, array $shared = []): array
{
    return ['submission' => (string) Str::uuid(), 'document_kind' => 'executive_summary', 'page' => 2, ...$shared, 'changes' => $changes];
}

function revenueCell(string $value, ?string $base = '1679019473.60'): array
{
    return ['line_code' => 'performance.revenue', 'fiscal_year' => 2025, 'basis' => 'actual', 'value' => $value, 'base' => $base];
}

it('prefills the grid with the current figure and the report years', function () {
    $this->actingAs(User::factory()->create())->get(route('capitol.finances.show', ['town' => '0201529000', 'year' => 2025]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('report.entryYears', [2024, 2025])
            ->where('report.cells.0.line', 'performance.revenue')
            ->where('report.cells.0.value', '1679019473.60')
            ->where('report.cells.0.status', 'verified'));
});

it('refuses a save when the report changed since the grid was opened', function () {
    $this->actingAs(User::factory()->create())->post($this->route, gridSave([revenueCell('1679019473.70', '1600000000.00')], ['reason' => 'Typo']))
        ->assertSessionHasErrors('changes');

    expect(FinanceFigure::query()->count())->toBe(1);
});

it('needs a reason to correct a verified amount and keeps the published one live until review', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post($this->route, gridSave([revenueCell('1679019473.70')]))->assertSessionHasErrors('reason');
    expect(FinanceFigure::query()->count())->toBe(1);

    $this->actingAs($user)->post($this->route, gridSave([revenueCell('1679019473.70')], ['reason' => 'Last digit misread']))
        ->assertSessionHasNoErrors()->assertRedirect();

    $proposal = FinanceFigure::query()->where('entry_method', 'manual')->sole();
    expect($proposal->status)->toBe(FinanceFigureStatus::Pending)
        ->and($proposal->note)->toBe('Last digit misread')
        ->and($proposal->location)->toBe('page 2')
        ->and($this->published->fresh()->status)->toBe(FinanceFigureStatus::Verified);
});

it('saves the same submission only once', function () {
    $user = User::factory()->create();
    $save = gridSave([['line_code' => 'performance.expenses', 'fiscal_year' => 2025, 'basis' => 'actual', 'value' => '1403269517.37', 'base' => null]]);

    $this->actingAs($user)->post($this->route, $save)->assertRedirect();
    $this->actingAs($user)->post($this->route, $save)->assertRedirect();

    expect(FinanceFigure::query()->where('line_code', 'performance.expenses')->count())->toBe(1);
});

it('ignores unchanged cells and refuses a save with no change', function () {
    $this->actingAs(User::factory()->create())->post($this->route, gridSave([revenueCell('1679019473.60')]))
        ->assertSessionHasErrors('changes');

    expect(FinanceFigure::query()->count())->toBe(1);
});

it('limits the changes in one save', function () {
    config(['cagayan.finances.entry_changes_max' => 1]);
    $cell = fn (int $year) => ['line_code' => 'performance.expenses', 'fiscal_year' => $year, 'basis' => 'actual', 'value' => '1.00', 'base' => null];

    $this->actingAs(User::factory()->create())->post($this->route, gridSave([$cell(2024), $cell(2025)]))
        ->assertSessionHasErrors('changes');

    expect(FinanceFigure::query()->count())->toBe(1);
});

it('rate limits finance saves per person', function () {
    config(['cagayan.finances.writes_per_minute' => 2]);
    $user = User::factory()->create();
    $save = fn () => gridSave([['line_code' => 'performance.expenses', 'fiscal_year' => 2025, 'basis' => 'actual', 'value' => '1.00', 'base' => null]]);

    $this->actingAs($user)->post($this->route, $save());
    $this->actingAs($user)->post($this->route, $save());
    $this->actingAs($user)->post($this->route, $save())->assertTooManyRequests();
});
