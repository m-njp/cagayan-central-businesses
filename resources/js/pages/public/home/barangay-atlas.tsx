import { usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { LazyProvinceMap } from '@/components/map/lazy-province-map';
import type { MapStyle } from '@/components/map/province-map';
import { Button } from '@/components/ui/button';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Skeleton } from '@/components/ui/skeleton';
import { formatCount, NOT_AVAILABLE, sourceName } from '@/lib/format';
import {
    loadedTownBoundaries,
    loadTownBoundaries,
    type CensusCollection,
} from '@/lib/map-data';
import { pageSearchParams, replaceLocalQuery } from '@/lib/navigation';
import { home } from '@/routes';
import type { BarangayProfile, TownProfile } from '@/types/data';

export function BarangayAtlas({
    towns,
    counts,
    mapStyle,
}: {
    towns: TownProfile[];
    // Published counts from the database win over the boundary file, so a verified count needs no rebuild.
    counts: BarangayProfile[];
    mapStyle: MapStyle;
}) {
    const published = new Map(counts.map((item) => [item.code, item]));
    const { url } = usePage();
    const requested = pageSearchParams(url).get('barangayTown');
    const town =
        towns.find((item) => item.code === requested) ??
        towns.find((item) => item.code === '0201529000');
    const townCode = town?.code;
    const [fetched, setFetched] = useState<{
        code: string;
        census: CensusCollection;
    } | null>(null);
    const [failedTown, setFailedTown] = useState<string | null>(null);
    const [attempt, setAttempt] = useState(0);
    const [selections, setSelections] = useState<Record<string, string>>({});
    const chosen = townCode ? (selections[townCode] ?? '') : '';
    useEffect(() => {
        if (!townCode) return;
        let active = true;
        setFailedTown(null);
        loadTownBoundaries(townCode)
            .then((census) => {
                if (active) setFetched({ code: townCode, census });
            })
            .catch(() => {
                if (active) setFailedTown(townCode);
            });
        return () => {
            active = false;
        };
    }, [townCode, attempt]);
    // The census arrives through state: a module cache read alone is not a dependency, so the first render would never update.
    const current =
        (townCode ? loadedTownBoundaries(townCode) : undefined) ??
        (fetched && fetched.code === townCode ? fetched.census : undefined);
    const findBarangay = (code: string) => {
        const properties = current?.features.find(
            (f) => f.properties.psgc_code === code,
        )?.properties;
        const count = published.get(code);
        return properties && count
            ? {
                  ...properties,
                  population: count.population
                      ? Number(count.population.value)
                      : null,
                  shade: count.shade,
              }
            : properties;
    };
    const picked = chosen ? findBarangay(chosen) : undefined;
    if (!town)
        return <p className="type-body">No town population is available.</p>;
    return (
        <div className="flex flex-col gap-4">
            <div className="flex flex-col gap-2">
                <label htmlFor="population-town" className="type-label">
                    Town
                </label>
                <div className="flex items-center gap-2">
                    <Select
                        value={town.code}
                        onValueChange={(code) =>
                            replaceLocalQuery(url, home, {
                                barangayTown: code,
                            })
                        }
                    >
                        <SelectTrigger
                            variant="quiet"
                            id="population-town"
                            className="min-w-0 flex-1"
                        >
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            {towns.map((item) => (
                                <SelectItem key={item.code} value={item.code}>
                                    {item.name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>
            </div>
            {failedTown === townCode ? (
                <div role="alert" className="flex flex-col gap-3">
                    <p>Could not load the barangay map.</p>
                    <Button
                        variant="outline"
                        onClick={() => setAttempt(attempt + 1)}
                    >
                        Try Again
                    </Button>
                </div>
            ) : !current ? (
                <Skeleton
                    aria-label="Loading barangay population"
                    role="status"
                    className="h-64 lg:h-72"
                />
            ) : (
                <>
                    <div className="h-64 text-primary lg:h-72">
                        <LazyProvinceMap
                            key={town.code}
                            source={current.raw}
                            mapStyle={mapStyle}
                            label={`Barangay population in ${town.name}, 2024`}
                            shades={Object.fromEntries(
                                current.features.map(({ properties: p }) => [
                                    p.psgc_code,
                                    findBarangay(p.psgc_code)?.shade ?? null,
                                ]),
                            )}
                            highlight={chosen ? [chosen] : []}
                            describe={(code, name) => {
                                const population =
                                    findBarangay(code)?.population;

                                return (
                                    <span>
                                        {name}:{' '}
                                        {population == null
                                            ? NOT_AVAILABLE
                                            : `${formatCount(population)} people`}
                                    </span>
                                );
                            }}
                            onSelect={(code) =>
                                setSelections((previous) => ({
                                    ...previous,
                                    [town.code]:
                                        previous[town.code] === code
                                            ? ''
                                            : code,
                                }))
                            }
                            className="size-full"
                        />
                    </div>
                    <div className="min-h-6">
                        {picked && picked.population != null && (
                            <p className="flex items-baseline justify-between gap-3">
                                <span
                                    className="truncate type-item"
                                    title={picked.name}
                                >
                                    {picked.name}
                                </span>
                                <span className="shrink-0 font-mono tabular-nums">
                                    {formatCount(picked.population)} people
                                </span>
                            </p>
                        )}
                    </div>
                    <div className="flex items-baseline gap-2 border-t pt-3 type-meta text-foreground">
                        <span>Source</span>
                        <a
                            href={current.source}
                            target="_blank"
                            rel="noreferrer"
                            className="underline underline-offset-2"
                        >
                            {sourceName(current.source)}
                            <span className="sr-only">
                                {' '}
                                (opens in a new tab)
                            </span>
                        </a>
                    </div>
                </>
            )}
        </div>
    );
}
