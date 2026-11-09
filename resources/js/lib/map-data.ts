import type { FeatureCollection, Geometry } from 'geojson';

export type AreaProperties = {
    psgc_code: string;
    name: string;
    population?: number | null;
    shade?: number | null;
};
export type AreaCollection = FeatureCollection<Geometry, AreaProperties>;
export type CensusCollection = AreaCollection & {
    year: number;
    source: string;
    raw: string;
};
const loaders = import.meta.glob('../../../data/barangays/maps/*.json', {
    query: '?raw',
    import: 'default',
});
const cached = new Map<string, Promise<CensusCollection>>();
const loaded = new Map<string, CensusCollection>();

// Synchronous read so a revisited town renders without a loading frame.
export function loadedTownBoundaries(
    code: string,
): CensusCollection | undefined {
    return loaded.get(code);
}

export function loadTownBoundaries(code: string): Promise<CensusCollection> {
    const existing = cached.get(code);
    if (existing) return existing;
    const loader = loaders['../../../data/barangays/maps/' + code + '.json'];
    if (!loader) return Promise.reject(new Error('Boundary data unavailable'));
    const pending = loader()
        .then((raw) => {
            const census = {
                ...(JSON.parse(raw as string) as CensusCollection),
                raw: raw as string,
            };
            loaded.set(code, census);
            return census;
        })
        .catch((error: unknown) => {
            cached.delete(code);
            throw error;
        });
    cached.set(code, pending);
    return pending;
}

export function collectionBounds(
    data: AreaCollection,
): [[number, number], [number, number]] {
    let west = Infinity,
        east = -Infinity,
        south = Infinity,
        north = -Infinity;
    function visit(value: unknown): void {
        if (!Array.isArray(value)) return;
        if (typeof value[0] === 'number' && typeof value[1] === 'number') {
            west = Math.min(west, value[0]);
            east = Math.max(east, value[0]);
            south = Math.min(south, value[1]);
            north = Math.max(north, value[1]);
        } else value.forEach(visit);
    }
    data.features.forEach(({ geometry }) => {
        if ('coordinates' in geometry) visit(geometry.coordinates);
    });
    return [
        [west, south],
        [east, north],
    ];
}
