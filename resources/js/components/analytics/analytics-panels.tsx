import { SimulatedLabel } from '@/components/privacy/simulated-label';
import type { ReactNode } from 'react';
import { FinanceToolbar } from '@/components/analytics/finance-toolbar';
import { ExecutiveSummary } from '@/components/charts/executive-summary';
import FinancePanel, { Figure } from '@/components/charts/finance-panel';
import { FinanceTrends } from '@/components/charts/finance-trends';
import { RESULT_METRIC } from '@/components/charts/trend-mark';
import {
    latestYear,
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

const STRIP = [
    ['totals', 'performance.revenue'],
    ['totals', 'performance.expenses'],
    ['totals', RESULT_METRIC],
    ['position', 'position.assets'],
] as const;

/** Capitol only: the newest reported year's headline figures with the change from the year before. */
function FigureStrip({
    catalog,
    town,
}: {
    catalog: AnalyticsCatalog;
    town: string;
}) {
    const histories = catalog.finances[town];
    const year = histories?.totals ? latestYear(histories.totals) : null;
    if (!histories || year === null) return null;
    const figures = STRIP.flatMap(([group, id]) => {
        const history = histories[group];
        const metric = history?.metrics.find((item) => item.id === id);
        const point = history?.rows.find((row) => row.year === year)
            ?.observations[id];
        return metric ? [{ metric, point }] : [];
    });
    return (
        <dl className="grid grid-cols-2 gap-px overflow-hidden rounded-sm border bg-border sm:grid-cols-[auto_repeat(4,minmax(0,1fr))]">
            <div className="col-span-2 flex items-center bg-card px-3 py-2 sm:col-span-1">
                <dt className="sr-only">Fiscal Year</dt>
                <dd className="font-mono type-item tabular-nums">{year}</dd>
            </div>
            {figures.map(({ metric, point }) => (
                <div
                    key={metric.id}
                    className="flex min-w-0 flex-col gap-0.5 bg-card px-3 py-2"
                >
                    <dt className="type-meta">{metric.label}</dt>
                    <dd className="flex flex-wrap items-baseline gap-x-2 font-mono type-item tabular-nums">
                        <Figure
                            point={point}
                            metric={metric}
                            alwaysShort
                            withChange
                        />
                    </dd>
                </div>
            ))}
        </dl>
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
    businessControls,
    figures = false,
}: {
    catalog: AnalyticsCatalog;
    filters: AnalyticsFilters;
    onChange: (patch: Partial<AnalyticsFilters>) => void;
    businessControls?: ReactNode;
    figures?: boolean;
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
                    <FinanceToolbar
                        catalog={catalog}
                        filters={filters}
                        onChange={onChange}
                    />
                </div>
                {figures && (
                    <FigureStrip catalog={catalog} town={filters.town} />
                )}
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
                                measure={filters.measure}
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
