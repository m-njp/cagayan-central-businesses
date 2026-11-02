import {
    Bar,
    CartesianGrid,
    ComposedChart,
    Line,
    ReferenceLine,
    XAxis,
    YAxis,
} from 'recharts';
import { RESULT_METRIC } from '@/components/charts/trend-mark';
import { ChartContainer, ChartTooltip } from '@/components/ui/chart';
import { financeChartSeries } from '@/lib/finance-chart';
import { formatMetricTick, observationLabel } from '@/lib/performance-format';
import type { FinancialHistory } from '@/types/economic-performance';

export default function FinanceHistoryChart({
    history,
    result = false,
    legend = true,
}: {
    history: FinancialHistory;
    result?: boolean;
    legend?: boolean;
}) {
    const metrics = history.metrics;
    const unit = metrics[0]?.unit ?? 'php';
    const segments = financeChartSeries(history);
    const config = Object.fromEntries(
        metrics.map((metric, index) => [
            `series${index}`,
            { label: metric.label, color: `var(--chart-${(index % 5) + 1})` },
        ]),
    );
    const data = history.rows.map((row, position) => {
        const point = row.observations[RESULT_METRIC];
        return {
            year: row.year,
            result:
                point?.state === 'shown' && point.value !== null
                    ? Number(point.value)
                    : null,
            ...Object.fromEntries(
                segments.map((segment, index) => [
                    `segment${index}`,
                    segment.values[position],
                ]),
            ),
        };
    });
    return (
        <div className="flex min-w-0 flex-col gap-2">
            <ChartContainer
                config={config}
                className={`aspect-auto w-full font-mono tabular-nums ${result ? 'h-32 sm:h-36' : 'h-48 sm:h-56'}`}
                role="group"
                aria-label={`${metrics.map((metric) => metric.label).join(' and ')} by year. Exact values are listed in the table.`}
            >
                <ComposedChart
                    data={data}
                    margin={{ top: 8, right: 8, bottom: 0, left: 0 }}
                    accessibilityLayer
                    syncId="finance-trends"
                >
                    <CartesianGrid vertical={false} stroke="var(--border)" />
                    <XAxis
                        dataKey="year"
                        type="number"
                        domain={
                            history.rows.length === 1
                                ? [
                                      history.rows[0].year - 0.5,
                                      history.rows[0].year + 0.5,
                                  ]
                                : ['dataMin', 'dataMax']
                        }
                        ticks={history.rows.map((row) => row.year)}
                        tickLine={false}
                        axisLine={false}
                        tickMargin={12}
                        minTickGap={24}
                        padding={{ left: 20, right: 20 }}
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
                    {result && (
                        <ReferenceLine y={0} stroke="var(--muted-foreground)" />
                    )}
                    <ChartTooltip
                        cursor={{
                            stroke: 'var(--muted-foreground)',
                            strokeDasharray: '3 3',
                        }}
                        content={({ active, label }) => {
                            const row = history.rows.find(
                                (item) => item.year === Number(label),
                            );
                            return active && row ? (
                                <div className="grid max-w-80 gap-2 rounded-sm border bg-popover p-3 text-popover-foreground shadow-md">
                                    <p className="type-label">{row.year}</p>
                                    {metrics.map((metric) => {
                                        const point =
                                            row.observations[metric.id];
                                        const value = observationLabel(
                                            point,
                                            metric.unit,
                                        );
                                        return (
                                            <div
                                                key={metric.id}
                                                className="grid gap-1 border-t pt-2 type-data"
                                            >
                                                <span className="font-sans">
                                                    {metric.label}
                                                </span>
                                                <span>{value}</span>
                                            </div>
                                        );
                                    })}
                                </div>
                            ) : null;
                        }}
                    />
                    {result ? (
                        <Bar
                            dataKey="result"
                            name={metrics[0]?.label}
                            fill="var(--color-series0)"
                            maxBarSize={32}
                            isAnimationActive={false}
                        />
                    ) : (
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
                        ))
                    )}
                </ComposedChart>
            </ChartContainer>
            {legend && metrics.length > 1 && (
                <ul
                    aria-label="Legend"
                    className="flex flex-wrap justify-center gap-x-4 gap-y-1"
                >
                    {metrics.map((metric, index) => (
                        <li
                            key={metric.id}
                            className="flex items-center gap-2 type-meta"
                        >
                            <svg width="24" height="8" aria-hidden="true">
                                <line
                                    x1="0"
                                    y1="4"
                                    x2="24"
                                    y2="4"
                                    stroke={`var(--chart-${(index % 5) + 1})`}
                                    strokeWidth="3"
                                    strokeDasharray={
                                        index % 2 ? '6 4' : undefined
                                    }
                                />
                            </svg>
                            {metric.label}
                        </li>
                    ))}
                </ul>
            )}
        </div>
    );
}
