import type { FinancialHistory } from '@/types/economic-performance';

/** Never connect observations across a missing year or a definition change. */
export function financeChartSeries(history: FinancialHistory) {
    return history.metrics.flatMap((metric, index) => {
        const segments: {
            index: number;
            metric: typeof metric;
            values: (number | null)[];
        }[] = [];
        let previousYear: number | null = null;
        for (const [position, row] of history.rows.entries()) {
            const point = row.observations[metric.id];
            if (point?.state !== 'shown' || point.value === null) {
                previousYear = null;
                continue;
            }
            if (previousYear !== row.year - 1 || !point.comparable) {
                segments.push({
                    index,
                    metric,
                    values: history.rows.map(() => null),
                });
            }
            segments[segments.length - 1].values[position] = Number(
                point.value,
            );
            previousYear = row.year;
        }
        return segments;
    });
}

export function focusedFinance(
    history: FinancialHistory,
    metric: string | null,
) {
    return (
        history.metrics.find((item) => item.id === metric) ?? history.metrics[0]
    );
}
