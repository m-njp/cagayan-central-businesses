import { Area, AreaChart, CartesianGrid, XAxis, YAxis } from 'recharts';
import { FinanceTooltip } from '@/components/charts/finance-tooltip';
import { ChartContainer, ChartTooltip } from '@/components/ui/chart';
import { formatPesoTick } from '@/lib/performance-format';
import type { FinancialHistory } from '@/types/economic-performance';

const STACKED = ['position.liabilities', 'position.equity'];
// Two layers of one hue: Liabilities darker at the base, Government Equity lighter above (26-11-03).
const LAYERS = ['var(--map-5)', 'var(--map-2)'];

/** Liabilities and Government Equity as stacked areas, so the top edge is each year's Assets (M35). */
export default function FinancialConditionChart({
    history,
}: {
    history: FinancialHistory;
}) {
    const stacked = history.metrics.filter((metric) =>
        STACKED.includes(metric.id),
    );
    const config = Object.fromEntries(
        stacked.map((metric, index) => [
            metric.id.replace('.', '_'),
            { label: metric.label, color: LAYERS[index] },
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
                <AreaChart
                    data={data}
                    margin={{ top: 8, right: 8, bottom: 0, left: 0 }}
                    accessibilityLayer
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
                        cursor={{
                            stroke: 'var(--muted-foreground)',
                            strokeDasharray: '3 3',
                        }}
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
                                            color: LAYERS[index],
                                        })),
                                    ]}
                                />
                            ) : null
                        }
                    />
                    {stacked.map((metric) => (
                        <Area
                            key={metric.id}
                            dataKey={metric.id.replace('.', '_')}
                            name={metric.label}
                            type="linear"
                            stackId="assets"
                            stroke={`var(--color-${metric.id.replace('.', '_')})`}
                            strokeWidth={1.5}
                            fill={`var(--color-${metric.id.replace('.', '_')})`}
                            fillOpacity={0.3}
                            dot={false}
                            activeDot={{ r: 4 }}
                            connectNulls={false}
                            isAnimationActive={false}
                        />
                    ))}
                </AreaChart>
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
                            className="size-2.5 rounded-sm"
                            style={{ background: LAYERS[index] }}
                        />
                        {metric.label}
                    </li>
                ))}
            </ul>
        </div>
    );
}
