import { Fragment } from 'react';
import { DialogTitle } from '@/components/ui/dialog';
import { FocusDialog } from '@/components/ui/focus-dialog';
import { Skeleton } from '@/components/ui/skeleton';
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
import type {
    ReportDifferenceDetail,
    ReportDifferenceFamily,
} from '@/types/finance';
import { FAMILY_LABELS, findingEntry, kindLabel } from './finding-guide';

function ReportLink({ href, label }: { href: string; label: string }) {
    return (
        <a
            href={href}
            target="_blank"
            rel="noreferrer"
            className="underline underline-offset-4"
        >
            {label}
        </a>
    );
}

export function DifferenceDetail({
    open,
    selection,
    detail,
    failed,
    onClose,
    onCloseAutoFocus,
    onExplain,
}: {
    open: boolean;
    selection: { town: string; year: number } | null;
    detail: ReportDifferenceDetail | null | undefined;
    failed: boolean;
    onClose: () => void;
    onCloseAutoFocus: (event: Event) => void;
    onExplain: (kind: string) => void;
}) {
    const families = (Object.keys(FAMILY_LABELS) as ReportDifferenceFamily[])
        .map((name) => ({
            name,
            items: detail?.items.filter((item) => item.family === name) ?? [],
        }))
        .filter((group) => group.items.length > 0);

    return (
        <FocusDialog
            open={open}
            onOpenChange={(next) => !next && onClose()}
            closeButton
            onCloseAutoFocus={onCloseAutoFocus}
            className="max-h-dvh w-full max-w-5xl overflow-y-auto px-4 py-14 sm:max-w-5xl"
        >
            <div className="flex min-w-0 flex-col gap-3">
                <DialogTitle className="type-heading">
                    {detail
                        ? `${detail.town} ${detail.year} Report`
                        : selection
                          ? `${selection.year} Report`
                          : 'Report'}
                </DialogTitle>
                {failed ? (
                    <p className="text-muted-foreground">
                        The findings for this report could not be loaded. Close
                        this view and open the report again.
                    </p>
                ) : detail === undefined ? (
                    <div className="flex flex-col gap-2" aria-busy="true">
                        <Skeleton className="h-row w-full" />
                        <Skeleton className="h-40 w-full" />
                    </div>
                ) : detail === null ? (
                    <p className="text-muted-foreground">Not available</p>
                ) : (
                    <>
                        <Surface>
                            <Table className="table-fixed">
                                <TableHeader>
                                    <TableRow>
                                        <TableHead className="text-right">
                                            Verified
                                        </TableHead>
                                        <TableHead className="text-right">
                                            Corroborated
                                        </TableHead>
                                        <TableHead className="text-right">
                                            Pending
                                        </TableHead>
                                        <TableHead>Source Report</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow>
                                        <TableCell className="text-right font-mono tabular-nums">
                                            {formatCount(detail.verified)}
                                        </TableCell>
                                        <TableCell className="text-right font-mono tabular-nums">
                                            {formatCount(detail.corroborated)}
                                        </TableCell>
                                        <TableCell className="text-right font-mono tabular-nums">
                                            {formatCount(detail.pending)}
                                        </TableCell>
                                        <TableCell>
                                            {detail.sources.length > 0 ? (
                                                <ul className="grid gap-0.5">
                                                    {detail.sources.map(
                                                        (source) => (
                                                            <li
                                                                key={
                                                                    source.label
                                                                }
                                                            >
                                                                <ReportLink
                                                                    href={
                                                                        source.url
                                                                    }
                                                                    label={`${source.label} ${detail.year}`}
                                                                />
                                                            </li>
                                                        ),
                                                    )}
                                                </ul>
                                            ) : (
                                                <span className="text-muted-foreground">
                                                    Not available
                                                </span>
                                            )}
                                        </TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </Surface>
                        <Surface className="overflow-x-auto">
                            <Table className="min-w-2xl">
                                <TableHeader>
                                    <TableRow>
                                        <TableHead className="w-48">
                                            Finding
                                        </TableHead>
                                        <TableHead className="w-44">
                                            Line
                                        </TableHead>
                                        <TableHead className="w-16 text-right">
                                            Year
                                        </TableHead>
                                        <TableHead>Detail</TableHead>
                                        <TableHead className="w-20">
                                            Page
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {families.length ? (
                                        families.map((group) => (
                                            <Fragment key={group.name}>
                                                <TableRow data-plain>
                                                    <TableCell
                                                        colSpan={5}
                                                        className="type-item"
                                                    >
                                                        {
                                                            FAMILY_LABELS[
                                                                group.name
                                                            ]
                                                        }
                                                    </TableCell>
                                                </TableRow>
                                                {group.items.map(
                                                    (item, position) => (
                                                        <TableRow
                                                            key={`${item.kind}${position}`}
                                                        >
                                                            <TableCell className="whitespace-normal">
                                                                {findingEntry(
                                                                    item.kind,
                                                                ) ? (
                                                                    <button
                                                                        type="button"
                                                                        onClick={() =>
                                                                            onExplain(
                                                                                item.kind,
                                                                            )
                                                                        }
                                                                        aria-label={`${kindLabel(item.kind)}, open its explanation in the guide`}
                                                                        className="cursor-pointer text-left underline underline-offset-4"
                                                                    >
                                                                        {kindLabel(
                                                                            item.kind,
                                                                        )}
                                                                    </button>
                                                                ) : (
                                                                    kindLabel(
                                                                        item.kind,
                                                                    )
                                                                )}
                                                            </TableCell>
                                                            <TableCell className="whitespace-normal">
                                                                {item.line ?? (
                                                                    <span className="text-muted-foreground">
                                                                        Whole
                                                                        document
                                                                    </span>
                                                                )}
                                                                <span className="block type-data text-muted-foreground">
                                                                    {
                                                                        item.document
                                                                    }
                                                                </span>
                                                            </TableCell>
                                                            <TableCell className="text-right font-mono tabular-nums">
                                                                {item.fiscalYear ??
                                                                    ''}
                                                            </TableCell>
                                                            <TableCell className="text-sm break-words whitespace-normal text-muted-foreground">
                                                                {item.message}
                                                            </TableCell>
                                                            <TableCell>
                                                                {item.sourceUrl?.includes(
                                                                    '#page=',
                                                                ) ? (
                                                                    <a
                                                                        href={
                                                                            item.sourceUrl
                                                                        }
                                                                        target="_blank"
                                                                        rel="noreferrer"
                                                                        className="font-mono whitespace-nowrap underline underline-offset-4"
                                                                    >
                                                                        Page{' '}
                                                                        {
                                                                            item.sourceUrl.split(
                                                                                '#page=',
                                                                            )[1]
                                                                        }
                                                                    </a>
                                                                ) : null}
                                                            </TableCell>
                                                        </TableRow>
                                                    ),
                                                )}
                                            </Fragment>
                                        ))
                                    ) : (
                                        <TableRow>
                                            <TableCell
                                                colSpan={5}
                                                className="py-8 text-center whitespace-normal text-muted-foreground"
                                            >
                                                No findings for this report.
                                            </TableCell>
                                        </TableRow>
                                    )}
                                </TableBody>
                            </Table>
                        </Surface>
                    </>
                )}
            </div>
        </FocusDialog>
    );
}
