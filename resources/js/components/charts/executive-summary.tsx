import { lazy } from 'react';
import { SourceLine } from '@/components/analytics/source-line';
import { Figure } from '@/components/charts/finance-panel';
import { PeriodToggle } from '@/components/charts/finance-trends';
import PerformanceVisual from '@/components/charts/performance-visual';
import { RESULT_METRIC } from '@/components/charts/trend-mark';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { executiveSummary } from '@/lib/analytics';
import { citationLinks, observationNote } from '@/lib/performance-format';
import type {
    AnalyticsCatalog,
    AnalyticsFilters,
    FinancialHistory,
} from '@/types/economic-performance';

const FinanceHistoryChart = lazy(
    () => import('@/components/charts/finance-history-chart'),
);
const FinancialConditionChart = lazy(
    () => import('@/components/charts/financial-condition-chart'),
);

const numberCell =
    'px-1.5 text-right font-mono whitespace-nowrap tabular-nums sm:px-2';
const labelCell =
    'sticky left-0 z-10 h-auto min-w-36 bg-card py-1.5 font-normal whitespace-normal';

/** The note every cell of a row shares, such as a line loaded for one town only; none when the cells differ. */
function rowNote(history: FinancialHistory, metricId: string): string | null {
    const notes = history.rows.map((row) =>
        observationNote(row.observations[metricId]),
    );
    return notes[0] && notes.every((note) => note === notes[0])
        ? notes[0]
        : null;
}

function plotted(history: FinancialHistory, ids?: string[]): boolean {
    return history.rows.some((row) =>
        history.metrics.some(
            (metric) =>
                (!ids || ids.includes(metric.id)) &&
                row.observations[metric.id]?.state === 'shown' &&
                row.observations[metric.id]?.value !== null,
        ),
    );
}

/**
 * The Executive Summary as COA prints it: Financial Condition, Results of Operation and Budget Execution, one row per
 * line and one column per fiscal year (design.md, "Executive Summary View"). Every figure is already loaded, so the
 * period and the year links change only local history.
 */
export function ExecutiveSummary({
    catalog,
    filters,
    onChange,
}: {
    catalog: AnalyticsCatalog;
    filters: AnalyticsFilters;
    onChange: (patch: Partial<AnalyticsFilters>) => void;
}) {
    const sections = executiveSummary(catalog, filters);
    const years = sections[0]?.history.rows.map((row) => row.year) ?? [];
    // Exact amounts fit three years; longer periods use the short form with the exact amount on hover, as Trends does.
    const exactFits = years.length <= 3;
    if (years.length === 0)
        return (
            <p role="status" className="py-6 type-body">
                Not available for this period.
            </p>
        );

    const results = sections.find((section) => section.id === 'totals');
    const position = sections.find((section) => section.id === 'position');
    // Reports in year order, then page order, however the sections cite them.
    const links = citationLinks(
        catalog.sources,
        sections.flatMap(({ history }) =>
            history.rows.flatMap((row) =>
                Object.values(row.observations).flatMap(
                    (point) => point.sourceRefs ?? [],
                ),
            ),
        ),
    ).sort((first, second) =>
        first.text.localeCompare(second.text, undefined, { numeric: true }),
    );

    return (
        <div className="flex min-w-0 flex-col gap-3">
            <PeriodToggle
                history={sections[0].history}
                filters={filters}
                onChange={onChange}
            />
            <Table className="type-data sm:type-body">
                <TableHeader>
                    <TableRow>
                        <TableHead className={labelCell}>Item</TableHead>
                        {years.map((year) => (
                            <TableHead
                                key={year}
                                className={numberCell}
                                scope="col"
                            >
                                <button
                                    type="button"
                                    className="min-h-8 underline underline-offset-2"
                                    onClick={() =>
                                        onChange({
                                            presentation: 'summary',
                                            year,
                                        })
                                    }
                                >
                                    {year}
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
                    {sections.map(({ id, title, history }) => [
                        <TableRow key={id} className="hover:bg-transparent">
                            <TableHead
                                scope="colgroup"
                                colSpan={years.length + 1}
                                className="sticky left-0 h-auto bg-card pt-3 pb-1 type-label"
                            >
                                {title}
                            </TableHead>
                        </TableRow>,
                        ...history.metrics.map((metric) => {
                            const note = rowNote(history, metric.id);
                            return (
                                <TableRow key={metric.id}>
                                    <TableHead
                                        scope="row"
                                        className={labelCell}
                                    >
                                        {metric.label}
                                        {note && (
                                            <span className="block type-data text-muted-foreground">
                                                {note}
                                            </span>
                                        )}
                                    </TableHead>
                                    {history.rows.map((row) => (
                                        <TableCell
                                            key={row.year}
                                            className={numberCell}
                                        >
                                            <Figure
                                                point={
                                                    row.observations[metric.id]
                                                }
                                                metric={metric}
                                                alwaysShort={!exactFits}
                                            />
                                        </TableCell>
                                    ))}
                                </TableRow>
                            );
                        }),
                    ])}
                </TableBody>
            </Table>
            <div className="grid gap-3 lg:grid-cols-2">
                {results &&
                    plotted(results.history, [
                        'performance.revenue',
                        'performance.expenses',
                    ]) && (
                        <section className="flex min-w-0 flex-col gap-2">
                            <h3 className="type-item">{results.title}</h3>
                            <PerformanceVisual>
                                <FinanceHistoryChart
                                    history={{
                                        ...results.history,
                                        metrics: results.history.metrics.filter(
                                            (metric) =>
                                                metric.id !== RESULT_METRIC,
                                        ),
                                    }}
                                />
                            </PerformanceVisual>
                        </section>
                    )}
                {position && plotted(position.history) && (
                    <section className="flex min-w-0 flex-col gap-2">
                        <h3 className="type-item">{position.title}</h3>
                        <PerformanceVisual>
                            <FinancialConditionChart
                                history={position.history}
                            />
                        </PerformanceVisual>
                    </section>
                )}
            </div>
            <SourceLine links={links} town={filters.town} />
        </div>
    );
}
