<?php

use App\Data\GuardedFigure;
use App\Services\Privacy\PrivacyGuard;

function guard(): PrivacyGuard
{
    return app(PrivacyGuard::class);
}

it('hides a group below the small-group threshold, administrators included (P2)', function () {
    $min = config('cagayan.privacy.small_group_min');

    expect(guard()->figure('900000.00', $min - 1, '300000.00'))->toEqual(GuardedFigure::hidden('small_group'))
        ->and(guard()->figure('900000.00', $min, '300000.00')->value)->toBe('900000.00');
});

it('hides a group where one business holds more than the dominance share (P3)', function () {
    // 800,000 of 1,000,000 is 80 percent, above the configured share.
    expect(guard()->figure('1000000.00', 6, '800000.00')->reason)->toBe('dominance')
        ->and(guard()->figure('1000000.00', 6, '500000.00')->state)->toBe('shown');
});

it('also hides the smallest shown cell when exactly one cell is hidden (P4)', function () {
    $min = config('cagayan.privacy.small_group_min');

    $figures = guard()->breakdown([
        'tiny' => ['total' => '100.00', 'count' => $min - 1, 'maxSingle' => '50.00'],
        'small' => ['total' => '2000.00', 'count' => $min, 'maxSingle' => '500.00'],
        'large' => ['total' => '9000.00', 'count' => $min + 4, 'maxSingle' => '900.00'],
        'larger' => ['total' => '12000.00', 'count' => $min + 9, 'maxSingle' => '900.00'],
    ]);

    expect(array_map(fn (GuardedFigure $figure) => $figure->reason, $figures))
        ->toBe(['tiny' => 'small_group', 'small' => 'complementary', 'large' => null, 'larger' => null]);
});

it('hides anything derived from a hidden figure, never zero (P6)', function () {
    $shown = new GuardedFigure('shown', value: '10.00');

    expect(guard()->derived($shown, GuardedFigure::hidden('dominance'))?->reason)->toBe('dominance')
        ->and(guard()->derived($shown, $shown))->toBeNull();
});

it('gives the public a band and nothing numeric (P7, M15)', function () {
    $band = guard()->band(new GuardedFigure('shown', value: '75000000.00'));

    expect($band->value)->toBeNull()
        ->and($band->band)->toBe('₱50 million to ₱100 million')
        ->and(guard()->band(new GuardedFigure('shown', value: '2000.00'))->band)->toBe('Under ₱10 million')
        ->and(guard()->band(new GuardedFigure('shown', value: '2000000000.00'))->band)->toBe('Over ₱1 billion')
        ->and(guard()->band(GuardedFigure::hidden('small_group')))->toEqual(GuardedFigure::hidden('small_group'));
});
