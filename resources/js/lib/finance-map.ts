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

export function municipalityIncomeRanking(
    finances: FinanceMap,
    towns: { code: string; name: string }[],
    year: number | null,
) {
    const rowsFor = (fiscalYear: number | null) =>
        towns
            .map((town) => {
                const point = financeMapObservation(
                    finances,
                    town.code,
                    fiscalYear,
                    MAP_MEASURE,
                );
                const value =
                    point?.state === 'shown' && point.value !== null
                        ? Number(point.value)
                        : null;
                return { town, point, value };
            })
            .sort(
                (a, b) =>
                    (b.value ?? -Infinity) - (a.value ?? -Infinity) ||
                    a.town.name.localeCompare(b.town.name),
            );

    const ranksFor = (rows: ReturnType<typeof rowsFor>) => {
        const ranks = new Map<string, number>();
        let rank = 0;
        rows.forEach(({ town, value }, index) => {
            if (value === null) return;
            if (value !== rows[index - 1]?.value) rank = index + 1;
            ranks.set(town.code, rank);
        });
        return ranks;
    };

    const current = rowsFor(year);
    const ranks = ranksFor(current);
    const history = new Map(
        finances.years
            .filter((fiscalYear) => year !== null && fiscalYear < year)
            .map((fiscalYear) => [fiscalYear, ranksFor(rowsFor(fiscalYear))]),
    );
    const previous = year === null ? undefined : history.get(year - 1);

    return current.map(({ town, point, value }) => {
        const rank = ranks.get(town.code) ?? null;
        const priorRank = previous?.get(town.code);
        const firstAppearance =
            rank !== null &&
            !!previous?.size &&
            !Array.from(history.values()).some((past) => past.has(town.code));
        const change =
            rank !== null && priorRank !== undefined
                ? priorRank - rank
                : firstAppearance
                  ? ('new' as const)
                  : null;
        return { town, point, value, rank, change };
    });
}
