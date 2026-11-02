import { SimulatedLabel } from '@/components/privacy/simulated-label';
import type { ReactNode } from 'react';
import { AnalyticsHelp } from '@/components/analytics/analytics-help';
import { ExecutiveSummary } from '@/components/charts/executive-summary';
import FinancePanel from '@/components/charts/finance-panel';
import { FinanceTrends } from '@/components/charts/finance-trends';
import {
    selectedBusiness,
    selectedFinance,
    selectedYear,
} from '@/lib/analytics';
import { NOT_AVAILABLE } from '@/lib/format';
import type {
    AnalyticsCatalog,
    AnalyticsFilters,
} from '@/types/economic-performance';

function Unavailable() {
    return (
        <p role="status" className="grid min-h-32 content-center type-heading">
            {NOT_AVAILABLE}
        </p>
    );
}

/**
 * Government finances for the selected town, plus business trends while simulated records are loaded. Shared by the
 * public Economy page and the Capitol Analytics page.
 */
export function AnalyticsPanels({
    catalog,
    filters,
    onChange,
    financeControls,
    businessControls,
}: {
    catalog: AnalyticsCatalog;
    filters: AnalyticsFilters;
    onChange: (patch: Partial<AnalyticsFilters>) => void;
    financeControls?: ReactNode;
    businessControls?: ReactNode;
}) {
    const finance = selectedFinance(catalog, filters);
    const business = selectedBusiness(catalog, filters);
    const panel = 'flex min-w-0 flex-col gap-2 rounded-sm border bg-card p-3';
    const shown = {
        catalog,
        presentation: filters.presentation,
        year: selectedYear(catalog, filters),
        town: filters.town,
        onYear: (year: number) => onChange({ presentation: 'summary', year }),
    };

    return (
        <>
            <section aria-labelledby="finance-heading" className={panel}>
                <div className="flex flex-wrap items-center justify-between gap-2">
                    <div className="flex items-baseline gap-3">
                        <h2 id="finance-heading" className="type-heading">
                            Government Finances
                        </h2>
                        {filters.presentation !== 'statement' &&
                            filters.measure === 'business_taxes' && (
                                <span className="type-label">General Fund</span>
                            )}
                    </div>
                    <AnalyticsHelp
                        guide={catalog.guide}
                        showBusiness={catalog.businesses !== null}
                    />
                </div>
                {financeControls}
                {filters.presentation === 'statement' ? (
                    <ExecutiveSummary
                        catalog={catalog}
                        filters={filters}
                        onChange={onChange}
                    />
                ) : finance ? (
                    <FinancePanel
                        history={finance}
                        {...shown}
                        trends={
                            <FinanceTrends
                                history={finance}
                                filters={filters}
                                onChange={onChange}
                            />
                        }
                    />
                ) : (
                    <Unavailable />
                )}
            </section>
            {catalog.businesses && (
                <section aria-labelledby="business-heading" className={panel}>
                    <div className="flex items-start justify-between gap-3">
                        <h2 id="business-heading" className="type-heading">
                            Business Trends
                        </h2>
                        <SimulatedLabel />
                    </div>
                    {businessControls}
                    {business ? (
                        <FinancePanel history={business.dataset} {...shown} />
                    ) : (
                        <Unavailable />
                    )}
                </section>
            )}
        </>
    );
}
