import assert from 'node:assert/strict';
import test from 'node:test';
import {
    parseSourceSelection,
    sourceQuery,
} from '../../resources/js/lib/source-library.ts';

const towns = ['Abulug', 'Peñablanca', 'Tuguegarao City'];

await test('the report browser defaults to Tuguegarao City and rejects stale selections', () => {
    assert.deepEqual(parseSourceSelection(new URLSearchParams(), towns), {
        town: 'Tuguegarao City',
        q: '',
    });
    assert.deepEqual(
        parseSourceSelection(new URLSearchParams('town=Unknown'), towns),
        { town: 'Tuguegarao City', q: '' },
    );
    assert.equal(
        parseSourceSelection(new URLSearchParams(), ['Abulug']).town,
        'Abulug',
    );
});

await test('a municipality and multiword search survive shareable URLs; a year in an old link is ignored', () => {
    const selected = { town: 'Peñablanca', q: 'Santa ' };
    const query = new URLSearchParams(
        Object.entries(sourceQuery(selected)).filter(
            ([, value]) => value !== undefined,
        ),
    );
    assert.deepEqual(parseSourceSelection(query, towns), selected);
    assert.equal(sourceQuery({ ...selected, q: '' }).q, undefined);
    assert.deepEqual(
        parseSourceSelection(
            new URLSearchParams('town=Abulug&year=2025'),
            towns,
        ),
        { town: 'Abulug', q: '' },
    );
    assert.equal('year' in sourceQuery(selected), false);
});

await test('an unavailable catalog has an empty selection rather than inventing a municipality', () => {
    assert.deepEqual(
        parseSourceSelection(new URLSearchParams('town=Abulug&year=2025'), []),
        { town: '', q: '' },
    );
});
