import { Head } from '@inertiajs/react';
import { AnalyticsPanels } from '@/components/analytics/analytics-panels';
import { AnalyticsPickers } from '@/components/analytics/analytics-pickers';
import { PageHeader } from '@/components/capitol/page-header';
import {
    WorkspaceFilterLayout,
    WorkspaceFilters,
} from '@/components/capitol/workspace-filters';
import { useTownCatalog } from '@/hooks/use-town-catalog';
import { pushFilters, withCoveredMeasure } from '@/lib/analytics';
import { analytics } from '@/routes/capitol';
import type {
    AnalyticsCatalog,
    AnalyticsFilters,
} from '@/types/economic-performance';

export default function Analytics({
    catalog: pageCatalog,
    filters,
}: {
    catalog: AnalyticsCatalog;
    filters: AnalyticsFilters;
}) {
    const { catalog, status, retry } = useTownCatalog(
        pageCatalog,
        filters.town,
    );
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
            <PageHeader title="Analytics" />
            <WorkspaceFilterLayout
                results={
                    <AnalyticsPanels
                        catalog={catalog}
                        filters={filters}
                        onChange={change}
                        status={status}
                        onRetry={retry}
                        figures
                    />
                }
            >
                <WorkspaceFilters>
                    <AnalyticsPickers
                        idPrefix="analytics"
                        catalog={catalog}
                        filters={filters}
                        fields={['town', 'business']}
                        onChange={change}
                        compact
                    />
                </WorkspaceFilters>
            </WorkspaceFilterLayout>
        </>
    );
}
