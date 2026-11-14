import { router, usePage } from '@inertiajs/react';
import { FileSpreadsheet, FileText, Folder, FolderOpen } from 'lucide-react';
import { useEffect, useRef } from 'react';
import { SearchField } from '@/components/search-field';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Surface } from '@/components/ui/surface';
import { pageSearchParams } from '@/lib/navigation';
import { municipalityCitationReports } from '@/lib/performance-format';
import { parseSourceSelection, sourceQuery } from '@/lib/source-library';
import type { SourceSelection } from '@/lib/source-library';
import { cn } from '@/lib/utils';
import { sources } from '@/routes/public';
import type { FinanceCitation } from '@/types/economic-performance';
import { SourceArchiveDownload } from './source-archive-download';

/**
 * One branch of the file tree: a continuous guide line down the left, an elbow into each item, and the line stopping
 * at the last item, the way `tree` draws it.
 */
const BRANCH =
    'relative pl-5 before:absolute before:top-0 before:left-0 before:h-full before:border-l before:border-border last:before:h-3.5 after:absolute after:top-3.5 after:left-0 after:w-3.5 after:border-t after:border-border';

/**
 * The COA report library as a file tree: a compact municipality list on the left; on the right the selected
 * municipality, every report year and its documents. A PDF opens in a new tab; a workbook downloads.
 */
export function ReportLibrary({ citations }: { citations: FinanceCitation[] }) {
    const { url } = usePage();
    const municipalities = municipalityCitationReports(citations);
    const view = parseSourceSelection(
        pageSearchParams(url),
        municipalities.map(({ town }) => town),
    );
    const selected = municipalities.find(({ town }) => town === view.town);
    const filtered = municipalities.filter(({ town }) =>
        town.toLocaleLowerCase().includes(view.q.trim().toLocaleLowerCase()),
    );
    const townYears = [...(selected?.years ?? [])].sort(
        (first, second) => second.year - first.year,
    );
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
                <div className="grid min-h-0 md:h-[min(32rem,65dvh)] md:min-h-64 md:grid-cols-[12rem_minmax(0,1fr)]">
                    <div className="hidden min-h-0 flex-col border-r md:flex">
                        <div className="flex items-center justify-between gap-2 border-b px-2 py-1">
                            <span className="type-meta">Municipalities</span>
                            <SearchField
                                value={view.q}
                                onChange={(q) => go({ q }, true)}
                                label="Search Municipalities"
                                compact
                                expandedClassName="w-24"
                            />
                        </div>
                        <nav
                            ref={navigation}
                            aria-label="Report Municipalities"
                            className="relative min-h-0 flex-1 overflow-y-auto overscroll-contain py-1"
                        >
                            {filtered.map(({ town }) => {
                                const open = town === view.town;
                                const Icon = open ? FolderOpen : Folder;
                                return (
                                    <button
                                        key={town}
                                        type="button"
                                        ref={open ? currentTown : undefined}
                                        aria-current={open ? 'true' : undefined}
                                        onClick={() => go({ town, q: '' })}
                                        className={cn(
                                            'flex h-7 w-full items-center gap-2 px-2 text-left type-body hover:bg-accent/60',
                                            open &&
                                                'bg-accent font-medium text-accent-foreground',
                                        )}
                                    >
                                        <Icon
                                            className="size-3.5 shrink-0 text-muted-foreground"
                                            aria-hidden="true"
                                        />
                                        <span className="truncate">{town}</span>
                                    </button>
                                );
                            })}
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
                        <div className="border-b px-panel py-1 md:hidden">
                            <Select
                                value={view.town}
                                onValueChange={(town) => go({ town, q: '' })}
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
                        <div
                            role="region"
                            aria-label={`${view.town} COA Reports`}
                            tabIndex={0}
                            className="max-h-[min(32rem,65dvh)] min-h-0 flex-1 overflow-auto overscroll-contain px-panel py-2 type-body outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-inset md:max-h-none"
                        >
                            <div className="flex h-7 items-center gap-2 font-medium">
                                <FolderOpen
                                    className="size-4 shrink-0 text-muted-foreground"
                                    aria-hidden="true"
                                />
                                {view.town}
                            </div>
                            {townYears.length === 0 ? (
                                <p
                                    role="status"
                                    className="pl-6 text-muted-foreground"
                                >
                                    Reports are not available.
                                </p>
                            ) : (
                                <ul
                                    aria-label={`${view.town} Report Years`}
                                    className="ml-2"
                                >
                                    {townYears.map(({ year, reports }) => {
                                        const files = reports.flatMap(
                                            (report) =>
                                                report.links.map((link) => ({
                                                    report,
                                                    link,
                                                })),
                                        );
                                        return (
                                            <li key={year} className={BRANCH}>
                                                <div className="flex h-7 items-center gap-2">
                                                    <Folder
                                                        className="size-4 shrink-0 text-muted-foreground"
                                                        aria-hidden="true"
                                                    />
                                                    <span className="font-mono font-medium tabular-nums">
                                                        {year}
                                                    </span>
                                                </div>
                                                <ul className="ml-2">
                                                    {files.map(
                                                        ({ report, link }) => {
                                                            const workbook =
                                                                link.text ===
                                                                'Download Workbook';
                                                            const Icon =
                                                                workbook
                                                                    ? FileSpreadsheet
                                                                    : FileText;
                                                            const name = `${report.label}${report.links.length > 1 && !workbook ? ` ${link.text}` : ''}`;
                                                            return (
                                                                <li
                                                                    key={`${report.key}${link.href}`}
                                                                    className={
                                                                        BRANCH
                                                                    }
                                                                >
                                                                    <div className="flex min-h-7 items-center gap-2">
                                                                        <Icon
                                                                            className="size-4 shrink-0 text-muted-foreground"
                                                                            aria-hidden="true"
                                                                        />
                                                                        <a
                                                                            href={
                                                                                link.href
                                                                            }
                                                                            {...(workbook
                                                                                ? {
                                                                                      download:
                                                                                          '',
                                                                                  }
                                                                                : {
                                                                                      target: '_blank',
                                                                                      rel: 'noreferrer',
                                                                                  })}
                                                                            aria-label={`${view.town} ${name} ${year} (${workbook ? 'downloads an Excel workbook' : 'opens in a new tab'})`}
                                                                            className="truncate underline underline-offset-2"
                                                                        >
                                                                            {
                                                                                name
                                                                            }
                                                                        </a>
                                                                        <span className="shrink-0 font-mono type-data text-muted-foreground">
                                                                            {workbook
                                                                                ? 'XLSX'
                                                                                : 'PDF'}
                                                                        </span>
                                                                    </div>
                                                                </li>
                                                            );
                                                        },
                                                    )}
                                                </ul>
                                            </li>
                                        );
                                    })}
                                </ul>
                            )}
                        </div>
                    </div>
                </div>
            )}
        </Surface>
    );
}
