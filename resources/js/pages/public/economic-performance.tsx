import { Head, Link } from '@inertiajs/react';
import { MessageSquarePlus } from 'lucide-react';
import { AnalyticsPanels } from '@/components/analytics/analytics-panels';
import { AnalyticsPickers } from '@/components/analytics/analytics-pickers';
import WishRankingChart from '@/components/charts/wish-ranking-chart';
import { LazyProvinceMap } from '@/components/lazy-province-map';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { pushFilters, withCoveredMeasure } from '@/lib/analytics';
import { economicPerformance } from '@/routes/public';
import { index as suggestions } from '@/routes/public/wishes';
import type {
    EconomicPerformance,
    PerformanceFilters,
} from '@/types/economic-performance';

export default function EconomicPerformancePage({
    performance,
}: {
    performance: EconomicPerformance;
}) {
    const { catalog, filters } = performance;
    const townName = catalog.towns.find(
        (town) => town.value === filters.town,
    )?.label;
    const wishes = performance.wishes[filters.town];
    const change = (patch: Partial<PerformanceFilters>) => {
        if (patch.town && !catalog.towns.some((t) => t.value === patch.town))
            return;
        pushFilters(
            filters,
            withCoveredMeasure(catalog, filters, patch),
            (query) => economicPerformance({ query }).url,
            (props, next) => ({
                ...props,
                performance: { ...performance, filters: next },
            }),
        );
    };

    return (
        <div className="grid gap-4">
            <Head title="Cagayan Economy" />
            <header className="flex flex-wrap items-end justify-between gap-3">
                <h1 className="type-title">Cagayan Economy</h1>
                <div className="w-full sm:w-72">
                    <AnalyticsPickers
                        idPrefix="performance"
                        catalog={catalog}
                        filters={filters}
                        fields={['town']}
                        onChange={change}
                    />
                </div>
            </header>
            <div className="grid min-w-0 items-start gap-3 lg:grid-cols-[minmax(0,1fr)_20rem]">
                <div className="flex min-w-0 flex-col gap-3">
                    <AnalyticsPanels
                        catalog={catalog}
                        filters={filters}
                        onChange={change}
                        businessControls={
                            <div className="grid gap-3 sm:grid-cols-2 lg:w-2/3">
                                <AnalyticsPickers
                                    idPrefix="performance"
                                    catalog={catalog}
                                    filters={filters}
                                    fields={['dimension', 'group']}
                                    onChange={change}
                                />
                            </div>
                        }
                    />
                </div>
                <div className="flex min-w-0 flex-col gap-3 lg:sticky lg:top-4">
                    <aside
                        aria-label="Town Explorer"
                        className="dark flex min-w-0 flex-col gap-2 rounded-sm bg-brand p-3 text-brand-foreground"
                    >
                        <div className="flex flex-wrap items-center justify-between gap-2">
                            <h2 className="type-heading">Cagayan</h2>
                            <span className="type-meta text-brand-foreground">
                                {townName}
                            </span>
                        </div>
                        <div className="h-64">
                            <LazyProvinceMap
                                mapStyle="solid"
                                label="Choose a Town in Cagayan"
                                highlight={[filters.town]}
                                onSelect={(town) => change({ town })}
                                water
                                className="size-full"
                            />
                        </div>
                    </aside>
                    <section
                        aria-labelledby="wishes-heading"
                        className="flex min-w-0 flex-col gap-2 rounded-sm border bg-card p-3"
                    >
                        <div className="flex items-start justify-between gap-3">
                            <h2 id="wishes-heading" className="type-item">
                                Community Wishes
                            </h2>
                            {wishes && (
                                <Badge
                                    variant="outline"
                                    className="ml-auto border-destructive-foreground/30 bg-destructive-foreground/10 type-pin text-destructive-foreground"
                                >
                                    {wishes.source}
                                </Badge>
                            )}
                        </div>
                        <Button asChild variant="outline" size="sm">
                            <Link
                                href={suggestions({
                                    query: { town: filters.town },
                                })}
                            >
                                <MessageSquarePlus
                                    className="size-4"
                                    aria-hidden="true"
                                />
                                Share Your Wish
                            </Link>
                        </Button>
                        {wishes ? (
                            <WishRankingChart snapshot={wishes} />
                        ) : (
                            <p role="status" className="py-4 type-meta">
                                Community wishes are not available for this town
                                yet.
                            </p>
                        )}
                    </section>
                </div>
            </div>
        </div>
    );
}
