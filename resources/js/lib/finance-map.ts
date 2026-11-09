import type { FinanceMap } from '@/types/finance-map';

export const MAP_MEASURE = 'performance.revenue';

export function financeMapSelection(
    finances: FinanceMap,
    params: URLSearchParams,
) {
    const requested = params.get('year');
    const latest =
        !requested ||
        requested === 'latest' ||
        !finances.years.includes(Number(requested));
    const year = latest ? finances.latestYear : Number(requested);
    return { latest, year };
}

export function financeMapObservation(
    finances: FinanceMap,
    code: string,
    year: number | null,
    measure: string,
) {
    return year === null
        ? undefined
        : finances.observations[code]?.[year]?.[measure];
}

export function financeMapShade(
    finances: FinanceMap,
    code: string,
    year: number | null,
    measure: string,
) {
    return year === null
        ? null
        : (finances.shades[code]?.[year]?.[measure] ?? null);
}
