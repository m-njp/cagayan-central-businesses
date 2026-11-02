import { lazy } from 'react';
import { ExactAmount } from '@/components/charts/exact-amount';
import { ObservationChange } from '@/components/charts/observation-change';
import PerformanceVisual from '@/components/charts/performance-visual';
import { RESULT_METRIC } from '@/components/charts/trend-mark';
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group';
import { focusedFinance } from '@/lib/finance-chart';
import { observationLabel } from '@/lib/performance-format';
import type {
    AnalyticsFilters,
    FinancialHistory,
} from '@/types/economic-performance';

const FinanceHistoryChart = lazy(
    () => import('@/components/charts/finance-history-chart'),
);

/** The displayed year range beside 3Y, 5Y and Max, shared by Trends and the Executive Summary. */
export function PeriodToggle({
    history,
    filters,
    onChange,
}: {
    history: FinancialHistory;
    filters: AnalyticsFilters;
    onChange: (patch: Partial<AnalyticsFilters>) => void;
}) {
    return (
        <div className="flex flex-wrap items-center justify-between gap-2">
            <span className="type-label">
                {history.rows[0]?.year}
                {history.rows.length > 1 && ` to ${history.rows.at(-1)?.year}`}
            </span>
            <ToggleGroup
                type="single"
                variant="outline"
                value={filters.period}
                aria-label="Finance Period"
                onValueChange={(period) => {
                    if (period)
                        onChange({
                            period: period as AnalyticsFilters['period'],
                        });
                }}
            >
                {filters.period === '2y' && (
                    <ToggleGroupItem value="2y" className="min-h-8 px-3">
                        2Y
                    </ToggleGroupItem>
                )}
                {filters.period === 'recent' && (
                    <ToggleGroupItem value="recent" className="min-h-8 px-3">
                        Recent
                    </ToggleGroupItem>
                )}
                <ToggleGroupItem value="3y" className="min-h-8 px-3">
                    3Y
                </ToggleGroupItem>
                <ToggleGroupItem value="5y" className="min-h-8 px-3">
                    5Y
                </ToggleGroupItem>
                <ToggleGroupItem value="all" className="min-h-8 px-3">
                    Max
                </ToggleGroupItem>
            </ToggleGroup>
        </div>
    );
}

export function FinanceTrends({
    history,
    filters,
    onChange,
}: {
    history: FinancialHistory;
    filters: AnalyticsFilters;
    onChange: (patch: Partial<AnalyticsFilters>) => void;
}) {
    const totals = filters.measure === 'totals';
    const selected = focusedFinance(history, filters.metric);
    const latest = history.rows.at(-1);
    const plotted = {
        ...history,
        metrics: history.metrics.filter((metric) =>
            totals ? metric.id !== RESULT_METRIC : metric.id === selected?.id,
        ),
    };
    const result = {
        ...history,
        metrics: history.metrics.filter(
            (metric) => metric.id === RESULT_METRIC,
        ),
    };
    const hasValues = plotted.rows.some((row) =>
        plotted.metrics.some(
            (metric) =>
                row.observations[metric.id]?.state === 'shown' &&
                row.observations[metric.id]?.value !== null,
        ),
    );
    return (
        <div className="flex min-w-0 flex-col gap-3">
            <PeriodToggle
                history={history}
                filters={filters}
                onChange={onChange}
            />
            <div className="grid gap-3 sm:grid-cols-2">
                {(totals ? plotted.metrics : history.metrics).map(
                    (metric, index) => {
                        const point = latest?.observations[metric.id];
                        const exact = observationLabel(point, metric.unit);
                        return (
                            <div
                                key={metric.id}
                                className="flex min-w-0 flex-col items-start gap-1"
                            >
                                {totals ? (
                                    <span className="flex items-center gap-2 type-body">
                                        <svg
                                            width="24"
                                            height="8"
                                            aria-hidden="true"
                                        >
                                            <line
                                                x1="0"
                                                y1="4"
                                                x2="24"
                                                y2="4"
                                                stroke={`var(--chart-${index + 1})`}
                                                strokeWidth="3"
                                                strokeDasharray={
                                                    index ? '6 4' : undefined
                                                }
                                            />
                                        </svg>
                                        {metric.label}
                                    </span>
                                ) : (
                                    <button
                                        type="button"
                                        aria-pressed={
                                            selected?.id === metric.id
                                        }
                                        onClick={() =>
                                            onChange({ metric: metric.id })
                                        }
                                        className="min-h-8 text-left type-body underline-offset-4 hover:underline focus-visible:outline-2 focus-visible:outline-ring aria-pressed:font-semibold aria-pressed:underline"
                                    >
                                        {metric.label}
                                    </button>
                                )}
                                <span
                                    className={
                                        totals || selected?.id === metric.id
                                            ? 'type-figure'
                                            : 'font-mono type-item tabular-nums'
                                    }
                                >
                                    <ExactAmount exact={exact}>
                                        {observationLabel(
                                            point,
                                            metric.unit,
                                            true,
                                        )}
                                    </ExactAmount>
                                </span>
                                <div className="flex flex-wrap items-center gap-2 type-meta">
                                    <span>{latest?.year}</span>
                                    <ObservationChange
                                        point={point}
                                        unit={metric.unit}
                                        markOnly
                                    />
                                    {point?.comparable && (
                                        <span>from {point.comparisonYear}</span>
                                    )}
                                </div>
                            </div>
                        );
                    },
                )}
            </div>
            {hasValues ? (
                <PerformanceVisual>
                    <FinanceHistoryChart history={plotted} legend={false} />
                </PerformanceVisual>
            ) : (
                <p role="status" className="py-6 type-body">
                    Not available for this period.
                </p>
            )}
            {totals && result.metrics.length > 0 && (
                <div className="flex flex-col gap-2 border-t pt-3">
                    <div className="flex flex-wrap items-baseline justify-between gap-2">
                        <h3 className="type-item">{result.metrics[0].label}</h3>
                        <span className="font-mono type-item tabular-nums">
                            <ExactAmount
                                exact={observationLabel(
                                    latest?.observations[RESULT_METRIC],
                                    'php',
                                )}
                            >
                                {observationLabel(
                                    latest?.observations[RESULT_METRIC],
                                    'php',
                                    true,
                                )}
                            </ExactAmount>
                        </span>
                    </div>
                    <PerformanceVisual>
                        {result.rows.some(
                            (row) =>
                                row.observations[RESULT_METRIC]?.state ===
                                    'shown' &&
                                row.observations[RESULT_METRIC]?.value !== null,
                        ) ? (
                            <FinanceHistoryChart
                                history={result}
                                result
                                legend={false}
                            />
                        ) : (
                            <p role="status" className="py-6 type-body">
                                Not available for this period.
                            </p>
                        )}
                    </PerformanceVisual>
                </div>
            )}
        </div>
    );
}
