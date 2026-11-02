// The one place that decides how a change looks: green up for a rise, red down for a fall (design.md,
// "Executive Summary View"). The colors are the --trend-up and --trend-down tokens. Direction only, never a rating (M26).
// Net Income itself carries no mark; a loss reads in parentheses.
export type Trend = {
    direction: 1 | -1 | 0;
    tone: string;
    glyph: '▲' | '▼' | '=';
};

const UP: Trend = { direction: 1, tone: 'text-trend-up', glyph: '▲' };
const DOWN: Trend = { direction: -1, tone: 'text-trend-down', glyph: '▼' };
const FLAT: Trend = {
    direction: 0,
    tone: 'text-muted-foreground',
    glyph: '=',
};

/** Null when there is no number, so a missing or hidden figure never looks like a rise or a fall. */
export function trendOf(
    value: number | string | null | undefined,
): Trend | null {
    if (value === null || value === undefined || value === '') return null;
    const number = Number(value);
    if (Number.isNaN(number)) return null;
    return number > 0 ? UP : number < 0 ? DOWN : FLAT;
}

export const CHANGE_WORDS = ['Increased', 'Decreased', 'Unchanged'] as const;

export function trendWord(
    trend: Trend,
    words: readonly [string, string, string],
): string {
    return trend.direction > 0
        ? words[0]
        : trend.direction < 0
          ? words[1]
          : words[2];
}
