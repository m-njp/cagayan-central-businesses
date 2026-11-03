import { router } from '@inertiajs/react';
import { pageSearchParams } from '@/lib/navigation';
import { loadTownBoundaries } from '@/lib/map-data';
import { dashboard } from '@/routes/capitol';
import type { WorkspaceMap } from '@/types/workspace';

// Geography is public, cached client data. Selection does not visit the server.
let latestSelection = 0;
export async function navigateDashboard(
    town: string | null,
    level?: string,
    barangay?: string,
): Promise<void> {
    const selection = ++latestSelection;
    const census =
        town && level === 'barangays' ? await loadTownBoundaries(town) : null;
    if (selection !== latestSelection) return;
    router.push({
        url: dashboard({
            query: {
                ...Object.fromEntries(pageSearchParams(window.location.href)),
                town: town ?? undefined,
                level,
                barangay,
            },
        }).url,
        preserveScroll: true,
        preserveState: true,
        props: (props) => {
            const map = props.map as WorkspaceMap;
            return {
                ...props,
                map: {
                    ...map,
                    // Shapes from the boundary file, counts from the database (Data Entry, Phase 4).
                    barangays:
                        census?.features.map(({ properties: p }) => {
                            const count = map.counts[p.psgc_code];
                            return {
                                code: p.psgc_code,
                                name: p.name,
                                population: count
                                    ? {
                                          value: String(count.value),
                                          year: count.year,
                                          sourceUrl: count.source,
                                      }
                                    : null,
                                shade: count?.shade ?? null,
                            };
                        }) ?? [],
                },
            };
        },
    });
}
