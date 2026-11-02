import { ExactAmount } from '@/components/charts/exact-amount';
import { TrendMark } from '@/components/charts/trend-mark';
import { changeLabel } from '@/lib/performance-format';
import { trendOf } from '@/lib/trend';
import type {
    EconomicObservation,
    MetricUnit,
} from '@/types/economic-performance';

/**
 * Direction of the change from the previous comparable year, with the exact amount on hover, focus or tap. The mark
 * alone in Trends cells, the mark and the short amount in a Change column. Never a percentage, never a rating (M26).
 */
export function ObservationChange({
    point,
    unit,
    markOnly = false,
}: {
    point?: EconomicObservation;
    unit: MetricUnit;
    markOnly?: boolean;
}) {
    if (point?.state !== 'shown' || !point.comparable || point.change === null)
        return null;
    const trend = trendOf(point.change);
    if (!trend) return null;
    const exact = `${changeLabel(point, unit)} from ${point.comparisonYear}`;
    const short = changeLabel(point, unit, true);
    return (
        <ExactAmount exact={exact} label={exact}>
            <span
                className={`inline-flex items-center gap-1 font-mono type-data tabular-nums ${trend.tone}`}
            >
                <TrendMark trend={trend} />
                {!markOnly && short}
            </span>
        </ExactAmount>
    );
}
