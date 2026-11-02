import {
    Bar,
    CartesianGrid,
    ComposedChart,
    Line,
    ReferenceLine,
    XAxis,
    YAxis,
} from 'recharts';
import { FinanceTooltip } from '@/components/charts/finance-tooltip';
import { RESULT_METRIC } from '@/components/charts/trend-mark';
import { ChartContainer, ChartTooltip } from '@/components/ui/chart';
import { financeChartSeries } from '@/lib/finance-chart';
import { formatMetricTick } from '@/lib/performance-format';
import type {
    EconomicObservation,
    FinancialHistory,
} from '@/types/economic-performance';

function amount(point?: EconomicObservation): number | null {
    return point?.state === 'shown' && point.value !== null
        ? Number(point.value)
        : null;
}

/**
 * Annual series as lines, Net Income alone as bars (`result`), or Income and Expenses as paired bars with Net Income as
 * a line over the zero line (`bars`, Results of Operation). A missing year stays a gap, never a zero.
 */
export default function FinanceHistoryChart({
    history,
    result = false,
    bars = false,
    legend = true,
    syncId = 'finance-trends',
}: {
    history: FinancialHistory;
    result?: boolean;
    bars?: boolean;
    legend?: boolean;
    syncId?: string;
}) {
    const resultMetric = bars
        ? history.metrics.find((metric) => metric.id === RESULT_METRIC)
        : undefined;
    const metrics = history.metrics.filter(
        (metric) => !bars || metric.id !== RESULT_METRIC,
    );
    const unit = metrics[0]?.unit ?? 'php';
    const segments = financeChartSeries({ ...history, metrics });
    const color = (index: number) => `var(--chart-${(index % 5) + 1})`;
    const resultColor = 'var(--foreground)';
    const series = [
        ...metrics.map((metric, index) => ({ metric, color: color(index) })),
        ...(resultMetric ? [{ metric: resultMetric, color: resultColor }] : []),
    ];
    const config = Object.fromEntries(
        metrics.map((metric, index) => [
            `series${index}`,
            { label: metric.label, color: color(index) },
        ]),
    );
    const data = history.rows.map((row, position) => ({
        year: row.year,
        result: amount(row.observations[RESULT_METRIC]),
        ...Object.fromEntries(
            metrics.map((metric, index) => [
                `bar${index}`,
                amount(row.observations[metric.id]),
            ]),
        ),
        ...Object.fromEntries(
            segments.map((segment, index) => [
                `segment${index}`,
                segment.values[position],
            ]),
        ),
    }));
    const zeroLine = result || resultMetric !== undefined;
    return (
        <div className="flex min-w-0 flex-col gap-2">
            <ChartContainer
                config={config}
                className={`aspect-auto w-full font-mono tabular-nums ${result ? 'h-32 sm:h-36' : 'h-48 sm:h-56'}`}
                role="group"
                aria-label={`${series.map(({ metric }) => metric.label).join(' and ')} by year. Exact values are listed in the table.`}
            >
                <ComposedChart
                    data={data}
                    margin={{ top: 8, right: 8, bottom: 0, left: 0 }}
                    accessibilityLayer
                    syncId={syncId}
                    barGap={2}
                    barCategoryGap="28%"
                >
                    <CartesianGrid vertical={false} stroke="var(--border)" />
                    <XAxis
                        dataKey="year"
                        type={bars ? 'category' : 'number'}
                        domain={
                            bars
                                ? undefined
                                : history.rows.length === 1
                                  ? [
                                        history.rows[0].year - 0.5,
                                        history.rows[0].year + 0.5,
                                    ]
                                  : ['dataMin', 'dataMax']
                        }
                        ticks={
                            bars
                                ? undefined
                                : history.rows.map((row) => row.year)
                        }
                        tickLine={false}
                        axisLine={false}
                        tickMargin={12}
                        minTickGap={bars ? 0 : 24}
                        padding={bars ? undefined : { left: 20, right: 20 }}
                    />
                    <YAxis
                        domain={[
                            (lowest: number) => Math.min(0, lowest),
                            (highest: number) => Math.max(0, highest),
                        ]}
                        width={64}
                        tickFormatter={(value: number) =>
                            formatMetricTick(value, unit)
                        }
                        tickLine={false}
                        axisLine={false}
                    />
                    {zeroLine && (
                        <ReferenceLine y={0} stroke="var(--muted-foreground)" />
                    )}
                    <ChartTooltip
                        cursor={
                            bars
                                ? { fill: 'var(--muted)' }
                                : {
                                      stroke: 'var(--muted-foreground)',
                                      strokeDasharray: '3 3',
                                  }
                        }
                        content={({ active, label }) =>
                            active ? (
                                <FinanceTooltip
                                    history={history}
                                    label={label}
                                    series={series}
                                />
                            ) : null
                        }
                    />
                    {result && !bars && (
                        <Bar
                            dataKey="result"
                            name={metrics[0]?.label}
                            fill="var(--color-series0)"
                            maxBarSize={32}
                            isAnimationActive={false}
                        />
                    )}
                    {bars &&
                        metrics.map((metric, index) => (
                            <Bar
                                key={metric.id}
                                dataKey={`bar${index}`}
                                name={metric.label}
                                fill={`var(--color-series${index})`}
                                maxBarSize={28}
                                isAnimationActive={false}
                            />
                        ))}
                    {resultMetric && (
                        <Line
                            dataKey="result"
                            name={resultMetric.label}
                            type="linear"
                            stroke={resultColor}
                            strokeWidth={2}
                            dot={{ r: 3, fill: 'var(--card)' }}
                            activeDot={{ r: 5 }}
                            connectNulls={false}
                            isAnimationActive={false}
                        />
                    )}
                    {!result &&
                        !bars &&
                        segments.map((segment, index) => (
                            <Line
                                key={index}
                                dataKey={`segment${index}`}
                                name={segment.metric.label}
                                type="linear"
                                stroke={`var(--color-series${segment.index})`}
                                strokeWidth={3}
                                strokeDasharray={
                                    segment.index % 2 ? '6 4' : undefined
                                }
                                dot={{
                                    r: 3,
                                    fill: 'var(--card)',
                                    strokeDasharray: '0',
                                }}
                                activeDot={{ r: 5 }}
                                connectNulls={false}
                                isAnimationActive={false}
                            />
                        ))}
                </ComposedChart>
            </ChartContainer>
            {legend && series.length > 1 && (
                <ul
                    aria-label="Legend"
                    className="flex flex-wrap justify-center gap-x-4 gap-y-1"
                >
                    {series.map(({ metric, color: swatch }, index) => (
                        <li
                            key={metric.id}
                            className="flex items-center gap-2 type-meta"
                        >
                            {bars && metric.id !== RESULT_METRIC ? (
                                <span
                                    aria-hidden="true"
                                    className="size-2.5 rounded-sm"
                                    style={{ background: swatch }}
                                />
                            ) : (
                                <svg width="24" height="8" aria-hidden="true">
                                    <line
                                        x1="0"
                                        y1="4"
                                        x2="24"
                                        y2="4"
                                        stroke={swatch}
                                        strokeWidth="3"
                                        strokeDasharray={
                                            !bars && index % 2
                                                ? '6 4'
                                                : undefined
                                        }
                                    />
                                </svg>
                            )}
                            {metric.label}
                        </li>
                    ))}
                </ul>
            )}
        </div>
    );
}
