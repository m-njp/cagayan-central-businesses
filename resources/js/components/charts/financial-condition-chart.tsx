import { Bar, BarChart, CartesianGrid, XAxis, YAxis } from 'recharts';
import { FinanceTooltip } from '@/components/charts/finance-tooltip';
import { ChartContainer, ChartTooltip } from '@/components/ui/chart';
import { formatPesoTick } from '@/lib/performance-format';
import type { FinancialHistory } from '@/types/economic-performance';

const STACKED = ['position.liabilities', 'position.equity'];
const SWATCHES = ['bg-chart-3', 'bg-chart-4'];

/** Liabilities and Government Equity stacked, so each bar's height is the year's Assets (M35). */
export default function FinancialConditionChart({
    history,
    syncId,
}: {
    history: FinancialHistory;
    syncId?: string;
}) {
    const stacked = history.metrics.filter((metric) =>
        STACKED.includes(metric.id),
    );
    const config = Object.fromEntries(
        stacked.map((metric, index) => [
            metric.id.replace('.', '_'),
            { label: metric.label, color: `var(--chart-${index + 3})` },
        ]),
    );
    const data = history.rows.map((row) => ({
        year: row.year,
        ...Object.fromEntries(
            stacked.map((metric) => {
                const point = row.observations[metric.id];
                return [
                    metric.id.replace('.', '_'),
                    point?.state === 'shown' && point.value !== null
                        ? Number(point.value)
                        : null,
                ];
            }),
        ),
    }));
    return (
        <div className="flex min-w-0 flex-col gap-2">
            <ChartContainer
                config={config}
                className="aspect-auto h-48 w-full font-mono tabular-nums sm:h-56"
                role="group"
                aria-label={`${stacked.map((metric) => metric.label).join(' and ')} by year, adding up to Assets. Exact values are listed in the table.`}
            >
                <BarChart
                    data={data}
                    margin={{ top: 8, right: 8, bottom: 0, left: 0 }}
                    accessibilityLayer
                    syncId={syncId}
                >
                    <CartesianGrid vertical={false} stroke="var(--border)" />
                    <XAxis
                        dataKey="year"
                        tickLine={false}
                        axisLine={false}
                        tickMargin={12}
                    />
                    <YAxis
                        width={64}
                        tickFormatter={formatPesoTick}
                        tickLine={false}
                        axisLine={false}
                    />
                    <ChartTooltip
                        cursor={{ fill: 'var(--muted)' }}
                        content={({ active, label }) =>
                            active ? (
                                <FinanceTooltip
                                    history={history}
                                    label={label}
                                    series={[
                                        ...history.metrics
                                            .filter(
                                                (metric) =>
                                                    !STACKED.includes(
                                                        metric.id,
                                                    ),
                                            )
                                            .map((metric) => ({
                                                metric,
                                                color: 'var(--foreground)',
                                            })),
                                        ...stacked.map((metric, index) => ({
                                            metric,
                                            color: `var(--chart-${index + 3})`,
                                        })),
                                    ]}
                                />
                            ) : null
                        }
                    />
                    {stacked.map((metric) => (
                        <Bar
                            key={metric.id}
                            dataKey={metric.id.replace('.', '_')}
                            name={metric.label}
                            stackId="assets"
                            fill={`var(--color-${metric.id.replace('.', '_')})`}
                            maxBarSize={40}
                            isAnimationActive={false}
                        />
                    ))}
                </BarChart>
            </ChartContainer>
            <ul
                aria-label="Legend"
                className="flex flex-wrap justify-center gap-x-4 gap-y-1"
            >
                {stacked.map((metric, index) => (
                    <li
                        key={metric.id}
                        className="flex items-center gap-2 type-meta"
                    >
                        <span
                            aria-hidden="true"
                            className={`size-2.5 rounded-sm ${SWATCHES[index]}`}
                        />
                        {metric.label}
                    </li>
                ))}
            </ul>
        </div>
    );
}
