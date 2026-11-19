import { Link } from '@inertiajs/react';
import { useId } from 'react';
import { SourceLink } from '@/components/source-link';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { financeMapObservation } from '@/lib/finance-map';
import { formatFigure, formatNumber, sourceName } from '@/lib/format';
import {
    financeMapSources,
    groupCitationReports,
    observationLabel,
} from '@/lib/performance-format';
import { sources as publicSources } from '@/routes/public';
import type { TownFigure } from '@/types/data';
import type { FinanceMap } from '@/types/finance-map';

export function FinanceMapControls({
    finances,
    latest,
    year,
    onChange,
    compact = false,
}: {
    finances: FinanceMap;
    latest: boolean;
    year: number | null;
    onChange: (patch: Record<string, string | null>) => void;
    compact?: boolean;
}) {
    const yearDescription = useId();
    const resolvedYear = latest ? finances.latestYear : null;

    return (
        <div className="flex items-center justify-between gap-2">
            <span className="type-label">Year</span>
            <Select
                value={latest ? 'latest' : String(year)}
                onValueChange={(value) =>
                    onChange({ year: value === 'latest' ? null : value })
                }
            >
                <SelectTrigger
                    variant="quiet"
                    size={compact ? 'sm' : 'default'}
                    className="font-mono tabular-nums"
                    aria-label="Fiscal year"
                    aria-describedby={
                        resolvedYear === null ? undefined : yearDescription
                    }
                >
                    <SelectValue>
                        {latest ? (resolvedYear ?? 'Latest') : undefined}
                    </SelectValue>
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="latest">Latest</SelectItem>
                    {finances.years.map((value) => (
                        <SelectItem key={value} value={String(value)}>
                            {value}
                        </SelectItem>
                    ))}
                </SelectContent>
            </Select>
            {resolvedYear !== null && (
                <span id={yearDescription} className="sr-only">
                    Latest verified fiscal year
                </span>
            )}
        </div>
    );
}

const figureRow = 'grid grid-cols-[minmax(0,1fr)_auto] items-baseline gap-x-3';

export function FinanceMapFigures({
    finances,
    code,
    year,
    latest,
    population,
    aggregate,
    barangay = false,
    coverage = true,
}: {
    finances: FinanceMap;
    code: string;
    year: number | null;
    latest: boolean;
    population: TownFigure | null;
    aggregate: boolean;
    barangay?: boolean;
    // Public Home states how many towns a province sum covers; the Capitol dashboard does not.
    coverage?: boolean;
}) {
    const rows = barangay
        ? []
        : finances.metrics.map((metric) => ({
              metric,
              point: financeMapObservation(finances, code, year, metric.id),
          }));
    const empty = !rows.some(({ point }) => point?.state === 'shown');
    const coverages = rows.flatMap(({ point }) =>
        point?.coverage ? [point.coverage] : [],
    );
    const sharedCoverage =
        coverages.length > 0 &&
        coverages.every(
            (item) =>
                item.reporting === coverages[0].reporting &&
                item.total === coverages[0].total,
        )
            ? coverages[0]
            : null;
    const sources = barangay ? [] : financeMapSources(finances, code, year);
    const hasProvinceReports =
        aggregate && sources.some((source) => source?.url);
    const links = aggregate
        ? []
        : groupCitationReports(sources).flatMap(({ reports }) =>
              reports.flatMap((report) =>
                  report.links.map((link) => ({
                      ...link,
                      report: report.label,
                  })),
              ),
          );
    const provinceCoverage =
        coverage && aggregate && !empty ? sharedCoverage : null;
    const reporting = provinceCoverage
        ? `${provinceCoverage.reporting} of ${provinceCoverage.total} towns reporting`
        : null;
    const populationSourceUrl = latest ? population?.sourceUrl : null;
    const populationSourceName = populationSourceUrl
        ? sourceName(populationSourceUrl)
        : null;
    return (
        <div className="grid gap-2">
            {latest && (
                <dl className={figureRow}>
                    <dt className="type-label">
                        Population
                        {population ? ` (${population.year})` : ''}
                    </dt>
                    <dd className="font-mono type-body tabular-nums">
                        {formatFigure(population, formatNumber)}
                    </dd>
                </dl>
            )}
            {barangay ? (
                <p className="type-meta">
                    Government finances are not available at barangay level.
                </p>
            ) : empty ? (
                <p role="status" className="type-meta">
                    {year === null
                        ? 'Verified financial figures are not available.'
                        : `${year} figures are not available.`}
                </p>
            ) : (
                <dl className="grid gap-1">
                    {rows.map(({ metric, point }) => (
                        <div key={metric.id} className={figureRow}>
                            <dt className="type-label">{metric.label}</dt>
                            <dd className="font-mono type-body tabular-nums">
                                {observationLabel(point, metric.unit)}
                            </dd>
                            {coverage && !sharedCoverage && point?.coverage && (
                                <dd className="col-span-full type-meta">
                                    {point.coverage.reporting} of{' '}
                                    {point.coverage.total} towns reporting
                                </dd>
                            )}
                        </div>
                    ))}
                </dl>
            )}
            {reporting && <p className="type-meta">{reporting}</p>}
            {(hasProvinceReports ||
                links.length > 0 ||
                populationSourceUrl) && (
                <div className="flex flex-wrap items-baseline gap-x-2 gap-y-1 border-t border-current/15 pt-2 type-meta">
                    <span>Source</span>
                    {hasProvinceReports && (
                        <Link
                            href={`${publicSources.url()}#coa-reports`}
                            className="underline underline-offset-2"
                        >
                            COA Reports
                        </Link>
                    )}
                    {links.map((item) => (
                        <SourceLink key={item.href} href={item.href}>
                            {`${item.report} ${item.text}`}
                        </SourceLink>
                    ))}
                    {populationSourceUrl && populationSourceName && (
                        <SourceLink href={populationSourceUrl}>
                            {populationSourceName ===
                            'Philippine Statistics Authority'
                                ? 'PSA Census'
                                : populationSourceName}
                        </SourceLink>
                    )}
                </div>
            )}
        </div>
    );
}
