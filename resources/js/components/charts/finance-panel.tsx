import { lazy } from 'react';
import type { ReactNode } from 'react';
import { SourceLine } from '@/components/analytics/source-line';
import { ExactAmount } from '@/components/charts/exact-amount';
import PerformanceVisual from '@/components/charts/performance-visual';
import { RESULT_METRIC, TrendMark } from '@/components/charts/trend-mark';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { latestYear } from '@/lib/analytics';
import {
    changeLabel,
    citationReports,
    observationLabel,
    observationNote,
    percentChangeLabel,
} from '@/lib/performance-format';
import { trendOf } from '@/lib/trend';
import type {
    AnalyticsCatalog,
    AnalyticsFilters,
    EconomicMetric,
    EconomicObservation,
    FinancialHistory,
} from '@/types/economic-performance';

const FinanceHistoryChart = lazy(
    () => import('@/components/charts/finance-history-chart'),
);

const numberCell = 'px-1.5 text-right font-mono tabular-nums sm:px-2';
const phone = 'type-data sm:type-body';

/**
 * The exact figure from tablet width up and the short form on a phone, which shows the exact figure on hover, focus or
 * tap. Trends always uses the short form so several years fit. A negative amount reads in parentheses.
 */
export function Figure({
    point,
    metric,
    alwaysShort = false,
    withChange = false,
}: {
    point?: EconomicObservation;
    metric: EconomicMetric;
    alwaysShort?: boolean;
    withChange?: boolean;
}) {
    const exact = observationLabel(point, metric.unit);
    const short = observationLabel(point, metric.unit, true);
    // One tooltip for the amount and its change; Net Income carries no arrow.
    const trend =
        withChange && metric.id !== RESULT_METRIC && point?.comparable
            ? trendOf(point.change)
            : null;
    if (trend && point) {
        // Accounting style: a fall reads in parentheses, a rise as a plain amount; no words or plus sign.
        const percent = percentChangeLabel(point, metric.unit);
        const change = changeLabel(point, metric.unit);
        return (
            <ExactAmount
                exact={exact}
                detail={`Change from ${point.comparisonYear}: ${change}${percent ? `, ${percent}` : ''}`}
            >
                <span className="inline-flex items-center gap-1">
                    <span className="hidden sm:inline">
                        {alwaysShort ? short : exact}
                    </span>
                    <span className="sm:hidden">{short}</span>
                    <span className={trend.tone}>
                        <TrendMark trend={trend} />
                    </span>
                </span>
            </ExactAmount>
        );
    }
    if (exact === short) return <>{exact}</>;
    if (alwaysShort) return <ExactAmount exact={exact}>{short}</ExactAmount>;
    return (
        <>
            <span className="hidden sm:inline">{exact}</span>
            <span className="sm:hidden">
                <ExactAmount exact={exact}>{short}</ExactAmount>
            </span>
        </>
    );
}

/**
 * One ledger table for a series: the selected fiscal year beside the year before with the change, or every year of
 * a period in Trends. The citations are one Source line under the table, not a column (design.md, "Government
 * Finances Ledger Table").
 */
export default function FinancePanel({
    history,
    catalog,
    presentation,
    year,
    town,
    onYear,
    trends,
}: {
    history: FinancialHistory;
    catalog: AnalyticsCatalog;
    presentation: AnalyticsFilters['presentation'];
    year: number | null;
    town: string;
    onYear: (year: number) => void;
    trends?: ReactNode;
}) {
    const annualYear = year ?? latestYear(history);
    const annual = history.rows.find((row) => row.year === annualYear);
    const previous = history.rows.find(
        (row) => row.year === (annualYear ?? 0) - 1,
    );
    const plotted = history.rows.some((row) =>
        Object.values(row.observations).some(
            (point) => point.state === 'shown' && point.value !== null,
        ),
    );

    if (history.rows.length === 0 || history.metrics.length === 0)
        return (
            <p role="status" className="py-6 type-body">
                No observations are available for this period.
            </p>
        );

    if (presentation === 'trends')
        return (
            <div className="flex min-w-0 flex-col gap-2">
                {trends ??
                    (plotted && (
                        <PerformanceVisual>
                            <FinanceHistoryChart history={history} />
                        </PerformanceVisual>
                    ))}
                <Table data-striped className={phone}>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Item</TableHead>
                            {history.rows.map((row) => (
                                <TableHead
                                    key={row.year}
                                    className={numberCell}
                                    scope="col"
                                >
                                    <button
                                        type="button"
                                        className="min-h-8 underline underline-offset-2"
                                        onClick={() => onYear(row.year)}
                                    >
                                        {row.year}
                                        <span className="sr-only">
                                            {' '}
                                            Yearly Summary
                                        </span>
                                    </button>
                                </TableHead>
                            ))}
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {history.metrics.map((metric) => (
                            <TableRow key={metric.id}>
                                <TableHead
                                    scope="row"
                                    className="h-auto py-1.5 font-normal whitespace-normal"
                                >
                                    {metric.label}
                                </TableHead>
                                {history.rows.map((row) => (
                                    <TableCell
                                        key={row.year}
                                        className={numberCell}
                                    >
                                        <Figure
                                            point={row.observations[metric.id]}
                                            metric={metric}
                                            alwaysShort
                                            withChange
                                        />
                                    </TableCell>
                                ))}
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>
                {history.note && <p className="type-meta">{history.note}</p>}
            </div>
        );

    if (annualYear === null || !annual)
        return (
            <p role="status" className="py-6 type-body">
                Not available for this fiscal year.
            </p>
        );

    const notes = history.metrics.map((metric) =>
        observationNote(annual.observations[metric.id]),
    );
    const sharedNote =
        notes[0] && notes.every((note) => note === notes[0]) ? notes[0] : null;
    const citations = citationReports(
        catalog.sources,
        history.metrics.flatMap((metric) =>
            [annual, previous].flatMap(
                (row) => row?.observations[metric.id]?.sourceRefs ?? [],
            ),
        ),
    );

    return (
        <div className="flex min-w-0 flex-col gap-2">
            <Table data-striped className={phone}>
                <TableHeader>
                    <TableRow>
                        <TableHead>Item</TableHead>
                        <TableHead className={numberCell}>
                            {annualYear - 1}
                        </TableHead>
                        <TableHead className={numberCell}>
                            {annualYear}
                        </TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    {history.metrics.map((metric, index) => {
                        const point = annual.observations[metric.id];
                        return (
                            <TableRow key={metric.id}>
                                <TableHead
                                    scope="row"
                                    className="h-auto py-1.5 font-normal whitespace-normal"
                                >
                                    {metric.label}
                                    {notes[index] &&
                                        notes[index] !== sharedNote && (
                                            <span className="block type-data text-muted-foreground">
                                                {notes[index]}
                                            </span>
                                        )}
                                </TableHead>
                                <TableCell className={numberCell}>
                                    <Figure
                                        point={
                                            previous?.observations[metric.id]
                                        }
                                        metric={metric}
                                    />
                                </TableCell>
                                <TableCell className={numberCell}>
                                    <Figure
                                        point={point}
                                        metric={metric}
                                        withChange
                                    />
                                </TableCell>
                            </TableRow>
                        );
                    })}
                </TableBody>
            </Table>
            {sharedNote && <p className="type-meta">{sharedNote}</p>}
            {history.note && <p className="type-meta">{history.note}</p>}
            <SourceLine citations={citations} town={town} />
        </div>
    );
}
