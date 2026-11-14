import {
    Area,
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

/**
 * How a series reads (design.md, "Finance Charts"):
 * - lines: separate amounts, such as business taxes; definition changes break the line.
 * - outcome: Income and Expenses as overlapping areas with Net Income as a line over zero (Results of Operation).
 * - bars: amounts compared side by side each year, such as appropriations, allotments and obligations.
 * - result: Net Income alone as bars.
 */
export type FinanceChartVariant = 'lines' | 'outcome' | 'bars' | 'result';

function amount(point?: EconomicObservation): number | null {
    return point?.state === 'shown' && point.value !== null
        ? Number(point.value)
        : null;
}

const contrast = (index: number) => `var(--chart-${(index % 5) + 1})`;
const RESULT_COLOR = 'var(--foreground)';

/** Annual finance series; a missing year stays a gap, never a zero. */
export default function FinanceHistoryChart({
    history,
    variant = 'lines',
}: {
    history: FinancialHistory;
    variant?: FinanceChartVariant;
}) {
    const resultMetric =
        variant === 'outcome' || variant === 'result'
            ? history.metrics.find((metric) => metric.id === RESULT_METRIC)
            : undefined;
    const metrics =
        variant === 'result'
            ? []
            : history.metrics.filter((metric) => metric.id !== RESULT_METRIC);
    const unit = (metrics[0] ?? history.metrics[0])?.unit ?? 'php';
    const color = contrast;
    const segments =
        variant === 'lines' ? financeChartSeries({ ...history, metrics }) : [];
    const series = [
        ...metrics.map((metric, index) => ({ metric, color: color(index) })),
        ...(resultMetric
            ? [
                  {
                      metric: resultMetric,
                      color: variant === 'result' ? color(0) : RESULT_COLOR,
                  },
              ]
            : []),
    ];
    const data = history.rows.map((row, position) => ({
        year: row.year,
        result: amount(row.observations[RESULT_METRIC]),
        ...Object.fromEntries(
            metrics.map((metric, index) => [
                `value${index}`,
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
    const category = variant === 'bars' || variant === 'result';
    return (
        <div className="flex min-w-0 flex-col gap-2">
            <ChartContainer
                className={`aspect-auto w-full font-mono tabular-nums ${variant === 'result' ? 'h-32 sm:h-36' : 'h-48 sm:h-56'}`}
                role="group"
                aria-label={`${series.map(({ metric }) => metric.label).join(' and ')} by year`}
            >
                <ComposedChart
                    data={data}
                    margin={{ top: 8, right: 8, bottom: 0, left: 0 }}
                    accessibilityLayer
                    barGap={2}
                    barCategoryGap="24%"
                >
                    <CartesianGrid vertical={false} stroke="var(--border)" />
                    <XAxis
                        dataKey="year"
                        type={category ? 'category' : 'number'}
                        domain={
                            category
                                ? undefined
                                : history.rows.length === 1
                                  ? [
                                        history.rows[0].year - 0.5,
                                        history.rows[0].year + 0.5,
                                    ]
                                  : ['dataMin', 'dataMax']
                        }
                        ticks={
                            category
                                ? undefined
                                : history.rows.map((row) => row.year)
                        }
                        tickLine={false}
                        axisLine={false}
                        tickMargin={12}
                        minTickGap={category ? 0 : 24}
                        padding={category ? undefined : { left: 16, right: 16 }}
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
                    {resultMetric && (
                        <ReferenceLine y={0} stroke="var(--muted-foreground)" />
                    )}
                    <ChartTooltip
                        cursor={
                            category
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
                    {variant === 'result' && (
                        <Bar
                            dataKey="result"
                            name={resultMetric?.label}
                            fill={color(0)}
                            maxBarSize={32}
                            isAnimationActive={false}
                        />
                    )}
                    {variant === 'bars' &&
                        metrics.map((metric, index) => (
                            <Bar
                                key={metric.id}
                                dataKey={`value${index}`}
                                name={metric.label}
                                fill={color(index)}
                                maxBarSize={24}
                                isAnimationActive={false}
                            />
                        ))}
                    {variant === 'outcome' &&
                        metrics.map((metric, index) => (
                            <Area
                                key={metric.id}
                                dataKey={`value${index}`}
                                name={metric.label}
                                type="linear"
                                stroke={color(index)}
                                strokeWidth={2}
                                fill={color(index)}
                                fillOpacity={0.12}
                                dot={false}
                                activeDot={{ r: 4 }}
                                connectNulls={false}
                                isAnimationActive={false}
                            />
                        ))}
                    {variant === 'outcome' && resultMetric && (
                        <Line
                            dataKey="result"
                            name={resultMetric.label}
                            type="linear"
                            stroke={RESULT_COLOR}
                            strokeWidth={2}
                            dot={{ r: 3, fill: 'var(--card)' }}
                            activeDot={{ r: 5 }}
                            connectNulls={false}
                            isAnimationActive={false}
                        />
                    )}
                    {variant === 'lines' &&
                        segments.map((segment, index) => (
                            <Line
                                key={index}
                                dataKey={`segment${index}`}
                                name={segment.metric.label}
                                type="linear"
                                stroke={color(segment.index)}
                                strokeWidth={2}
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
            {series.length > 1 && (
                <ul
                    aria-label="Legend"
                    className="flex flex-wrap justify-center gap-x-4 gap-y-1"
                >
                    {series.map(({ metric, color: swatch }, index) => (
                        <li
                            key={metric.id}
                            className="flex items-center gap-2 type-meta"
                        >
                            {variant === 'lines' ||
                            metric.id === RESULT_METRIC ? (
                                <svg width="24" height="8" aria-hidden="true">
                                    <line
                                        x1="0"
                                        y1="4"
                                        x2="24"
                                        y2="4"
                                        stroke={swatch}
                                        strokeWidth="3"
                                        strokeDasharray={
                                            variant === 'lines' && index % 2
                                                ? '6 4'
                                                : undefined
                                        }
                                    />
                                </svg>
                            ) : (
                                <span
                                    aria-hidden="true"
                                    className="size-2.5 rounded-sm"
                                    style={{ background: swatch }}
                                />
                            )}
                            {metric.label}
                        </li>
                    ))}
                </ul>
            )}
        </div>
    );
}
