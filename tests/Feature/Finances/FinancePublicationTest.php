<?php

use App\Actions\ReviewFinanceFigures;
use App\Enums\FinanceFigureStatus;
use App\Models\FinanceFigure;
use App\Models\FinanceLine;
use App\Models\Municipality;
use App\Models\User;
use App\Queries\FinanceMapQuery;
use Illuminate\Support\Facades\DB;

it('publishes a reviewed pair and its surplus to Analytics and both maps atomically', function () {
    FinanceLine::factory()->create(['code' => 'performance.surplus', 'source' => 'derived']);
    $town = Municipality::factory()->create();
    $revenue = FinanceFigure::factory()->for($town)->line('performance.revenue', 2026, '100.00')->create();
    $expenses = FinanceFigure::factory()->for($town)->line('performance.expenses', 2026, '120.00')->create();

    $admin = User::factory()->create();
    $this->actingAs($admin)->get(route('capitol.finances.show', ['town' => $town->psgc_code, 'year' => 2026]))
        ->assertOk()->assertInertia(fn ($page) => $page->where('report.limits.lastYear', 2026));
    $this->post(route('capitol.finances.reviews.store', ['town' => $town->psgc_code, 'year' => 2026]), [
        'decision' => 'verified', 'figures' => [$revenue->public_id, $expenses->public_id],
    ])->assertSessionHasNoErrors()->assertRedirect();

    $map = app(FinanceMapQuery::class)->get();
    expect($map->latestYear)->toBe(2026)
        ->and($map->observations[$town->psgc_code][2026]['performance.surplus']->value)->toBe('-20.00');
    $this->assertDatabaseHas('stats_finance_year', ['municipality_id' => $town->id, 'fiscal_year' => 2026, 'metric' => 'performance.revenue', 'value' => '100.00']);
    $this->get(route('home'))->assertInertia(fn ($page) => $page->where('overview.finances.latestYear', 2026));
    $this->actingAs(User::factory()->create())->get(route('capitol.dashboard'))->assertInertia(fn ($page) => $page->where('map.finances', json_decode(json_encode($map), true)));
    $this->get(route('public.economic-performance', ['year' => 2026, 'town' => $town->psgc_code]))
        ->assertOk()->assertInertia(fn ($page) => $page->where('performance.catalog.lastYear', 2026));
});

it('rolls back sign-off and activity logging if refreshing finance results fails', function () {
    $figure = FinanceFigure::factory()->create();
    DB::statement('alter table stats_finance_year add constraint test_refuse_publication check (value < 0)');

    expect(fn () => app(ReviewFinanceFigures::class)->handle(User::factory()->create(), $figure->municipality, 2024, 'verified', [$figure->public_id], null))->toThrow(PDOException::class);

    expect($figure->fresh()->status)->toBe(FinanceFigureStatus::Pending);
    $this->assertDatabaseCount('stats_finance_year', 0);
    $this->assertDatabaseCount('activity_logs', 0);
});
