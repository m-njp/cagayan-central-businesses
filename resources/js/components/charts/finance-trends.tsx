import { lazy } from 'react';
import PerformanceVisual from '@/components/charts/performance-visual';
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group';
import type {
    AnalyticsFilters,
    FinancialHistory,
} from '@/types/economic-performance';
import type { FinanceChartVariant } from '@/components/charts/finance-history-chart';

const FinanceHistoryChart = lazy(
    () => import('@/components/charts/finance-history-chart'),
);
const FinancialConditionChart = lazy(
    () => import('@/components/charts/financial-condition-chart'),
);

// One chart per measure, chosen for how its lines relate (design.md, "Finance Charts").
const VARIANTS: Record<string, FinanceChartVariant> = {
    totals: 'outcome',
    budget: 'bars',
};

/** 3Y, 5Y and All in the report header; the years themselves show only in column headings and axes. */
export function PeriodToggle({
    filters,
    onChange,
}: {
    filters: AnalyticsFilters;
    onChange: (patch: Partial<AnalyticsFilters>) => void;
}) {
    const item = 'h-9 min-w-8 px-1.5 font-mono type-data';
    return (
        <ToggleGroup
            type="single"
            variant="outline"
            size="sm"
            value={filters.presentation === 'summary' ? '' : filters.period}
            aria-label="Period"
            onValueChange={(period) => {
                if (period)
                    onChange({
                        period: period as AnalyticsFilters['period'],
                        presentation:
                            filters.presentation === 'summary'
                                ? 'statement'
                                : filters.presentation,
                    });
            }}
        >
            {filters.period === '2y' && (
                <ToggleGroupItem value="2y" className={item}>
                    2Y
                </ToggleGroupItem>
            )}
            {filters.period === 'recent' && (
                <ToggleGroupItem value="recent" className={item}>
                    Recent
                </ToggleGroupItem>
            )}
            <ToggleGroupItem value="3y" className={item}>
                3Y
            </ToggleGroupItem>
            <ToggleGroupItem value="5y" className={item}>
                5Y
            </ToggleGroupItem>
            <ToggleGroupItem value="all" className={item}>
                All
            </ToggleGroupItem>
        </ToggleGroup>
    );
}

/** The Charts view: one chart for the whole measure above its table, never a grid of figure tiles. */
export function FinanceTrends({
    history,
    measure,
}: {
    history: FinancialHistory;
    measure: string;
}) {
    const plotted = history.rows.some((row) =>
        history.metrics.some(
            (metric) =>
                row.observations[metric.id]?.state === 'shown' &&
                row.observations[metric.id]?.value !== null,
        ),
    );
    if (!plotted)
        return (
            <p role="status" className="py-6 type-body">
                Not available for this period.
            </p>
        );
    return (
        <PerformanceVisual>
            {measure === 'position' ? (
                <FinancialConditionChart history={history} />
            ) : (
                <FinanceHistoryChart
                    history={history}
                    variant={VARIANTS[measure] ?? 'lines'}
                />
            )}
        </PerformanceVisual>
    );
}
