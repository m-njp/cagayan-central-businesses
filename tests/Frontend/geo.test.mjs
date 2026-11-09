import assert from 'node:assert/strict';
import { test } from 'node:test';
import { containsPoint } from '../../resources/js/lib/geo.ts';

const square = [
    [0, 0],
    [10, 0],
    [10, 10],
    [0, 10],
    [0, 0],
];
const hole = [
    [4, 4],
    [6, 4],
    [6, 6],
    [4, 6],
    [4, 4],
];

void test('a pin inside a barangay polygon is found, outside or in a hole is not', () => {
    const polygon = { type: 'Polygon', coordinates: [square, hole] };

    assert.equal(containsPoint(polygon, [2, 2]), true);
    assert.equal(containsPoint(polygon, [5, 5]), false);
    assert.equal(containsPoint(polygon, [12, 2]), false);
});

void test('any part of a multipolygon counts', () => {
    const shifted = square.map(([x, y]) => [x + 20, y]);
    const multi = { type: 'MultiPolygon', coordinates: [[square], [shifted]] };

    assert.equal(containsPoint(multi, [25, 5]), true);
    assert.equal(
        containsPoint({ type: 'Point', coordinates: [1, 1] }, [1, 1]),
        false,
    );
});
