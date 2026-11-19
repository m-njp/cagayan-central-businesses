import { useEffect, useMemo, useState } from 'react';
import { town as townFinances } from '@/routes/public/economic-performance';
import type {
    AnalyticsCatalog,
    AnalyticsTownFinances,
} from '@/types/economic-performance';

export type TownStatus = 'ready' | 'loading' | 'error';

/**
 * One request per town and published data version for the whole visit, shared by every page and remount. A failed
 * load is evicted so Try Again fetches it again (frontend.md, "Local Selection Contract").
 */
const loads = new Map<string, Promise<AnalyticsTownFinances>>();

function loadTown(
    version: number,
    town: string,
): Promise<AnalyticsTownFinances> {
    const key = `${version} ${town}`;
    let load = loads.get(key);
    if (!load) {
        load = fetch(townFinances.url(town), {
            headers: { Accept: 'application/json' },
        }).then((response) => {
            if (!response.ok) throw new Error('Town finances unavailable');
            return response.json() as Promise<AnalyticsTownFinances>;
        });
        load.catch(() => loads.delete(key));
        loads.set(key, load);
    }
    return load;
}

/**
 * The page's catalog plus the finances of every town loaded so far. The first selection of a town with figures
 * fetches it once; selecting it again, Back and Forward reuse the cache and make no request.
 */
export function useTownCatalog(
    catalog: AnalyticsCatalog,
    town: string,
): { catalog: AnalyticsCatalog; status: TownStatus; retry: () => void } {
    const [loaded, setLoaded] = useState<Record<string, AnalyticsTownFinances>>(
        {},
    );
    const [failed, setFailed] = useState<string | null>(null);
    const [attempt, setAttempt] = useState(0);
    const missing =
        catalog.financeTowns.includes(town) &&
        !(town in catalog.finances) &&
        !(town in loaded);

    useEffect(() => {
        if (!missing) return;
        let active = true;
        loadTown(catalog.lastYear, town).then(
            (data) => {
                if (active)
                    setLoaded((current) => ({ ...current, [town]: data }));
            },
            () => {
                if (active) setFailed(town);
            },
        );
        return () => {
            active = false;
        };
    }, [missing, town, catalog.lastYear, attempt]);

    const merged = useMemo(() => {
        const towns = Object.values(loaded);
        if (towns.length === 0) return catalog;
        return {
            ...catalog,
            finances: Object.assign(
                {},
                catalog.finances,
                ...towns.map((item) => item.finances),
            ),
            sources: Object.assign(
                {},
                catalog.sources,
                ...towns.map((item) => item.sources),
            ),
        };
    }, [catalog, loaded]);

    return {
        catalog: merged,
        status: !missing ? 'ready' : failed === town ? 'error' : 'loading',
        retry: () => {
            setFailed(null);
            setAttempt((current) => current + 1);
        },
    };
}
