import assert from 'node:assert/strict';
import { test } from 'node:test';
import {
    businessQuery,
    parseBusinessView,
    visibleBusinesses,
} from '../../resources/js/lib/public-businesses.ts';

const row = (overrides) => ({
    id: 'a',
    name: 'Kape ni Maria',
    category: 'food-and-beverage',
    barangay: '0201529016',
    bir: true,
    milestone: null,
    point: null,
    ...overrides,
});
const towns = [{ value: '0201529000' }];
const barangays = [{ value: '0201529016' }];
const categories = [{ code: 'food-and-beverage' }, { code: 'retail' }];
const parse = (search) =>
    parseBusinessView(
        new URLSearchParams(search),
        towns,
        barangays,
        categories,
    );

void test('a shared link restores the town and filters and drops unknown values', () => {
    const view = parse(
        'town=0201529000&q=kape&barangay=0201529016&category=nope&new=1&show=map&more=2',
    );

    assert.deepEqual(view, {
        town: '0201529000',
        q: 'kape',
        barangay: '0201529016',
        category: null,
        bir: false,
        map: true,
        shown: 60,
    });
    assert.deepEqual(businessQuery(view), {
        town: '0201529000',
        q: 'kape',
        barangay: '0201529016',
        show: 'map',
        more: '2',
    });
    assert.equal(parse('town=9999999999').town, null);
    assert.deepEqual(businessQuery(parse('')), {});
});

void test('filters combine on the loaded rows', () => {
    const rows = [
        row({ id: 'a' }),
        row({
            id: 'b',
            name: 'Santos Hardware',
            category: 'retail',
            bir: false,
        }),
    ];
    const base = parse('');

    assert.deepEqual(
        visibleBusinesses(rows, { ...base, q: 'hardware' }).map((r) => r.id),
        ['b'],
    );
    assert.deepEqual(
        visibleBusinesses(rows, { ...base, bir: true }).map((r) => r.id),
        ['a'],
    );
    assert.deepEqual(
        visibleBusinesses(rows, { ...base, category: 'retail' }).map(
            (r) => r.id,
        ),
        ['b'],
    );
});
