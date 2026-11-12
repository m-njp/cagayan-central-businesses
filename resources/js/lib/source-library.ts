export type SourceSelection = {
    town: string;
    year: number | null;
    q: string;
};

export function parseSourceSelection(
    params: URLSearchParams,
    towns: string[],
    years: number[],
): SourceSelection {
    const requestedTown = params.get('town') ?? '';
    const requestedYear = Number(params.get('year'));
    return {
        town: towns.includes(requestedTown)
            ? requestedTown
            : towns.includes('Tuguegarao City')
              ? 'Tuguegarao City'
              : (towns[0] ?? ''),
        year: years.includes(requestedYear) ? requestedYear : null,
        q: params.get('q') ?? '',
    };
}

export function sourceQuery(selection: SourceSelection) {
    return {
        town: selection.town || undefined,
        year: selection.year?.toString(),
        q: selection.q || undefined,
    };
}
