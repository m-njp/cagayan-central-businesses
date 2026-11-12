import assert from 'node:assert/strict';
import test from 'node:test';
import {
    parseSourceSelection,
    sourceQuery,
} from '../../resources/js/lib/source-library.ts';

const towns = ['Abulug', 'Peñablanca', 'Tuguegarao City'];
const years = [2025, 2024, 2014];

await test('the report browser defaults to the full Tuguegarao history and rejects stale selections', () => {
    assert.deepEqual(
        parseSourceSelection(new URLSearchParams(), towns, years),
        {
            town: 'Tuguegarao City',
            year: null,
            q: '',
        },
    );
    assert.deepEqual(
        parseSourceSelection(
            new URLSearchParams('town=Unknown&year=2026'),
            towns,
            years,
        ),
        {
            town: 'Tuguegarao City',
            year: null,
            q: '',
        },
    );
    assert.equal(
        parseSourceSelection(new URLSearchParams(), ['Abulug'], years).town,
        'Abulug',
    );
});

await test('a municipality, year and multiword search survive shareable URLs and Back or Forward', () => {
    const selected = { town: 'Peñablanca', year: 2014, q: 'Santa ' };
    const query = new URLSearchParams(
        Object.entries(sourceQuery(selected)).filter(
            ([, value]) => value !== undefined,
        ),
    );
    assert.deepEqual(parseSourceSelection(query, towns, years), selected);
    assert.equal(
        sourceQuery({ ...selected, year: null, q: '' }).year,
        undefined,
    );
    assert.deepEqual(
        parseSourceSelection(
            new URLSearchParams('town=Abulug&year=2025'),
            towns,
            years,
        ),
        {
            town: 'Abulug',
            year: 2025,
            q: '',
        },
    );
});

await test('an unavailable catalog has an empty selection rather than inventing a municipality', () => {
    assert.deepEqual(
        parseSourceSelection(
            new URLSearchParams('town=Abulug&year=2025'),
            [],
            [],
        ),
        {
            town: '',
            year: null,
            q: '',
        },
    );
});
