import assert from 'node:assert/strict';
import { test } from 'node:test';
import {
    financeMapSelection,
    financeMapObservation,
    financeMapShade,
    municipalityIncomeRanking,
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

void test('municipality ranks compare consecutive years and give ties the same place', () => {
    const towns = [
        'alpha',
        'beta',
        'gamma',
        'delta',
        'hidden',
        'returning',
        'flat',
    ].map((code) => ({ code, name: code }));
    const observation = (value, state = 'shown') => ({
        'performance.revenue': { state, value: String(value) },
    });
    const finances = {
        years: [2025, 2024, 2023],
        observations: {
            alpha: {
                2023: observation(50),
                2024: observation(50),
                2025: observation(50),
            },
            beta: { 2024: observation(10), 2025: observation(50) },
            gamma: { 2024: observation(40), 2025: observation(30) },
            delta: { 2025: observation(10) },
            hidden: { 2025: observation(90, 'hidden') },
            returning: { 2023: observation(20), 2025: observation(20) },
            flat: { 2024: observation(35), 2025: observation(30) },
        },
    };
    const rows = municipalityIncomeRanking(finances, towns, 2025);
    const summary = ({ town, rank, change }) => [town.code, rank, change];

    assert.deepEqual(rows.map(summary), [
        ['alpha', 1, 0],
        ['beta', 1, 3],
        ['flat', 3, 0],
        ['gamma', 3, -1],
        ['returning', 5, null],
        ['delta', 6, 'new'],
        ['hidden', null, null],
    ]);
    assert.deepEqual(
        municipalityIncomeRanking(finances, towns, 2023)
            .slice(0, 2)
            .map(summary),
        [
            ['alpha', 1, null],
            ['returning', 2, null],
        ],
    );
    const gap = { ...finances, years: [2025, 2023] };
    assert.deepEqual(
        municipalityIncomeRanking(gap, towns, 2025).slice(0, 2).map(summary),
        [
            ['alpha', 1, null],
            ['beta', 1, null],
        ],
    );
});
