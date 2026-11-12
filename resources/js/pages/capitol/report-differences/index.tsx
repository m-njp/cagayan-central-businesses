import { Head, router, usePage } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import { PageHeader } from '@/components/capitol/page-header';
import { Button } from '@/components/ui/button';
import { TabRow } from '@/components/capitol/tab-row';
import { Surface } from '@/components/ui/surface';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { formatCount } from '@/lib/format';
import { icons } from '@/lib/icons';
import { cn } from '@/lib/utils';
import { index } from '@/routes/capitol/report-differences';
import type {
    ReportDifferenceCell,
    ReportDifferenceDetail,
    ReportDifferenceFamily,
    ReportDifferences,
} from '@/types/finance';
import { DifferenceDetail } from './difference-detail';
import { FAMILY_LABELS } from './finding-guide';
import { ReportDifferencesGuide } from './report-differences-guide';

type Selection = { town: string; year: number } | null;

const CELL_COUNTS: Record<ReportDifferenceFamily, keyof ReportDifferenceCell> =
    {
        report: 'report',
        between_reports: 'between',
        reading: 'reading',
    };

function cellCount(
    cell: ReportDifferenceCell,
    family: ReportDifferenceFamily | null,
) {
    return family
        ? cell[CELL_COUNTS[family]]
        : cell.report + cell.between + cell.reading;
}

function readSelection(url: string) {
    const query = new URLSearchParams(url.split('?')[1] ?? '');
    const family = query.get('family') as ReportDifferenceFamily | null;
    const town = query.get('town');
    const year = Number(query.get('year'));

    return {
        family: family && family in FAMILY_LABELS ? family : null,
        selection: town && year ? { town, year } : null,
    };
}

export default function ReportDifferencesPage({
    differences,
    detail,
}: {
    differences: ReportDifferences;
    detail?: ReportDifferenceDetail | null;
}) {
    const { url } = usePage();
    const { family, selection } = readSelection(url);
    // Findings for each report are fetched once; reopening one or going back uses this copy.
    const cached = useRef(new Map<string, ReportDifferenceDetail | null>());
    const [failed, setFailed] = useState(false);
    const [guide, setGuide] = useState<{ open: boolean; focus: string | null }>(
        { open: false, focus: null },
    );
    const key = selection ? `${selection.town}|${selection.year}` : null;
    const matches = (item: ReportDifferenceDetail | null | undefined) =>
        item?.townCode === selection?.town && item?.year === selection?.year;
    // Focus returns to the cell that opened a report.
    const opener = useRef<HTMLElement | null>(null);
    const shown =
        key && detail !== undefined && matches(detail)
            ? detail
            : key
              ? cached.current.get(key)
              : undefined;

    useEffect(() => {
        if (detail && key && matches(detail)) cached.current.set(key, detail);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [detail, key]);

    const query = (next: { family: string | null; selection: Selection }) => ({
        ...(next.family ? { family: next.family } : {}),
        ...(next.selection
            ? {
                  town: next.selection.town,
                  year: String(next.selection.year),
              }
            : {}),
    });

    const show = (next: Selection, nextFamily = family) => {
        const target = index({
            query: query({ family: nextFamily, selection: next }),
        }).url;
        const nextKey = next ? `${next.town}|${next.year}` : null;
        if (next && document.activeElement instanceof HTMLElement)
            opener.current = document.activeElement;
        setFailed(false);
        if (!nextKey || cached.current.has(nextKey)) {
            router.push({
                url: target,
                props: (props) => ({
                    ...props,
                    detail: nextKey ? cached.current.get(nextKey) : null,
                }),
                preserveState: true,
                preserveScroll: true,
            });

            return;
        }
        router.get(
            target,
            {},
            {
                only: ['detail'],
                preserveState: true,
                preserveScroll: true,
                onError: () => setFailed(true),
                onNetworkError: () => setFailed(true),
            },
        );
    };

    // A shared link opens straight onto a report: load its findings once.
    useEffect(() => {
        if (key && shown === undefined && !failed) show(selection, family);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [key]);

    const totals = Object.fromEntries(
        (Object.keys(FAMILY_LABELS) as ReportDifferenceFamily[]).map((name) => [
            name,
            differences.rows.reduce(
                (sum, row) =>
                    sum +
                    Object.values(row.cells).reduce(
                        (cells, cell) =>
                            cells + (cell ? cell[CELL_COUNTS[name]] : 0),
                        0,
                    ),
                0,
            ),
        ]),
    ) as Record<ReportDifferenceFamily, number>;

    return (
        <>
            <Head title="Report Differences" />
            <PageHeader title="Report Differences">
                <Button
                    variant="outline"
                    size="sm"
                    onClick={() => setGuide({ open: true, focus: null })}
                >
                    <icons.help aria-hidden="true" />
                    Guide
                </Button>
            </PageHeader>
            <Surface padded>
                <TabRow
                    label="Finding"
                    value={family}
                    tabs={[
                        { value: null, label: 'All' },
                        ...(
                            Object.entries(FAMILY_LABELS) as [
                                ReportDifferenceFamily,
                                string,
                            ][]
                        ).map(([value, label]) => ({
                            value,
                            label,
                            count: totals[value],
                        })),
                    ]}
                    onChange={(value) =>
                        show(selection, value as ReportDifferenceFamily | null)
                    }
                />
            </Surface>
            <Surface className="overflow-x-auto">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead className="sticky left-0 z-10 bg-card">
                                Municipality
                            </TableHead>
                            {differences.years.map((year) => (
                                <TableHead
                                    key={year}
                                    className="text-right font-mono tabular-nums"
                                >
                                    {year}
                                </TableHead>
                            ))}
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {differences.rows.length ? (
                            differences.rows.map((row) => (
                                <TableRow key={row.townCode}>
                                    <TableCell className="sticky left-0 z-10 bg-card whitespace-nowrap">
                                        {row.town}
                                    </TableCell>
                                    {differences.years.map((year) => {
                                        const cell = row.cells[year];
                                        if (!cell)
                                            return (
                                                <TableCell
                                                    key={year}
                                                    className="text-right text-xs text-muted-foreground"
                                                >
                                                    Not available
                                                </TableCell>
                                            );
                                        const count = cellCount(cell, family);
                                        const status =
                                            cell.pending > 0
                                                ? 'Pending'
                                                : cell.verified > 0
                                                  ? 'Verified'
                                                  : null;

                                        return (
                                            <TableCell
                                                key={year}
                                                className="p-0 text-right"
                                            >
                                                <button
                                                    type="button"
                                                    aria-label={`${row.town} ${year} report, ${count} findings${status ? `, ${status}` : ''}`}
                                                    onClick={() =>
                                                        show({
                                                            town: row.townCode,
                                                            year,
                                                        })
                                                    }
                                                    className="flex h-full w-full cursor-pointer items-center justify-end gap-1.5 px-cell-x py-cell-y font-mono tabular-nums hover:bg-muted focus-visible:bg-muted focus-visible:outline-none"
                                                >
                                                    {status && (
                                                        <span
                                                            aria-hidden="true"
                                                            title={status}
                                                            className={cn(
                                                                'size-2 rounded-full',
                                                                status ===
                                                                    'Verified'
                                                                    ? 'bg-status-new'
                                                                    : 'border border-muted-foreground',
                                                            )}
                                                        />
                                                    )}
                                                    <span
                                                        className={cn(
                                                            count === 0 &&
                                                                'text-muted-foreground',
                                                        )}
                                                    >
                                                        {formatCount(count)}
                                                    </span>
                                                </button>
                                            </TableCell>
                                        );
                                    })}
                                </TableRow>
                            ))
                        ) : (
                            <TableRow>
                                <TableCell
                                    colSpan={differences.years.length + 1}
                                    className="py-8 text-center whitespace-normal text-muted-foreground"
                                >
                                    No Executive Summaries have been collected
                                    yet.
                                </TableCell>
                            </TableRow>
                        )}
                    </TableBody>
                </Table>
            </Surface>
            <DifferenceDetail
                open={selection !== null && !guide.open}
                selection={selection}
                detail={shown}
                failed={failed}
                family={family}
                onClose={() => show(null)}
                onCloseAutoFocus={(event) => {
                    event.preventDefault();
                    opener.current?.focus();
                }}
                onExplain={(kind) => setGuide({ open: true, focus: kind })}
            />
            <ReportDifferencesGuide
                open={guide.open}
                focus={guide.focus}
                onOpenChange={(open) =>
                    setGuide({ open, focus: open ? guide.focus : null })
                }
            />
        </>
    );
}
