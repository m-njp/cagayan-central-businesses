import { CHANGE_WORDS, trendWord } from '@/lib/trend';
import type { Trend } from '@/lib/trend';

/** Net Income reads in accounting style like any amount: a loss sits in parentheses, with no triangle or color. */
export const RESULT_METRIC = 'performance.surplus';

/** The triangle of a change, with its meaning in words so color and shape are never the only signal. */
export function TrendMark({ trend }: { trend: Trend }) {
    return (
        <span role="img" aria-label={trendWord(trend, CHANGE_WORDS)}>
            {trend.glyph}
        </span>
    );
}
