import type { ReactNode } from 'react';
import { RowChevron, RowLink } from '@/components/row-link';
import { Surface } from '@/components/ui/surface';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { formatCount, formatEntryTimestamp } from '@/lib/format';
import type { DataEntryReviewRow } from '@/types/data-entry';

const number = 'text-right font-mono tabular-nums';

// Everything waiting to be verified, oldest first. The Dashboard and Data Entry both show this one table.
export function ReviewQueueTable({
    rows,
    footer,
    total,
}: {
    rows: DataEntryReviewRow[];
    footer?: ReactNode;
    total?: number;
}) {
    return (
        <Surface
            title="Waiting for Review"
            toolbar={
                total === undefined ? undefined : (
                    <span className="type-meta">
                        <span className="font-mono tabular-nums">
                            {formatCount(total)}
                        </span>{' '}
                        {total === 1 ? 'item' : 'items'}
                    </span>
                )
            }
            footer={footer}
        >
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Item</TableHead>
                        <TableHead>Dataset</TableHead>
                        <TableHead className="text-right">Pending</TableHead>
                        <TableHead className="text-right">Since</TableHead>
                        <TableHead className="w-0 px-1">
                            <span className="sr-only">Open</span>
                        </TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    {rows.length ? (
                        rows.map((row) => (
                            <TableRow key={row.href}>
                                <TableCell className="whitespace-normal">
                                    <RowLink href={row.href}>
                                        {row.item}
                                    </RowLink>
                                </TableCell>
                                <TableCell>{row.dataset}</TableCell>
                                <TableCell className={number}>
                                    {formatCount(row.count)}
                                </TableCell>
                                <TableCell className={number}>
                                    {formatEntryTimestamp(row.since)}
                                </TableCell>
                                <TableCell className="w-0 px-1">
                                    <RowChevron />
                                </TableCell>
                            </TableRow>
                        ))
                    ) : (
                        <TableRow>
                            <TableCell
                                colSpan={5}
                                className="py-6 text-center whitespace-normal text-muted-foreground"
                            >
                                Nothing is waiting for review.
                            </TableCell>
                        </TableRow>
                    )}
                </TableBody>
            </Table>
        </Surface>
    );
}
