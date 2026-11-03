import { Head, Link } from '@inertiajs/react';
import { Pagination } from '@/components/capitol/pagination';
import { RowChevron, RowLink } from '@/components/row-link';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { formatCount } from '@/lib/format';
import type { DataEntry } from '@/types/data-entry';

const number = 'text-right font-mono tabular-nums';

/** Where every dataset is entered, and for Admins one list of everything waiting to be verified. */
export default function DataEntryPage({ entry }: { entry: DataEntry }) {
    return (
        <>
            <Head title="Data Entry" />
            <header>
                <h1 className="type-heading">Data Entry</h1>
            </header>
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Dataset</TableHead>
                        <TableHead>Shows On</TableHead>
                        <TableHead className="text-right">Pending</TableHead>
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
                                {dataset.lastChange ?? 'Not available'}
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
            {entry.review && (
                <section
                    aria-labelledby="review-heading"
                    className="flex min-w-0 flex-col gap-2"
                >
                    <h2 id="review-heading" className="type-item">
                        Waiting for Review
                    </h2>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Item</TableHead>
                                <TableHead>Dataset</TableHead>
                                <TableHead className="text-right">
                                    Pending
                                </TableHead>
                                <TableHead className="text-right">
                                    Since
                                </TableHead>
                                <TableHead className="w-0 px-1">
                                    <span className="sr-only">Open</span>
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {entry.review.length ? (
                                entry.review.map((row) => (
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
                                            {row.since}
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
                    {entry.pagination && <Pagination data={entry.pagination} />}
                </section>
            )}
        </>
    );
}
