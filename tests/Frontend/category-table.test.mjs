import assert from 'node:assert/strict';
import { test } from 'node:test';
import {
    categoryQuery,
    parseCategoryView,
    titleCase,
    visibleTypes,
} from '../../resources/js/lib/category-table.ts';
import { paginate } from '../../resources/js/lib/pagination.ts';

const categories = [{ code: 'agriculture' }, { code: 'tourism' }];
const names = { agriculture: 'Agriculture', tourism: 'Tourism' };
const industries = [{ value: 'A' }, { value: 'G' }];
const view = (query = '') =>
    parseCategoryView(new URLSearchParams(query), categories, industries);
const row = (name, extra = {}) => ({
    code: name,
    name,
    category: 'tourism',
    industry: 'Trade',
    industryCode: 'G',
    registered: 1,
    ...extra,
});

void test('stale or edited URL state falls back to the defaults', () => {
    assert.deepEqual(
        view(
            'view=bogus&sort=nope&dir=sideways&category=mining&industry=Z&page=-4',
        ),
        {
            q: '',
            sort: 'name',
            dir: 'asc',
            category: null,
            industry: null,
            page: 1,
        },
    );
    assert.equal(view('view=categories&sort=industry').sort, 'industry');
    assert.equal(view('view=categories&sort=types').sort, 'name');
    assert.equal(view('view=types&sort=category').sort, 'category');
});

void test('only non-default state reaches the URL', () => {
    assert.deepEqual(categoryQuery(view()), {});
    assert.deepEqual(
        categoryQuery(
            view(
                'view=types&sort=registered&dir=desc&category=tourism&page=3&q=cafe',
            ),
        ),
        {
            q: 'cafe',
            sort: 'registered',
            dir: 'desc',
            category: 'tourism',
            page: '3',
        },
    );
    assert.deepEqual(categoryQuery(view('category=tourism')), {
        category: 'tourism',
    });
});

void test('names sort by locale so Ñ follows N, and ties fall back to the name', () => {
    const rows = [
        row('Ñandú Farm'),
        row('Zebra'),
        row('Nail Salon'),
        row('Apple'),
    ];
    assert.deepEqual(
        visibleTypes(rows, view('view=types'), names).map((item) => item.name),
        ['Apple', 'Nail Salon', 'Ñandú Farm', 'Zebra'],
    );
    assert.deepEqual(
        visibleTypes(
            [row('B', { registered: 2 }), row('A', { registered: 2 })],
            view('view=types&sort=registered&dir=desc'),
            names,
        ).map((item) => item.name),
        ['A', 'B'],
    );
});

void test('a count that is not available sorts last in both directions', () => {
    const rows = [
        row('A', { registered: null }),
        row('B', { registered: 5 }),
        row('C', { registered: 1 }),
    ];
    assert.deepEqual(
        visibleTypes(rows, view('view=types&sort=registered'), names).map(
            (item) => item.name,
        ),
        ['C', 'B', 'A'],
    );
    assert.deepEqual(
        visibleTypes(
            rows,
            view('view=types&sort=registered&dir=desc'),
            names,
        ).map((item) => item.name),
        ['B', 'C', 'A'],
    );
});

void test('search, category and industry filters combine, and types sort by category name', () => {
    const rows = [
        row('Rice Farm', {
            category: 'agriculture',
            industry: 'Agriculture',
            industryCode: 'A',
        }),
        row('Feed Store', { category: 'agriculture' }),
        row('Hotel or Inn', { category: 'tourism' }),
    ];
    const types = (query) =>
        visibleTypes(rows, view('view=types&' + query), names).map(
            (item) => item.name,
        );
    assert.deepEqual(types('category=agriculture'), [
        'Feed Store',
        'Rice Farm',
    ]);
    assert.deepEqual(types('category=agriculture&industry=A'), ['Rice Farm']);
    assert.deepEqual(types('q=agri'), ['Feed Store', 'Rice Farm']);
    assert.deepEqual(types('q=zzz'), []);
    assert.deepEqual(types('sort=category&dir=desc'), [
        'Hotel or Inn',
        'Feed Store',
        'Rice Farm',
    ]);
});

void test('paging clamps to the last page and reports totals', () => {
    const rows = Array.from({ length: 25 }, (_, index) => index);
    assert.deepEqual(
        paginate(rows, 1, 10).rows,
        [0, 1, 2, 3, 4, 5, 6, 7, 8, 9],
    );
    assert.deepEqual(paginate(rows, 99, 10), {
        rows: [20, 21, 22, 23, 24],
        page: 3,
        lastPage: 3,
        total: 25,
    });
    assert.deepEqual(paginate([], 4, 10), {
        rows: [],
        page: 1,
        lastPage: 1,
        total: 0,
    });
});

void test('Title Case is a suggestion that keeps acronyms and brands', () => {
    assert.equal(titleCase('sari-sari STORE'), 'Sari-Sari Store');
    assert.equal(titleCase('  LPG   DEALER '), 'LPG Dealer');
    assert.equal(titleCase('carinderia or eatery'), 'Carinderia or Eatery');
    assert.equal(titleCase('McDonald franchise'), 'McDonald Franchise');
    assert.equal(titleCase('ñandú farm and feed'), 'Ñandú Farm and Feed');
    assert.equal(titleCase('Rice and Corn Farm'), 'Rice and Corn Farm');
});
