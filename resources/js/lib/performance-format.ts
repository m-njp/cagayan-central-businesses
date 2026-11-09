import {
    HIDDEN_FIGURE,
    LOCALE,
    NOT_AVAILABLE,
    formatCount,
} from '@/lib/format';
import type {
    EconomicObservation,
    FinanceSource,
    MetricUnit,
} from '@/types/economic-performance';
import type { FinanceMap } from '@/types/finance-map';

const pesos = new Intl.NumberFormat(LOCALE, {
    style: 'currency',
    currency: 'PHP',
    minimumFractionDigits: 2,
});
const compact = new Intl.NumberFormat(LOCALE, {
    notation: 'compact',
    maximumFractionDigits: 1,
});
const shortPesos = new Intl.NumberFormat(LOCALE, {
    notation: 'compact',
    maximumFractionDigits: 2,
});
const share = new Intl.NumberFormat(LOCALE, { maximumFractionDigits: 2 });
const signedNumber = new Intl.NumberFormat(LOCALE, {
    signDisplay: 'exceptZero',
    maximumFractionDigits: 2,
});

const REASONS: Record<string, string> = {
    not_published: 'COA has not published this report',
    not_reported: 'Not in the COA report for this year',
    not_collected: 'The COA workbook for this year has not been added yet',
    not_loaded: 'Loaded for Tuguegarao City only',
    pending_review: 'Awaiting sign-off',
    small_group: 'Too few businesses in this group',
    dominance: 'One business holds most of this total',
    complementary: 'Hidden so another hidden figure cannot be worked out',
};

export function formatMetricValue(value: string, unit: MetricUnit): string {
    if (unit === 'percent') return `${share.format(Number(value))}%`;
    if (unit === 'count') return formatCount(Number(value));
    return formatExactPesos(value);
}

export function formatMetricTick(value: number, unit: MetricUnit): string {
    if (unit === 'percent') return `${compact.format(value)}%`;
    if (unit === 'count') return compact.format(value);
    return formatPesoTick(value);
}

export function reasonLabel(point?: EconomicObservation): string | null {
    return point?.reason ? (REASONS[point.reason] ?? null) : null;
}

/** Coverage for a Sum of Reporting Towns (M27), the census behind a per-resident value (M25), a restated year (M26), or why a value is missing. */
export function observationNote(point?: EconomicObservation): string | null {
    const notes: string[] = [];
    if (point?.coverage) {
        const { reporting, total, matched } = point.coverage;
        notes.push(`${reporting} of ${total} towns`);
        if (point.change !== null)
            notes.push(`Change over ${matched} reporting both years`);
    }
    if (point?.restatedIn)
        notes.push(`Restated in the ${point.restatedIn} report`);
    const reason = reasonLabel(point);
    if (reason) notes.push(reason);
    return notes.length ? notes.join('. ') : null;
}

/** Pesos in accounting style: a negative amount sits in parentheses, (₱58.65M), never behind a minus sign. */
function pesoText(value: number, short = false): string {
    const text = short
        ? `₱${shortPesos.format(Math.abs(value))}`
        : pesos.format(Math.abs(value));
    return value < 0 ? `(${text})` : text;
}

export function formatExactPesos(value: string): string {
    return pesoText(Number(value));
}

export function formatFinanceSource(
    source: string,
    location: string | null,
): string {
    if (!location) return source;
    const pages = location.match(/^pages?\s+(\d+)(?:\s*-\s*(\d+))?$/i);
    if (pages)
        return `${source} ${pages[2] ? `pp. ${pages[1]}-${pages[2]}` : `p. ${pages[1]}`}`;
    const cell = location.match(/^(.+)!([A-Z]+\d+(?::[A-Z]+\d+)?)$/i);
    if (cell)
        return `${source} ${cell[1].replace(/\s*\([^)]*\)/g, '').trim()}!${cell[2]}`;
    return `${source} ${location}`;
}

/** A COA PDF opens at its cited page; workbook cells have no page. */
export function sourceHref(url: string, location: string | null): string {
    const page = location?.match(/^pages?\s+(\d+)/i);
    return page && !url.includes('#') ? `${url}#page=${page[1]}` : url;
}

/** One link per distinct citation behind a set of figures: a report page, or a workbook once per report. */
export function citationLinks(
    sources: Record<string, FinanceSource>,
    refs: string[],
) {
    const links = new Map<string, { href: string; text: string }>();
    for (const ref of refs) {
        const source = sources[ref];
        if (!source?.url) continue;
        const name = `${source.label} ${source.reportYear ?? source.fiscalYear}`;
        const text =
            source.page === null
                ? name
                : formatFinanceSource(name, source.location);
        // Figures of one report share a page but not a link address, so the text is the identity.
        if (!links.has(text))
            links.set(text, {
                href: sourceHref(source.url, source.location),
                text,
            });
    }
    return [...links.values()];
}

/** One link per distinct report page behind the figures of an area and year. */
export function financeMapSources(
    finances: FinanceMap,
    code: string,
    year: number | null,
) {
    if (year === null) return [];
    const links = new Map<string, { href: string; text: string }>();
    for (const point of Object.values(
        finances.observations[code]?.[year] ?? {},
    ))
        for (const ref of point.sourceRefs ?? []) {
            const source = finances.sources[ref];
            if (!source?.url) continue;
            const href = sourceHref(source.url, source.location);
            if (!links.has(href))
                links.set(href, {
                    href,
                    text: formatFinanceSource('COA Report', source.location),
                });
        }
    return [...links.values()];
}

export function formatPesoTick(value: number): string {
    return `₱${compact.format(value)}`;
}

/** The exact figure, or its short form (₱1.68B) where a phone has no room for cents. */
export function observationLabel(
    point?: EconomicObservation,
    unit: MetricUnit = 'php',
    short = false,
): string {
    if (point?.state === 'hidden') return HIDDEN_FIGURE;
    if (point?.band) return point.band;
    if (point?.state !== 'shown' || point.value === null) return NOT_AVAILABLE;
    if (short && unit === 'php') return pesoText(Number(point.value), true);
    return formatMetricValue(point.value, unit);
}

/** The change as a share of the year before, such as "+4.2%"; none for percent lines or a zero base. */
export function percentChangeLabel(
    point?: EconomicObservation,
    unit: MetricUnit = 'php',
): string | null {
    const percent = point?.percentageChange;
    if (unit === 'percent' || percent === null || percent === undefined)
        return null;
    return `${signedNumber.format(percent)}%`;
}

/** The change from the year before; a rise carries +, a fall in pesos sits in parentheses. */
export function changeLabel(
    point?: EconomicObservation,
    unit: MetricUnit = 'php',
    short = false,
): string {
    if (point?.state === 'hidden') return HIDDEN_FIGURE;
    if (point?.state !== 'shown' || !point.comparable || point.change === null)
        return NOT_AVAILABLE;
    const change = Number(point.change);
    if (unit === 'percent') return `${signedNumber.format(change)} points`;
    if (unit === 'count') return signedNumber.format(change);
    // Accounting style: a fall reads in parentheses, a rise as a plain amount (26-11-03).
    return pesoText(change, short);
}
