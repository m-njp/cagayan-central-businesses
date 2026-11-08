import { Head, Link } from '@inertiajs/react';
import { PageHeader } from '@/components/capitol/page-header';
import { Pagination } from '@/components/capitol/pagination';
import { ReviewQueueTable } from '@/components/capitol/review-queue-table';
import { RowLink } from '@/components/row-link';
import { Button } from '@/components/ui/button';
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
import type { DataEntry } from '@/types/data-entry';

const number = 'text-right font-mono tabular-nums';

/** Where every dataset is entered, and for Admins one list of everything waiting to be verified. */
export default function DataEntryPage({ entry }: { entry: DataEntry }) {
    return (
        <>
            <Head title="Data Entry" />
            <PageHeader title="Data Entry" />
            <Surface>
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Dataset</TableHead>
                            <TableHead>Shows On</TableHead>
                            <TableHead className="text-right">
                                Pending
                            </TableHead>
                            <TableHead className="text-right">
                                Last Change
                            </TableHead>
                            <TableHead className="w-0" />
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {entry.datasets.map((dataset) => (
                            <TableRow key={dataset.id}>
                                <TableCell className="whitespace-normal">
                                    <RowLink href={dataset.href}>
                                        {dataset.label}
                                    </RowLink>
                                </TableCell>
                                <TableCell className="whitespace-normal text-muted-foreground">
                                    {dataset.shownOn.join(', ')}
                                </TableCell>
                                <TableCell className={number}>
                                    {dataset.pending === null
                                        ? 'Applies on Save'
                                        : formatCount(dataset.pending)}
                                </TableCell>
                                <TableCell className={number}>
                                    {dataset.lastChange
                                        ? formatEntryTimestamp(
                                              dataset.lastChange,
                                          )
                                        : 'Not available'}
                                </TableCell>
                                <TableCell className="py-1">
                                    {dataset.action && dataset.actionHref && (
                                        <Button
                                            asChild
                                            size="sm"
                                            variant="outline"
                                            className="relative z-10"
                                        >
                                            <Link href={dataset.actionHref}>
                                                {dataset.action}
                                            </Link>
                                        </Button>
                                    )}
                                </TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>
            </Surface>
            {entry.review && (
                <ReviewQueueTable
                    rows={entry.review}
                    footer={
                        entry.pagination && (
                            <Pagination data={entry.pagination} />
                        )
                    }
                />
            )}
        </>
    );
}
