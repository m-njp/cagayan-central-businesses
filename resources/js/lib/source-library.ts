export type SourceSelection = {
    town: string;
    q: string;
};

/** The report library's municipality and search, from a shareable URL. Every year is always listed (26-11-14). */
export function parseSourceSelection(
    params: URLSearchParams,
    towns: string[],
): SourceSelection {
    const requestedTown = params.get('town') ?? '';
    return {
        town: towns.includes(requestedTown)
            ? requestedTown
            : towns.includes('Tuguegarao City')
              ? 'Tuguegarao City'
              : (towns[0] ?? ''),
        q: params.get('q') ?? '',
    };
}

export function sourceQuery(selection: SourceSelection) {
    return {
        town: selection.town || undefined,
        q: selection.q || undefined,
    };
}
