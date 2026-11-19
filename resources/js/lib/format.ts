// Formatting only, no metric math (frontend.md, "Rules for numbers").

import type { TownFigure } from '@/types/data';

export const LOCALE = 'en-PH';

// Same zone as config('cagayan.display_timezone'), so server and browser renders match.
export const TIME_ZONE = 'Asia/Manila';

export const NOT_AVAILABLE = 'Not available';
export const HIDDEN_FIGURE = 'Hidden to protect individual businesses';

const SOURCE_NAMES: Record<string, string> = {
    'psa.gov.ph': 'Philippine Statistics Authority',
    'www.coa.gov.ph': 'Commission on Audit',
};

const countFormat = new Intl.NumberFormat(LOCALE);

const dateTimeFormat = new Intl.DateTimeFormat(LOCALE, {
    month: 'long',
    day: 'numeric',
    hour: 'numeric',
    minute: '2-digit',
    timeZone: TIME_ZONE,
});

export function formatCount(value: number): string {
    return countFormat.format(value);
}

// "October 6 at 4:12 PM", in residents' local time.
export function formatDateTime(iso: string): string {
    const parts = dateTimeFormat.formatToParts(new Date(iso));
    const part = (type: Intl.DateTimeFormatPartTypes) =>
        parts.find((p) => p.type === type)?.value ?? '';

    return `${part('month')} ${part('day')} at ${part('hour')}:${part('minute')} ${part('dayPeriod').toUpperCase()}`;
}

// "12-31-2026" from a server date string (YYYY-MM-DD), read as a calendar date: the one date format on screen.
export function formatDate(date: string): string {
    const [year, month, day] = date.slice(0, 10).split('-');
    return `${month}-${day}-${year}`;
}

const entryTimestampFormat = new Intl.DateTimeFormat(LOCALE, {
    year: '2-digit',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
    hourCycle: 'h23',
    timeZone: TIME_ZONE,
});

// "11-08-26 14:05" from a server instant: month-day-year, then the 24-hour time, in Philippine time.
export function formatEntryTimestamp(iso: string): string {
    const parts = entryTimestampFormat.formatToParts(new Date(iso));
    const part = (type: Intl.DateTimeFormatPartTypes) =>
        parts.find((p) => p.type === type)?.value ?? '';

    return `${part('month')}-${part('day')}-${part('year')} ${part('hour')}:${part('minute')}`;
}

const decimalFormat = new Intl.NumberFormat(LOCALE, {
    maximumFractionDigits: 1,
});

// Figures arrive as decimal strings from the server (TownFigure).
export function formatNumber(value: string): string {
    return decimalFormat.format(Number(value));
}

export function formatFigure(
    figure: TownFigure | null | undefined,
    format: (value: string) => string = formatNumber,
): string {
    return figure ? format(figure.value) : NOT_AVAILABLE;
}

export function sourceName(sourceUrl: string): string {
    const { hostname } = new URL(sourceUrl);

    return SOURCE_NAMES[hostname] ?? hostname;
}
