import assert from 'node:assert/strict';
import { test } from 'node:test';
import {
    financeMapSelection,
    financeMapObservation,
    financeMapShade,
} from '../../resources/js/lib/finance-map.ts';

const map = {
    years: [2026, 2025, 2024],
    latestYear: 2025,
    metrics: [{ id: 'performance.revenue' }, { id: 'performance.surplus' }],
    observations: {
        alpha: {
            2024: { 'performance.revenue': { state: 'shown', value: '10' } },
        },
    },
    shades: { alpha: { 2024: { 'performance.revenue': 1 } } },
};

void test('Latest is one global paired year and only Latest enables population', () => {
    assert.deepEqual(financeMapSelection(map, new URLSearchParams()), {
        latest: true,
        year: 2025,
    });
    assert.deepEqual(
        financeMapSelection(
            map,
            new URLSearchParams('year=2025&measure=performance.surplus'),
        ),
        { latest: false, year: 2025 },
    );
    assert.equal(
        financeMapSelection({ ...map, latestYear: 2026 }, new URLSearchParams())
            .year,
        2026,
    );
    assert.equal(
        financeMapSelection(
            { ...map, years: [], latestYear: null },
            new URLSearchParams(),
        ).year,
        null,
    );
});

void test('exact-year selection never carries older values or shades forward', () => {
    assert.equal(
        financeMapObservation(map, 'alpha', 2025, 'performance.revenue'),
        undefined,
    );
    assert.equal(
        financeMapShade(map, 'alpha', 2025, 'performance.revenue'),
        null,
    );
    assert.equal(financeMapShade(map, 'alpha', 2024, 'performance.revenue'), 1);
    assert.equal(
        financeMapObservation(map, 'alpha', null, 'performance.revenue'),
        undefined,
    );
});

void test('invalid URL selections use safe defaults', () => {
    assert.deepEqual(
        financeMapSelection(
            map,
            new URLSearchParams('year=not-a-year&measure=private'),
        ),
        { latest: true, year: 2025 },
    );
});
