import { Head } from '@inertiajs/react';
import { AnalyticsPanels } from '@/components/analytics/analytics-panels';
import { AnalyticsPickers } from '@/components/analytics/analytics-pickers';
import { WorkspaceFilters } from '@/components/capitol/workspace-filters';
import { pushFilters, withCoveredMeasure } from '@/lib/analytics';
import { analytics } from '@/routes/capitol';
import type {
    AnalyticsCatalog,
    AnalyticsFilters,
} from '@/types/economic-performance';

export default function Analytics({
    catalog,
    filters,
}: {
    catalog: AnalyticsCatalog;
    filters: AnalyticsFilters;
}) {
    const change = (patch: Partial<AnalyticsFilters>) =>
        pushFilters(
            filters,
            withCoveredMeasure(catalog, filters, patch),
            (query) => analytics({ query }).url,
            (props, next) => ({ ...props, filters: next }),
        );

    return (
        <>
            <Head title="Analytics" />
            <header>
                <h1 className="type-heading">Analytics</h1>
            </header>
            <div className="grid grid-cols-1 items-start gap-4 lg:grid-cols-[minmax(0,1fr)_16rem]">
                <div className="flex min-w-0 flex-col gap-4">
                    <AnalyticsPanels
                        catalog={catalog}
                        filters={filters}
                        onChange={change}
                        figures
                    />
                </div>
                <WorkspaceFilters>
                    <AnalyticsPickers
                        idPrefix="analytics"
                        catalog={catalog}
                        filters={filters}
                        fields={['town', 'dimension', 'group']}
                        onChange={change}
                    />
                </WorkspaceFilters>
            </div>
        </>
    );
}
