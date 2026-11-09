import type { BusinessCategoryRow, BusinessTypeRow } from '@/types/workspace';

export type CategoryView = {
    view: 'categories' | 'types';
    q: string;
    sort: string;
    dir: 'asc' | 'desc';
    category: string | null;
    industry: string | null;
    page: number;
};

const SORTS = {
    categories: ['name', 'types', 'registered'],
    types: ['name', 'category', 'industry', 'registered'],
};
const collator = new Intl.Collator('en', { numeric: true });
const SMALL_WORDS = new Set([
    'and',
    'or',
    'of',
    'the',
    'for',
    'in',
    'at',
    'to',
    'a',
    'an',
    'on',
    'by',
]);

/** Reads and validates the page state from the URL, so a stale or edited link falls back to the defaults. */
export function parseCategoryView(
    params: URLSearchParams,
    categories: { code: string }[],
    industries: { value: string }[],
): CategoryView {
    const view = params.get('view') === 'types' ? 'types' : 'categories';
    const sort = SORTS[view].includes(params.get('sort') ?? '')
        ? (params.get('sort') as string)
        : 'name';
    const category = params.get('category');
    const industry = params.get('industry');
    return {
        view,
        q: (params.get('q') ?? '').slice(0, 60),
        sort,
        dir: params.get('dir') === 'desc' ? 'desc' : 'asc',
        category: categories.some((item) => item.code === category)
            ? category
            : null,
        industry: industries.some((item) => item.value === industry)
            ? industry
            : null,
        page: Math.max(1, Math.floor(Number(params.get('page'))) || 1),
    };
}

/** Only non-default state goes in the URL. */
export function categoryQuery(state: CategoryView): Record<string, string> {
    const query: Record<string, string> = {};
    if (state.view !== 'categories') query.view = state.view;
    if (state.q) query.q = state.q;
    if (state.sort !== 'name' || state.dir !== 'asc') {
        query.sort = state.sort;
        query.dir = state.dir;
    }
    if (state.view === 'types' && state.category)
        query.category = state.category;
    if (state.view === 'types' && state.industry)
        query.industry = state.industry;
    if (state.page > 1) query.page = String(state.page);
    return query;
}

/** Counts that are not available sort last in either direction. */
function compare(
    a: string | number | null,
    b: string | number | null,
    dir: 'asc' | 'desc',
) {
    if (a === null || b === null) return a === b ? 0 : a === null ? 1 : -1;
    const order =
        typeof a === 'number' && typeof b === 'number'
            ? a - b
            : collator.compare(String(a), String(b));
    return dir === 'asc' ? order : -order;
}

/** `names` maps a category code to its name, for searching and sorting types by category. */
export function visibleTypes(
    rows: BusinessTypeRow[],
    state: CategoryView,
    names: Record<string, string>,
) {
    const q = state.q.trim().toLowerCase();
    const key = (row: BusinessTypeRow) =>
        state.sort === 'category'
            ? (names[row.category] ?? row.category)
            : state.sort === 'industry'
              ? row.industry
              : state.sort === 'registered'
                ? row.registered
                : row.name;
    return rows
        .filter(
            (row) =>
                (!q ||
                    row.name.toLowerCase().includes(q) ||
                    row.industry.toLowerCase().includes(q) ||
                    (names[row.category] ?? '').toLowerCase().includes(q)) &&
                (!state.category || row.category === state.category) &&
                (!state.industry || row.industryCode === state.industry),
        )
        .sort(
            (a, b) =>
                compare(key(a), key(b), state.dir) ||
                collator.compare(a.name, b.name),
        );
}

export function visibleCategories(
    rows: BusinessCategoryRow[],
    state: CategoryView,
) {
    const q = state.q.trim().toLowerCase();
    const key = (row: BusinessCategoryRow) =>
        state.sort === 'types'
            ? row.types
            : state.sort === 'registered'
              ? row.registered
              : row.name;
    return rows
        .filter((row) => !q || row.name.toLowerCase().includes(q))
        .sort(
            (a, b) =>
                compare(key(a), key(b), state.dir) ||
                collator.compare(a.name, b.name),
        );
}

export function paginate<T>(rows: T[], page: number, size: number) {
    const lastPage = Math.max(1, Math.ceil(rows.length / size));
    const current = Math.min(page, lastPage);
    const start = (current - 1) * size;
    return {
        rows: rows.slice(start, start + size),
        page: current,
        lastPage,
        total: rows.length,
    };
}

/** A suggestion only. Acronyms such as LPG and mixed-case brands such as McDonald stay as typed. */
export function titleCase(name: string): string {
    return name
        .trim()
        .replace(/\s+/g, ' ')
        .split(' ')
        .map((word, index) => {
            if (/^\p{Lu}{2,4}$/u.test(word) || /\p{Ll}\p{Lu}/u.test(word))
                return word;
            if (index > 0 && SMALL_WORDS.has(word.toLowerCase()))
                return word.toLowerCase();
            return word
                .toLowerCase()
                .replace(
                    /(^|[-/(])(\p{L})/gu,
                    (_, lead: string, letter: string) =>
                        lead + letter.toUpperCase(),
                );
        })
        .join(' ');
}
