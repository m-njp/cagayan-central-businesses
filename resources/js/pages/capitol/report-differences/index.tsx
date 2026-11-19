import { Head, router, usePage } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import { PageHeader } from '@/components/capitol/page-header';
import {
    WorkspaceFilterLayout,
    WorkspaceFilters,
} from '@/components/capitol/workspace-filters';
import { FigureStatus } from '@/components/registry-badges';
import { RowChevron, RowLink } from '@/components/row-link';
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
import { formatCount } from '@/lib/format';
import { icons } from '@/lib/icons';
import { index } from '@/routes/capitol/report-differences';
import type {
    ReportDifferenceCell,
    ReportDifferenceDetail,
    ReportDifferences,
} from '@/types/finance';
import { DifferenceDetail } from './difference-detail';
import { FAMILY_LABELS } from './finding-guide';
import { ReportDifferencesGuide } from './report-differences-guide';

type Selection = { town: string; year: number } | null;

const number = 'text-right font-mono tabular-nums';

function total(cell: ReportDifferenceCell) {
    return cell.report + cell.between + cell.reading;
}

function readSelection(url: string) {
    const query = new URLSearchParams(url.split('?')[1] ?? '');
    const town = query.get('town');
    const year = Number(query.get('year'));

    return {
        reportYear: Number(query.get('reportYear')) || null,
        selection: town && year ? { town, year } : null,
    };
}

/** One report year at a time: a row per municipality with its findings by category and whether review is complete. */
export default function ReportDifferencesPage({
    differences,
    detail,
}: {
    differences: ReportDifferences;
    detail?: ReportDifferenceDetail | null;
}) {
    const { url } = usePage();
    const { reportYear, selection } = readSelection(url);
    const years = [...differences.years].reverse();
    const year =
        years.find((item) => item === (selection?.year ?? reportYear)) ??
        years[0];
    // Findings for each report are fetched once; reopening one or going back uses this copy.
    const cached = useRef(new Map<string, ReportDifferenceDetail | null>());
    const [failed, setFailed] = useState(false);
    const [guide, setGuide] = useState<{ open: boolean; focus: string | null }>(
        { open: false, focus: null },
    );
    const key = selection ? `${selection.town}|${selection.year}` : null;
    const matches = (item: ReportDifferenceDetail | null | undefined) =>
        item?.townCode === selection?.town && item?.year === selection?.year;
    // Focus returns to the row that opened a report.
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

    const target = (next: Selection, nextYear: number) =>
        index({
            query: {
                reportYear: String(nextYear),
                ...(next ? { town: next.town, year: String(next.year) } : {}),
            },
        }).url;

    const show = (next: Selection, nextYear = year) => {
        const nextKey = next ? `${next.town}|${next.year}` : null;
        if (next && document.activeElement instanceof HTMLElement)
            opener.current = document.activeElement;
        setFailed(false);
        if (!nextKey || cached.current.has(nextKey)) {
            router.push({
                url: target(next, nextYear),
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
            target(next, nextYear),
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
        if (key && shown === undefined && !failed) show(selection);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [key]);

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
            <WorkspaceFilterLayout
                results={
                    <Surface>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Municipality</TableHead>
                                    <TableHead className="text-right">
                                        {FAMILY_LABELS.report}
                                    </TableHead>
                                    <TableHead className="text-right">
                                        {FAMILY_LABELS.between_reports}
                                    </TableHead>
                                    <TableHead className="text-right">
                                        {FAMILY_LABELS.reading}
                                    </TableHead>
                                    <TableHead className="text-right">
                                        Total
                                    </TableHead>
                                    <TableHead>Review</TableHead>
                                    <TableHead className="w-0" />
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {differences.rows.length ? (
                                    differences.rows.map((row) => {
                                        const cell = row.cells[year];
                                        if (!cell)
                                            return (
                                                <TableRow key={row.townCode}>
                                                    <TableCell>
                                                        {row.town}
                                                    </TableCell>
                                                    <TableCell
                                                        colSpan={6}
                                                        className="text-muted-foreground"
                                                    >
                                                        Not available
                                                    </TableCell>
                                                </TableRow>
                                            );
                                        const next = {
                                            town: row.townCode,
                                            year,
                                        };

                                        return (
                                            <TableRow key={row.townCode}>
                                                <TableCell>
                                                    <RowLink
                                                        href={target(
                                                            next,
                                                            year,
                                                        )}
                                                        onClick={(event) => {
                                                            event.preventDefault();
                                                            show(next);
                                                        }}
                                                    >
                                                        {row.town}
                                                    </RowLink>
                                                </TableCell>
                                                <TableCell className={number}>
                                                    {formatCount(cell.report)}
                                                </TableCell>
                                                <TableCell className={number}>
                                                    {formatCount(cell.between)}
                                                </TableCell>
                                                <TableCell className={number}>
                                                    {formatCount(cell.reading)}
                                                </TableCell>
                                                <TableCell
                                                    className={`${number} font-medium`}
                                                >
                                                    {formatCount(total(cell))}
                                                </TableCell>
                                                <TableCell>
                                                    <FigureStatus
                                                        status={
                                                            cell.pending > 0
                                                                ? 'pending'
                                                                : 'verified'
                                                        }
                                                    />
                                                </TableCell>
                                                <TableCell className="w-0 px-1">
                                                    <RowChevron />
                                                </TableCell>
                                            </TableRow>
                                        );
                                    })
                                ) : (
                                    <TableRow>
                                        <TableCell
                                            colSpan={7}
                                            className="py-8 text-center whitespace-normal text-muted-foreground"
                                        >
                                            No Executive Summaries have been
                                            collected yet.
                                        </TableCell>
                                    </TableRow>
                                )}
                            </TableBody>
                        </Table>
                    </Surface>
                }
            >
                <WorkspaceFilters>
                    <Field orientation="horizontal" className="gap-2">
                        <FieldLabel
                            htmlFor="report-year"
                            className="type-label"
                        >
                            Report Year
                        </FieldLabel>
                        <Select
                            value={String(year)}
                            onValueChange={(value) => show(null, Number(value))}
                        >
                            <SelectTrigger
                                id="report-year"
                                variant="quiet"
                                size="sm"
                                className="font-mono tabular-nums"
                            >
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent align="end">
                                {years.map((item) => (
                                    <SelectItem key={item} value={String(item)}>
                                        {item}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </Field>
                </WorkspaceFilters>
            </WorkspaceFilterLayout>
            <DifferenceDetail
                open={selection !== null && !guide.open}
                selection={selection}
                detail={shown}
                failed={failed}
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
