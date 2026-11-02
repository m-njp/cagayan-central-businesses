import { lazy } from 'react';
import { SourceLine } from '@/components/analytics/source-line';
import { Figure } from '@/components/charts/finance-panel';
import { ObservationChange } from '@/components/charts/observation-change';
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
    'border-l px-1.5 text-right font-mono whitespace-nowrap tabular-nums sm:px-2';
const labelCell =
    'sticky left-0 z-10 h-auto min-w-36 py-1.5 font-normal whitespace-normal';
// Alternating solid shades; the sticky Item cell repeats its row's shade so scrolled years pass behind it.
const ROW_SHADES = ['bg-card hover:bg-card', 'bg-muted hover:bg-muted'];
const CELL_SHADES = ['bg-card', 'bg-muted'];

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

    // Change compares the newest year with the year before; Net Income itself carries no arrow.
    const hasChange = years.length > 1;
    const columns = years.length + (hasChange ? 2 : 1);
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
            <Table className="border type-data sm:type-body">
                <TableHeader>
                    <TableRow className="hover:bg-transparent">
                        <TableHead className={`${labelCell} bg-card`}>
                            Item
                        </TableHead>
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
                        {hasChange && (
                            <TableHead className={numberCell} scope="col">
                                Change
                            </TableHead>
                        )}
                    </TableRow>
                </TableHeader>
                <TableBody>
                    {sections.map(({ id, title, history }) => [
                        <TableRow key={id} className="hover:bg-transparent">
                            <TableHead
                                scope="colgroup"
                                colSpan={columns}
                                className="sticky left-0 h-auto bg-card pt-3 pb-1 type-item"
                            >
                                {title}
                            </TableHead>
                        </TableRow>,
                        ...history.metrics.map((metric, index) => {
                            const note = rowNote(history, metric.id);
                            const latest = history.rows.at(-1);
                            return (
                                <TableRow
                                    key={metric.id}
                                    className={ROW_SHADES[index % 2]}
                                >
                                    <TableHead
                                        scope="row"
                                        className={`${labelCell} ${CELL_SHADES[index % 2]}`}
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
                                    {hasChange && (
                                        <TableCell className={numberCell}>
                                            {metric.id !== RESULT_METRIC && (
                                                <ObservationChange
                                                    point={
                                                        latest?.observations[
                                                            metric.id
                                                        ]
                                                    }
                                                    unit={metric.unit}
                                                />
                                            )}
                                        </TableCell>
                                    )}
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
                                    history={results.history}
                                    bars
                                    syncId="finance-summary"
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
                                syncId="finance-summary"
                            />
                        </PerformanceVisual>
                    </section>
                )}
            </div>
            <SourceLine links={links} town={filters.town} />
        </div>
    );
}
