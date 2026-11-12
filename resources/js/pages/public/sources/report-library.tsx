import { router, usePage } from '@inertiajs/react';
import { FileSpreadsheet, FileText } from 'lucide-react';
import { useEffect, useRef } from 'react';
import { SearchField } from '@/components/search-field';
import { SourceLink } from '@/components/source-link';
import { Button } from '@/components/ui/button';
import { Field, FieldLabel } from '@/components/ui/field';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Surface } from '@/components/ui/surface';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { pageSearchParams } from '@/lib/navigation';
import { municipalityCitationReports } from '@/lib/performance-format';
import { parseSourceSelection, sourceQuery } from '@/lib/source-library';
import type { SourceSelection } from '@/lib/source-library';
import { cn } from '@/lib/utils';
import { sources } from '@/routes/public';
import type { FinanceCitation } from '@/types/economic-performance';
import { SourceArchiveDownload } from './source-archive-download';

export function ReportLibrary({ citations }: { citations: FinanceCitation[] }) {
    const { url } = usePage();
    const municipalities = municipalityCitationReports(citations);
    const years = [
        ...new Set(
            citations.map(
                (citation) => citation.reportYear ?? citation.fiscalYear,
            ),
        ),
    ].sort((first, second) => second - first);
    const view = parseSourceSelection(
        pageSearchParams(url),
        municipalities.map(({ town }) => town),
        years,
    );
    const selected = municipalities.find(({ town }) => town === view.town);
    const filtered = municipalities.filter(({ town }) =>
        town.toLocaleLowerCase().includes(view.q.trim().toLocaleLowerCase()),
    );
    const shownYears = [...(selected?.years ?? [])]
        .filter(({ year }) => view.year === null || year === view.year)
        .sort((first, second) => second.year - first.year);
    const navigation = useRef<HTMLElement>(null);
    const currentTown = useRef<HTMLButtonElement>(null);

    function go(patch: Partial<SourceSelection>, replace = false) {
        const target = sources({
            query: sourceQuery({ ...view, ...patch }),
        }).url;
        if (target === url.split('#')[0]) return;
        const visit = {
            url: target,
            preserveState: true,
            preserveScroll: true,
        };
        if (replace) router.replace(visit);
        else router.push(visit);
    }

    useEffect(() => {
        const container = navigation.current;
        const button = currentTown.current;
        if (container && button) {
            container.scrollTop =
                button.offsetTop -
                (container.clientHeight - button.offsetHeight) / 2;
        }
    }, [view.town, view.q]);

    return (
        <Surface
            id="coa-reports"
            title="COA Audit Reports"
            className="scroll-mt-4"
            toolbar={municipalities.length > 0 && <SourceArchiveDownload />}
        >
            {municipalities.length === 0 ? (
                <p className="p-panel type-body text-muted-foreground">
                    Reports are not available.
                </p>
            ) : (
                <div className="grid min-h-0 md:h-[min(32rem,65dvh)] md:min-h-64 md:grid-cols-[15rem_minmax(0,1fr)]">
                    <div className="hidden min-h-0 flex-col border-r md:flex">
                        <div className="flex min-h-touch items-center justify-between gap-2 border-b px-panel py-1.5">
                            <span className="type-meta">Municipalities</span>
                            <SearchField
                                value={view.q}
                                onChange={(q) => go({ q }, true)}
                                label="Search Municipalities"
                                compact
                                expandedClassName="w-28"
                            />
                        </div>
                        <nav
                            ref={navigation}
                            aria-label="Report Municipalities"
                            className="relative min-h-0 flex-1 overflow-y-auto overscroll-contain p-1"
                        >
                            {filtered.map(({ town }) => (
                                <Button
                                    key={town}
                                    ref={
                                        town === view.town
                                            ? currentTown
                                            : undefined
                                    }
                                    variant="ghost"
                                    aria-current={
                                        town === view.town ? 'true' : undefined
                                    }
                                    onClick={() => go({ town, q: '' })}
                                    className={cn(
                                        'min-h-touch w-full justify-start text-left whitespace-normal md:min-h-row',
                                        town === view.town &&
                                            'bg-accent text-accent-foreground',
                                    )}
                                >
                                    {town}
                                </Button>
                            ))}
                            {filtered.length === 0 && (
                                <p
                                    role="status"
                                    className="p-panel type-body text-muted-foreground"
                                >
                                    No matching municipalities.
                                </p>
                            )}
                        </nav>
                    </div>
                    <div className="flex min-h-0 min-w-0 flex-col">
                        <div className="flex min-h-touch flex-wrap items-center justify-between gap-2 border-b px-panel py-1.5">
                            <h3 className="hidden type-item md:block">
                                {view.town}
                            </h3>
                            <div className="min-w-0 flex-1 md:hidden">
                                <Select
                                    value={view.town}
                                    onValueChange={(town) =>
                                        go({ town, q: '' })
                                    }
                                >
                                    <SelectTrigger
                                        aria-label="Municipality"
                                        variant="quiet"
                                        className="max-w-full"
                                    >
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {municipalities.map(({ town }) => (
                                            <SelectItem key={town} value={town}>
                                                {town}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                            <Field
                                orientation="horizontal"
                                className="w-auto gap-1"
                            >
                                <FieldLabel htmlFor="source-year">
                                    Year
                                </FieldLabel>
                                <Select
                                    value={view.year?.toString() ?? 'all'}
                                    onValueChange={(year) =>
                                        go({
                                            year:
                                                year === 'all'
                                                    ? null
                                                    : Number(year),
                                        })
                                    }
                                >
                                    <SelectTrigger
                                        id="source-year"
                                        variant="quiet"
                                        className="font-mono tabular-nums"
                                    >
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">
                                            All Years
                                        </SelectItem>
                                        {years.map((year) => (
                                            <SelectItem
                                                key={year}
                                                value={year.toString()}
                                                className="font-mono tabular-nums"
                                            >
                                                {year}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </Field>
                        </div>
                        <div
                            role="region"
                            aria-label={`${view.town} COA Reports`}
                            tabIndex={0}
                            className="max-h-[min(32rem,65dvh)] min-h-0 flex-1 overflow-auto overscroll-contain outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-inset md:max-h-none [&_[data-slot=table-container]]:overflow-visible"
                        >
                            <Table data-striped>
                                <TableHeader className="sticky top-0 z-10 bg-card">
                                    <TableRow>
                                        <TableHead>Year</TableHead>
                                        <TableHead>Reports</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {shownYears.map(({ year, reports }) => (
                                        <TableRow key={year}>
                                            <TableCell className="w-20 align-top font-mono tabular-nums">
                                                {year}
                                            </TableCell>
                                            <TableCell className="whitespace-normal">
                                                <ul className="grid gap-2">
                                                    {reports.map((report) => {
                                                        const workbook =
                                                            report.links.some(
                                                                (link) =>
                                                                    link.text ===
                                                                    'Download Workbook',
                                                            );
                                                        const Icon = workbook
                                                            ? FileSpreadsheet
                                                            : FileText;
                                                        return (
                                                            <li
                                                                key={report.key}
                                                                className="flex items-start gap-2"
                                                            >
                                                                <Icon
                                                                    className="mt-0.5 size-4 shrink-0"
                                                                    aria-hidden="true"
                                                                />
                                                                <div className="flex flex-wrap gap-x-3 gap-y-1 type-body">
                                                                    {report.links.map(
                                                                        (
                                                                            link,
                                                                        ) => (
                                                                            <SourceLink
                                                                                key={
                                                                                    link.text
                                                                                }
                                                                                href={
                                                                                    link.href
                                                                                }
                                                                                label={`${view.town} ${report.label} ${year} ${link.text}`}
                                                                            >
                                                                                {`${report.label}${report.links.length > 1 ? ` ${link.text}` : ''}`}
                                                                            </SourceLink>
                                                                        ),
                                                                    )}
                                                                </div>
                                                            </li>
                                                        );
                                                    })}
                                                </ul>
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                    {shownYears.length === 0 && (
                                        <TableRow>
                                            <TableCell
                                                colSpan={2}
                                                className="whitespace-normal text-muted-foreground"
                                            >
                                                <p role="status">
                                                    Reports are not available
                                                    for this year.
                                                </p>
                                            </TableCell>
                                        </TableRow>
                                    )}
                                </TableBody>
                            </Table>
                        </div>
                    </div>
                </div>
            )}
        </Surface>
    );
}
