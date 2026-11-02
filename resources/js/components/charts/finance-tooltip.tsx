import { observationLabel } from '@/lib/performance-format';
import type {
    EconomicMetric,
    FinancialHistory,
} from '@/types/economic-performance';

/**
 * One compact card for every finance chart: the year, then a swatch, the label and the exact amount of each series.
 * A loss reads in parentheses; a missing year reads Not available, never zero.
 */
export function FinanceTooltip({
    history,
    label,
    series,
}: {
    history: FinancialHistory;
    label: unknown;
    series: { metric: EconomicMetric; color: string }[];
}) {
    const row = history.rows.find((item) => item.year === Number(label));
    if (!row) return null;
    return (
        <div className="grid min-w-48 gap-1.5 rounded-sm border bg-popover px-3 py-2 text-popover-foreground shadow-md">
            <p className="font-mono type-item tabular-nums">{row.year}</p>
            {series.map(({ metric, color }) => (
                <div
                    key={metric.id}
                    className="flex items-center justify-between gap-4 type-data"
                >
                    <span className="flex items-center gap-2 font-sans">
                        <span
                            aria-hidden="true"
                            className="size-2.5 shrink-0 rounded-sm"
                            style={{ background: color }}
                        />
                        {metric.label}
                    </span>
                    <span className="font-mono tabular-nums">
                        {observationLabel(
                            row.observations[metric.id],
                            metric.unit,
                        )}
                    </span>
                </div>
            ))}
        </div>
    );
}
