import { useEffect, useState } from 'react';
import { loadedTownBoundaries, loadTownBoundaries } from '@/lib/map-data';
import type { CensusCollection } from '@/lib/map-data';

// A municipality's barangay boundary file, loaded once and cached. Null code loads nothing. The file arrives through
// state: a module cache read alone is not a dependency, so the first render would never update.
export function useTownShape(code: string | null) {
    const [fetched, setFetched] = useState<{
        code: string;
        census: CensusCollection;
    } | null>(null);
    const [failed, setFailed] = useState<string | null>(null);
    useEffect(() => {
        if (!code) return;
        let active = true;
        loadTownBoundaries(code)
            .then((census) => active && setFetched({ code, census }))
            .catch(() => active && setFailed(code));
        return () => {
            active = false;
        };
    }, [code]);

    return {
        shape: code
            ? (loadedTownBoundaries(code) ??
              (fetched?.code === code ? fetched.census : undefined))
            : undefined,
        failed: code !== null && failed === code,
    };
}
