import { WorkspaceFilters } from '@/components/capitol/workspace-filters';
import { Head, router } from '@inertiajs/react';
import { RowChevron, RowLink } from '@/components/row-link';
import { useEffect, useRef } from 'react';
import { cn } from '@/lib/utils';
import { Pagination } from '@/components/capitol/pagination';
import { Button } from '@/components/ui/button';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { formatCount } from '@/lib/format';
import { index, show } from '@/routes/capitol/finances';
import type { FinanceReviewList } from '@/types/finance';

const STATUS_OPTIONS = [{ value: 'pending', label: 'Pending' }];

export default function Finances({ list }: { list: FinanceReviewList }) {
    const cached = useRef(new Map<string, FinanceReviewList>());
    const cacheKey = (filters: FinanceReviewList['filters']) =>
        JSON.stringify([filters.year, filters.town, filters.status]);
    useEffect(() => {
        if (list.pagination.page === 1)
            cached.current.set(cacheKey(list.filters), list);
    }, [list]);
    const visit = (filters: FinanceReviewList['filters']) => {
        const url = index({ query: filters }).url;
        const saved = cached.current.get(cacheKey(filters));
        if (saved)
            router.push({
                url,
                props: (props) => ({ ...props, list: saved }),
                preserveState: true,
                preserveScroll: true,
            });
        else router.get(url, {}, { preserveState: true, preserveScroll: true });
    };
    const choose = (field: string, value: string) => {
        if (list.filters[field as keyof typeof list.filters] === value) return;
        visit({ ...list.filters, [field]: value === 'all' ? null : value });
    };
    const filters = [
        { key: 'town', label: 'Town or City', options: list.towns },
        { key: 'status', label: 'Status', options: STATUS_OPTIONS },
    ] as const;
    return (
        <>
            <Head title="Government Finances" />
            <h1 className="type-heading">Government Finances</h1>
            <nav
                aria-label="Report Year"
                className="flex gap-1 overflow-x-auto border-b"
            >
                {list.years.map((year) => (
                    <Button
                        key={year.value}
                        variant="ghost"
                        className={cn(
                            'shrink-0 rounded-none border-b-2 border-transparent font-mono tabular-nums',
                            list.filters.year === year.value &&
                                'border-primary bg-muted font-medium',
                        )}
                        aria-current={
                            list.filters.year === year.value
                                ? 'page'
                                : undefined
                        }
                        onClick={() => choose('year', year.value)}
                    >
                        {year.label}
                    </Button>
                ))}
            </nav>
            <div className="grid items-start gap-4 lg:grid-cols-[minmax(0,1fr)_16rem]">
                <section
                    aria-label="Finance reports"
                    className="flex min-w-0 flex-col gap-3"
                >
                    <div className="overflow-hidden rounded-sm bg-card">
                        <Table className="table-fixed">
                            <TableHeader>
                                <TableRow>
                                    <TableHead className="w-1/3">
                                        Town or City
                                    </TableHead>
                                    <TableHead className="text-right">
                                        Pending
                                    </TableHead>
                                    <TableHead className="text-right">
                                        Verified
                                    </TableHead>
                                    <TableHead className="text-right">
                                        Returned
                                    </TableHead>
                                    <TableHead className="w-0 px-1">
                                        <span className="sr-only">Open</span>
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {list.rows.length ? (
                                    list.rows.map((row) => (
                                        <TableRow key={row.townCode + row.year}>
                                            <TableCell className="whitespace-normal">
                                                <RowLink
                                                    href={show({
                                                        town: row.townCode,
                                                        year: row.year,
                                                    })}
                                                >
                                                    {row.town}
                                                </RowLink>
                                            </TableCell>
                                            <TableCell className="text-right font-mono tabular-nums">
                                                {formatCount(row.pending)}
                                            </TableCell>
                                            <TableCell className="text-right font-mono tabular-nums">
                                                {formatCount(row.verified)}
                                            </TableCell>
                                            <TableCell className="text-right font-mono tabular-nums">
                                                {formatCount(row.rejected)}
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
                                            className="py-8 text-center whitespace-normal text-muted-foreground"
                                        >
                                            No finance reports match these
                                            filters.
                                        </TableCell>
                                    </TableRow>
                                )}
                            </TableBody>
                        </Table>
                    </div>
                    <Pagination data={list.pagination} />
                </section>
                <WorkspaceFilters title="Filters">
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-1">
                        {filters.map(({ key, label, options }) => (
                            <div
                                key={key}
                                className="flex min-w-0 flex-col gap-2"
                            >
                                <label
                                    id={key + '-label'}
                                    className="type-label"
                                >
                                    {label}
                                </label>
                                <Select
                                    value={list.filters[key] ?? 'all'}
                                    onValueChange={(value) =>
                                        choose(key, value)
                                    }
                                >
                                    <SelectTrigger
                                        aria-labelledby={key + '-label'}
                                        className="w-full"
                                    >
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">All</SelectItem>
                                        {options.map((option) => (
                                            <SelectItem
                                                key={option.value}
                                                value={option.value}
                                            >
                                                {option.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                        ))}
                    </div>
                    <Button
                        variant="outline"
                        disabled={!list.filters.town && !list.filters.status}
                        onClick={() =>
                            visit({
                                year: list.filters.year,
                                town: null,
                                status: null,
                            })
                        }
                    >
                        Clear Filters
                    </Button>
                </WorkspaceFilters>
            </div>
        </>
    );
}
