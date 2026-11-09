<?php

use App\Enums\FinanceFigureStatus;
use App\Models\FinanceFigure;
use App\Models\Municipality;
use App\Models\User;
use App\Services\FinanceSignOff;
use Illuminate\Database\QueryException;

it('refuses a sign-off by the person who entered the figure', function () {
    $clerk = User::factory()->create();
    $figure = FinanceFigure::factory()->manual()->create(['entered_by' => $clerk->id]);

    expect(fn () => app(FinanceSignOff::class)->verify($figure, $clerk, '2026-10-28'))->toThrow(DomainException::class);

    app(FinanceSignOff::class)->verify($figure, User::factory()->create(), '2026-10-28');
    expect($figure->refresh()->status)->toBe(FinanceFigureStatus::Verified);
});

it('allows only one verified figure per town, year, line, and basis', function () {
    $town = Municipality::factory()->create();
    FinanceFigure::factory()->verified()->for($town)->line('performance.revenue', 2024, '100.00')->create();

    expect(fn () => FinanceFigure::factory()->manual()->verified()->for($town)->line('performance.revenue', 2024, '101.00')->create())
        ->toThrow(QueryException::class);
});

it('lets a manual correction outrank every report', function () {
    $town = Municipality::factory()->create();
    $parsed = FinanceFigure::factory()->for($town)->line('performance.revenue', 2024, '100.00')->create();
    $correction = FinanceFigure::factory()->manual()->for($town)->line('performance.revenue', 2024, '110.00')->create();
    $signOff = app(FinanceSignOff::class);

    $signOff->verify($correction, null, '2026-10-28');
    $signOff->verify($parsed, null, '2026-10-29');

    expect($correction->refresh()->status)->toBe(FinanceFigureStatus::Verified)
        ->and($parsed->refresh()->status)->toBe(FinanceFigureStatus::Superseded);
});

it('rejects a figure without publishing it', function () {
    $figure = FinanceFigure::factory()->create();

    app(FinanceSignOff::class)->reject($figure, null, '2026-10-28', 'Columns swapped in the report');

    expect($figure->refresh()->status)->toBe(FinanceFigureStatus::Rejected)
        ->and($figure->note)->toBe('Columns swapped in the report');
});
