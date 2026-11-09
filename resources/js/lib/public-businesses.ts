// URL state and local filtering for the public Businesses page. A town's rows load once; every other filter is local.
export type PublicBusinessRow = {
    id: string;
    name: string;
    category: string;
    barangay: string;
    bir: boolean;
    milestone: number | null;
    point: [number, number] | null;
};

export type TownBusinesses = {
    town: string;
    townName: string;
    rows: PublicBusinessRow[];
    barangays: { value: string; label: string }[];
};

export type BusinessView = {
    town: string | null;
    q: string;
    barangay: string | null;
    category: string | null;
    bir: boolean;
    map: boolean;
    shown: number;
};

export const PAGE_SIZE = 20;

/** A stale or edited link falls back to the defaults. */
export function parseBusinessView(
    params: URLSearchParams,
    towns: { value: string }[],
    barangays: { value: string }[],
    categories: { code: string }[],
): BusinessView {
    const town = params.get('town');
    const barangay = params.get('barangay');
    const category = params.get('category');
    return {
        town: towns.some((item) => item.value === town) ? town : null,
        q: (params.get('q') ?? '').slice(0, 60),
        barangay: barangays.some((item) => item.value === barangay)
            ? barangay
            : null,
        category: categories.some((item) => item.code === category)
            ? category
            : null,
        bir: params.get('bir') === '1',
        map: params.get('show') === 'map',
        shown:
            Math.max(1, Math.floor(Number(params.get('more')) || 0) + 1) *
            PAGE_SIZE,
    };
}

/** Only non-default state goes in the URL. */
export function businessQuery(view: BusinessView): Record<string, string> {
    const query: Record<string, string> = {};
    if (view.town) query.town = view.town;
    if (view.q) query.q = view.q;
    if (view.barangay) query.barangay = view.barangay;
    if (view.category) query.category = view.category;
    if (view.bir) query.bir = '1';
    if (view.map) query.show = 'map';
    if (view.shown > PAGE_SIZE) query.more = String(view.shown / PAGE_SIZE - 1);
    return query;
}

export function visibleBusinesses(
    rows: PublicBusinessRow[],
    view: BusinessView,
): PublicBusinessRow[] {
    const term = view.q.trim().toLocaleLowerCase();
    return rows.filter(
        (row) =>
            (!term || row.name.toLocaleLowerCase().includes(term)) &&
            (!view.barangay || row.barangay === view.barangay) &&
            (!view.category || row.category === view.category) &&
            (!view.bir || row.bir),
    );
}
