import { ChartColumn, Table2 } from 'lucide-react';
import { FinanceGuideButton } from '@/components/analytics/finance-guide';
import { PeriodToggle } from '@/components/charts/finance-trends';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group';
import { latestYear, measureCovers } from '@/lib/analytics';
import type {
    AnalyticsCatalog,
    AnalyticsFilters,
} from '@/types/economic-performance';

/**
 * Every Government Finances control in one header row: Show (charts and one year only), 3Y 5Y All, one fiscal year,
 * Table or Charts, and Help. Every option is already loaded, so each change stays in local history.
 */
export function FinanceToolbar({
    catalog,
    filters,
    onChange,
}: {
    catalog: AnalyticsCatalog;
    filters: AnalyticsFilters;
    onChange: (patch: Partial<AnalyticsFilters>) => void;
}) {
    const histories = catalog.finances[filters.town];
    const history = histories?.[filters.measure] ?? histories?.totals;
    const last = history ? latestYear(history) : null;
    const years = (history?.rows ?? [])
        .map((row) => row.year)
        .filter((year) => last === null || year <= last)
        .reverse();
    const view = filters.presentation === 'trends' ? 'charts' : 'table';
    return (
        <div className="flex flex-wrap items-center gap-1.5">
            {filters.presentation !== 'statement' && (
                <Select
                    value={filters.measure}
                    onValueChange={(measure) => onChange({ measure })}
                >
                    <SelectTrigger
                        variant="quiet"
                        aria-label="Show"
                        size="sm"
                        className="w-auto min-w-40"
                    >
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        {catalog.measures.map((measure) => (
                            <SelectItem
                                key={measure.value}
                                value={measure.value}
                                disabled={!measureCovers(measure, filters.town)}
                            >
                                {measure.label}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
            )}
            <PeriodToggle filters={filters} onChange={onChange} />
            <Select
                value={
                    filters.presentation === 'summary' && filters.year
                        ? String(filters.year)
                        : ''
                }
                onValueChange={(year) =>
                    onChange({ presentation: 'summary', year: Number(year) })
                }
                disabled={years.length === 0}
            >
                <SelectTrigger
                    variant="quiet"
                    aria-label="Fiscal Year"
                    size="sm"
                    className="w-auto gap-1 px-2.5 font-mono tabular-nums"
                >
                    <SelectValue placeholder="Year" />
                </SelectTrigger>
                <SelectContent>
                    {years.map((year) => (
                        <SelectItem
                            key={year}
                            value={String(year)}
                            className="font-mono tabular-nums"
                        >
                            {year}
                        </SelectItem>
                    ))}
                </SelectContent>
            </Select>
            <ToggleGroup
                type="single"
                variant="outline"
                size="sm"
                value={view}
                aria-label="View"
                onValueChange={(next) => {
                    if (next)
                        onChange({
                            presentation:
                                next === 'charts' ? 'trends' : 'statement',
                        });
                }}
            >
                <ToggleGroupItem
                    value="table"
                    aria-label="Table"
                    title="Table"
                    className="size-9"
                >
                    <Table2 aria-hidden="true" />
                </ToggleGroupItem>
                <ToggleGroupItem
                    value="charts"
                    aria-label="Charts"
                    title="Charts"
                    className="size-9"
                >
                    <ChartColumn aria-hidden="true" />
                </ToggleGroupItem>
            </ToggleGroup>
            <FinanceGuideButton />
        </div>
    );
}
