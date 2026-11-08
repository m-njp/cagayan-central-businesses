import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { financeMapObservation } from '@/lib/finance-map';
import { formatFigure, formatNumber, sourceName } from '@/lib/format';
import { financeMapSources, observationLabel } from '@/lib/performance-format';
import type { TownFigure } from '@/types/data';
import type { FinanceMap } from '@/types/finance-map';

export function FinanceMapControls({
    finances,
    latest,
    year,
    onChange,
}: {
    finances: FinanceMap;
    latest: boolean;
    year: number | null;
    onChange: (patch: Record<string, string | null>) => void;
}) {
    return (
        <label className="grid gap-2 type-label">
            Year
            <Select
                value={latest ? 'latest' : String(year)}
                onValueChange={(value) =>
                    onChange({ year: value === 'latest' ? null : value })
                }
            >
                <SelectTrigger className="w-full" aria-label="Fiscal year">
                    <SelectValue />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="latest">
                        Latest
                        {finances.latestYear === null
                            ? ''
                            : ` (${finances.latestYear})`}
                    </SelectItem>
                    {finances.years.map((value) => (
                        <SelectItem key={value} value={String(value)}>
                            {value}
                        </SelectItem>
                    ))}
                </SelectContent>
            </Select>
        </label>
    );
}

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
    // A sum of towns has one report per town; one plain attribution beats a wall of links.
    const sources =
        barangay || aggregate ? [] : financeMapSources(finances, code, year);
    const summed = aggregate && !barangay && !empty;

    return (
        <div className="grid gap-3">
            {latest && (
                <dl>
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
                <>
                    {aggregate && coverage && (
                        <div>
                            <h3 className="type-item">
                                Sum of Reporting Towns
                            </h3>
                            {sharedCoverage && (
                                <p className="type-meta">
                                    {sharedCoverage.reporting} of{' '}
                                    {sharedCoverage.total} towns reporting
                                </p>
                            )}
                        </div>
                    )}
                    <dl className="grid gap-3">
                        {rows.map(({ metric, point }) => (
                            <div key={metric.id}>
                                <dt className="type-label">{metric.label}</dt>
                                <dd className="font-mono type-body tabular-nums">
                                    {observationLabel(point, metric.unit)}
                                </dd>
                                {coverage &&
                                    !sharedCoverage &&
                                    point?.coverage && (
                                        <dd className="type-meta">
                                            {point.coverage.reporting} of{' '}
                                            {point.coverage.total} towns
                                            reporting
                                        </dd>
                                    )}
                            </div>
                        ))}
                    </dl>
                </>
            )}
            {(sources.length > 0 ||
                summed ||
                (latest && population?.sourceUrl)) && (
                <div className="grid gap-1 type-meta">
                    <span>Source</span>
                    {summed && <span>COA Annual Audit Reports</span>}
                    {sources.map((item) => (
                        <a
                            key={item.href}
                            href={item.href}
                            target="_blank"
                            rel="noreferrer"
                            className="underline underline-offset-2"
                        >
                            {item.text}
                            <span className="sr-only">
                                {' '}
                                (opens in a new tab)
                            </span>
                        </a>
                    ))}
                    {latest && population?.sourceUrl && (
                        <a
                            href={population.sourceUrl}
                            target="_blank"
                            rel="noreferrer"
                            className="underline underline-offset-2"
                        >
                            {sourceName(population.sourceUrl)}
                            <span className="sr-only">
                                {' '}
                                (opens in a new tab)
                            </span>
                        </a>
                    )}
                </div>
            )}
        </div>
    );
}
