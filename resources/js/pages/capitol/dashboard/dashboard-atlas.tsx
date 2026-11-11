import { router, usePage } from '@inertiajs/react';
import {
    lazy,
    Suspense,
    useCallback,
    useEffect,
    useMemo,
    useState,
} from 'react';
import townsRaw from '../../../../../data/boundaries/cagayan-municipalities.geojson?raw';
import { WorkspaceFilters } from '@/components/capitol/workspace-filters';
import { FinanceYearsTable } from '@/components/finance-years-table';
import { Button } from '@/components/ui/button';
import { Field, FieldLabel } from '@/components/ui/field';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Skeleton } from '@/components/ui/skeleton';
import { Surface } from '@/components/ui/surface';
import { VisualErrorBoundary } from '@/components/visual-error-boundary';
import { useInBrowser } from '@/hooks/use-in-browser';
import {
    FinanceMapControls,
    FinanceMapFigures,
} from '@/components/map/finance-map-panel';
import {
    financeMapSelection,
    financeMapShade,
    MAP_MEASURE,
} from '@/lib/finance-map';
import { dashboard } from '@/routes/capitol';
import { loadTownBoundaries } from '@/lib/map-data';
import type { AreaCollection } from '@/lib/map-data';
import { pageSearchParams } from '@/lib/navigation';
import { navigateDashboard } from '@/lib/workspace-map-navigation';
import type { WorkspaceMap } from '@/types/workspace';

const AreaMap = lazy(() => import('@/components/map/area-map'));
const townGeometry = JSON.parse(townsRaw) as AreaCollection;

export function DashboardAtlas({ data }: { data: WorkspaceMap }) {
    const { url } = usePage();
    const inBrowser = useInBrowser();
    const params = pageSearchParams(url);
    const townCode = params.get('town');
    const selection = financeMapSelection(data.finances, params);
    const change = (patch: Record<string, string | null>) => {
        const query = Object.fromEntries(params);
        for (const [key, value] of Object.entries(patch)) {
            if (value) query[key] = value;
            else delete query[key];
        }
        router.push({
            url: dashboard({ query }).url,
            preserveScroll: true,
            preserveState: true,
        });
    };
    const drilling = params.get('level') === 'barangays';
    const barangayCode = params.get('barangay');
    const town =
        data.atlas.towns.find((item) => item.code === townCode) ?? null;
    const barangay =
        data.barangays.find((item) => item.code === barangayCode) ?? null;
    const shown = barangay ?? town ?? data.atlas.province;
    const financeArea = town ?? data.atlas.province;
    const [loaded, setLoaded] = useState<{
        code: string;
        source: AreaCollection;
    } | null>(null);
    const [failed, setFailed] = useState<string | null>(null);
    useEffect(() => {
        if (!drilling || !townCode) return;
        let active = true;
        setFailed(null);
        loadTownBoundaries(townCode)
            .then((source) => {
                if (active) setLoaded({ code: townCode, source });
            })
            .catch(() => {
                if (active) setFailed(townCode);
            });
        return () => {
            active = false;
        };
    }, [townCode, drilling]);
    const source = useMemo<AreaCollection>(
        () => ({
            ...townGeometry,
            features: townGeometry.features.map((feature) => ({
                ...feature,
                properties: {
                    ...feature.properties,
                    shade: financeMapShade(
                        data.finances,
                        feature.properties.psgc_code,
                        selection.year,
                        MAP_MEASURE,
                    ),
                },
            })),
        }),
        [data.finances, selection.year],
    );
    const [navigating, setNavigating] = useState(false);
    const navigate = useCallback(
        async (
            townValue: string | null,
            level?: string,
            barangayValue?: string,
        ) => {
            setNavigating(true);
            setFailed(null);
            try {
                await navigateDashboard(townValue, level, barangayValue);
            } catch {
                setFailed(townValue);
            } finally {
                setNavigating(false);
            }
        },
        [],
    );
    const chooseMap = useCallback(
        (code: string) => {
            if (drilling && townCode)
                void navigate(townCode, 'barangays', code);
            else void navigate(code);
        },
        [drilling, townCode, navigate],
    );
    const pending = navigating || (drilling && loaded?.code !== townCode);

    return (
        <section
            className="grid min-w-0 gap-4 xl:grid-cols-[minmax(0,1.15fr)_minmax(26rem,1fr)]"
            aria-label="Map and Selected Area"
        >
            <div
                className="relative min-h-(--workspace-map-height)"
                aria-busy={pending}
            >
                <div className="absolute inset-0">
                    {!inBrowser ? (
                        <Skeleton className="h-full w-full" />
                    ) : failed !== null ? (
                        <div
                            role="alert"
                            className="flex h-full flex-col items-center justify-center gap-3 border p-4 type-body"
                        >
                            <p>Boundary data could not load.</p>
                            <Button
                                variant="outline"
                                onClick={() =>
                                    void navigate(failed, 'barangays')
                                }
                            >
                                Try Again
                            </Button>
                        </div>
                    ) : (
                        <VisualErrorBoundary
                            fallback={
                                <p
                                    role="status"
                                    className="flex h-full items-center justify-center border bg-muted p-4 type-body"
                                >
                                    The map could not load. Use the area
                                    selectors and data panel.
                                </p>
                            }
                        >
                            <Suspense
                                fallback={
                                    <Skeleton className="h-full w-full" />
                                }
                            >
                                <AreaMap
                                    label="Economic map of Cagayan"
                                    source={
                                        drilling && loaded?.code === townCode
                                            ? {
                                                  ...loaded.source,
                                                  features:
                                                      loaded.source.features.map(
                                                          (feature) => ({
                                                              ...feature,
                                                              properties: {
                                                                  ...feature.properties,
                                                                  shade: null,
                                                              },
                                                          }),
                                                      ),
                                              }
                                            : source
                                    }
                                    selected={
                                        drilling ? barangayCode : townCode
                                    }
                                    onSelect={chooseMap}
                                />
                            </Suspense>
                        </VisualErrorBoundary>
                    )}
                </div>
            </div>
            <div className="grid min-w-0 content-start gap-4">
                <WorkspaceFilters title="Selected Area">
                    <div className="@container grid">
                        <div className="grid divide-y @min-[25rem]:grid-cols-[minmax(0,1fr)_auto] @min-[25rem]:divide-x @min-[25rem]:divide-y-0">
                            <Field
                                orientation="horizontal"
                                className="min-w-0 gap-2 @min-[25rem]:pr-3"
                            >
                                <FieldLabel
                                    htmlFor="dashboard-municipality"
                                    className="shrink-0 type-label"
                                >
                                    Municipality
                                </FieldLabel>
                                <Select
                                    value={townCode ?? 'all'}
                                    onValueChange={(code) =>
                                        navigate(code === 'all' ? null : code)
                                    }
                                >
                                    <SelectTrigger
                                        variant="quiet"
                                        size="sm"
                                        id="dashboard-municipality"
                                    >
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent align="end">
                                        <SelectItem value="all">
                                            All of Cagayan
                                        </SelectItem>
                                        {data.atlas.towns.map((item) => (
                                            <SelectItem
                                                key={item.code}
                                                value={item.code}
                                            >
                                                {item.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </Field>
                            <div className="@min-[25rem]:pl-3">
                                <FinanceMapControls
                                    finances={data.finances}
                                    {...selection}
                                    onChange={change}
                                    compact
                                />
                            </div>
                        </div>
                        {drilling && (
                            <Field
                                orientation="horizontal"
                                className="gap-2 border-t"
                            >
                                <FieldLabel
                                    htmlFor="dashboard-barangay"
                                    className="shrink-0 type-label"
                                >
                                    Barangay
                                </FieldLabel>
                                <Select
                                    value={barangayCode ?? 'all'}
                                    onValueChange={(code) =>
                                        navigate(
                                            townCode,
                                            'barangays',
                                            code === 'all' ? undefined : code,
                                        )
                                    }
                                >
                                    <SelectTrigger
                                        variant="quiet"
                                        size="sm"
                                        id="dashboard-barangay"
                                    >
                                        <SelectValue placeholder="Choose a Barangay" />
                                    </SelectTrigger>
                                    <SelectContent align="end">
                                        <SelectItem value="all">
                                            All Barangays
                                        </SelectItem>
                                        {data.barangays.map((item) => (
                                            <SelectItem
                                                key={item.code}
                                                value={item.code}
                                            >
                                                {item.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </Field>
                        )}
                        {town && !drilling && (
                            <Button
                                variant="outline"
                                size="sm"
                                className="mt-2 h-touch justify-self-end md:h-control-compact"
                                onClick={() => navigate(town.code, 'barangays')}
                            >
                                View Barangays
                            </Button>
                        )}
                    </div>
                    <div className="grid gap-2 border-t pt-2">
                        <h2 className="type-item">{shown.name}</h2>
                        <FinanceMapFigures
                            finances={data.finances}
                            code={shown.code}
                            {...selection}
                            population={shown.population}
                            aggregate={!town && !barangay}
                            barangay={drilling}
                            coverage={false}
                        />
                    </div>
                    <div className="grid grid-cols-[auto_auto_minmax(2rem,1fr)_auto] items-center gap-1.5 border-t pt-2 type-meta">
                        <span>Government Income</span>
                        <span>Low</span>
                        <div
                            className="grid grid-cols-5 gap-0.5"
                            aria-hidden="true"
                        >
                            {[
                                'bg-map-1',
                                'bg-map-2',
                                'bg-map-3',
                                'bg-map-4',
                                'bg-map-5',
                            ].map((color) => (
                                <span key={color} className={color + ' h-3'} />
                            ))}
                        </div>
                        <span>High</span>
                    </div>
                </WorkspaceFilters>
                <Surface title="Government Finances">
                    <FinanceYearsTable
                        finances={data.finances}
                        code={financeArea.code}
                        name={financeArea.name}
                    />
                </Surface>
            </div>
        </section>
    );
}
