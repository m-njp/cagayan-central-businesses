import { Head, Link, router } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { CategoryChip } from '@/components/capitol/category-chip';
import { Pagination } from '@/components/capitol/pagination';
import { WorkspaceFilters } from '@/components/capitol/workspace-filters';
import { PermitBadge, RecordBadge } from '@/components/registry-badges';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
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
import { pageSearchParams } from '@/lib/navigation';
import {
    BUSINESS_STATUS_DOTS,
    BUSINESS_STATUS_LABELS,
    RECORD_STATUS,
} from '@/lib/registry';
import { create, index, show } from '@/routes/capitol/businesses';
import type { BusinessDirectory } from '@/types/workspace';

type Filter = 'barangay' | 'category' | 'type' | 'industry' | 'status';

const TABS = [null, 'submitted', 'returned', 'draft'] as const;

export default function Businesses({
    directory,
}: {
    directory: BusinessDirectory;
}) {
    const { filters } = directory;
    const [search, setSearch] = useState(filters.q ?? '');
    const byCode = Object.fromEntries(
        directory.categories.map((category) => [category.code, category]),
    );
    const active = !!(
        filters.q ||
        filters.barangay ||
        filters.category ||
        filters.type ||
        filters.industry ||
        filters.status
    );

    // Rows are paged on the server, so a change asks for the matching page.
    const go = (patch: Record<string, string | null>, replace = false) => {
        const query = Object.fromEntries(
            [...pageSearchParams(window.location.href)].filter(
                ([key]) => key !== 'page',
            ),
        );
        for (const [key, value] of Object.entries(patch)) {
            if (value) query[key] = value;
            else delete query[key];
        }
        router.get(
            index({ query }).url,
            {},
            { preserveState: true, preserveScroll: true, replace },
        );
    };
    const sortBy = (key: string) => {
        const current = filters.sort === key;
        go({
            sort: key === 'name' && !current ? null : key,
            dir: current && filters.dir === 'asc' ? 'desc' : null,
        });
    };

    useEffect(() => setSearch(filters.q ?? ''), [filters.q]);
    useEffect(() => {
        if (search === (filters.q ?? '')) return;
        const timer = setTimeout(() => go({ q: search || null }, true), 300);
        return () => clearTimeout(timer);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [search]);

    const sortable = (label: string, key: string, align = '') => (
        <TableHead
            aria-sort={
                filters.sort === key
                    ? filters.dir === 'desc'
                        ? 'descending'
                        : 'ascending'
                    : 'none'
            }
            className={align}
        >
            <button
                type="button"
                onClick={() => sortBy(key)}
                className="underline-offset-2 hover:underline"
            >
                {label}
                {filters.sort === key && (
                    <span aria-hidden="true">
                        {filters.dir === 'desc' ? ' ↓' : ' ↑'}
                    </span>
                )}
            </button>
        </TableHead>
    );
    const select = (
        field: Filter,
        label: string,
        options: { value: string; label: string }[],
    ) => (
        <>
            <label id={`${field}-label`} className="type-label">
                {label}
            </label>
            <Select
                value={filters[field] ?? 'all'}
                onValueChange={(value) =>
                    go({ [field]: value === 'all' ? null : value })
                }
            >
                <SelectTrigger
                    aria-labelledby={`${field}-label`}
                    className="w-full"
                >
                    <SelectValue />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="all">All</SelectItem>
                    {options.map((option) => (
                        <SelectItem key={option.value} value={option.value}>
                            {option.label}
                        </SelectItem>
                    ))}
                </SelectContent>
            </Select>
        </>
    );

    return (
        <>
            <Head title="Businesses" />
            <header className="flex flex-wrap items-center justify-between gap-3">
                <h1 className="type-heading">Businesses</h1>
                {directory.canAdd && (
                    <Button asChild>
                        <Link href={create()}>Add a Business</Link>
                    </Button>
                )}
            </header>
            <div
                role="tablist"
                aria-label="Review State"
                className="flex flex-wrap gap-2"
            >
                {TABS.map((tab) => {
                    const count = tab ? directory.recordCounts[tab] : undefined;
                    return (
                        <Button
                            key={tab ?? 'all'}
                            role="tab"
                            aria-selected={filters.record === tab}
                            variant={
                                filters.record === tab ? 'secondary' : 'ghost'
                            }
                            size="sm"
                            onClick={() => go({ record: tab })}
                        >
                            {tab ? RECORD_STATUS[tab] : 'All'}
                            {count ? (
                                <span className="font-mono text-muted-foreground tabular-nums">
                                    {count}
                                </span>
                            ) : null}
                        </Button>
                    );
                })}
            </div>
            <div className="grid items-start gap-4 lg:grid-cols-[minmax(0,1fr)_16rem]">
                <div className="min-w-0 space-y-4">
                    <div className="overflow-x-auto rounded-sm bg-card">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    {sortable('Business Name', 'name')}
                                    {sortable('Business Type', 'type')}
                                    <TableHead>Category</TableHead>
                                    <TableHead>Barangay</TableHead>
                                    {sortable(
                                        'Established',
                                        'established',
                                        'text-right',
                                    )}
                                    <TableHead>Status</TableHead>
                                    <TableHead>Permit</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {directory.rows.length === 0 && (
                                    <TableRow>
                                        <TableCell
                                            colSpan={7}
                                            className="py-8 text-center text-muted-foreground"
                                        >
                                            {active || filters.record
                                                ? 'No businesses match these filters.'
                                                : 'No registered businesses yet.'}
                                        </TableCell>
                                    </TableRow>
                                )}
                                {directory.rows.map((row) => (
                                    <TableRow key={row.publicId}>
                                        <TableCell className="whitespace-normal">
                                            <span className="flex flex-wrap items-center gap-2">
                                                <Link
                                                    href={show(row.publicId)}
                                                    className="underline-offset-2 hover:underline"
                                                >
                                                    {row.name}
                                                </Link>
                                                <RecordBadge
                                                    status={row.recordStatus}
                                                />
                                            </span>
                                        </TableCell>
                                        <TableCell className="whitespace-normal">
                                            {row.type}
                                        </TableCell>
                                        <TableCell>
                                            {byCode[row.category] && (
                                                <CategoryChip
                                                    name={
                                                        byCode[row.category]
                                                            .name
                                                    }
                                                    color={
                                                        byCode[row.category]
                                                            .color
                                                    }
                                                />
                                            )}
                                        </TableCell>
                                        <TableCell>{row.barangay}</TableCell>
                                        <TableCell className="text-right font-mono tabular-nums">
                                            {row.yearEstablished}
                                        </TableCell>
                                        <TableCell>
                                            {row.status && (
                                                <span className="inline-flex items-center gap-1.5 type-meta">
                                                    <span
                                                        className={`size-2 rounded-full ${BUSINESS_STATUS_DOTS[row.status] ?? ''}`}
                                                        aria-hidden="true"
                                                    />
                                                    {BUSINESS_STATUS_LABELS[
                                                        row.status
                                                    ] ?? row.status}
                                                </span>
                                            )}
                                        </TableCell>
                                        <TableCell>
                                            <PermitBadge
                                                status={row.permitStatus}
                                            />
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </div>
                    <Pagination data={directory.pagination} />
                </div>
                <WorkspaceFilters>
                    <label className="flex flex-col gap-2 type-label">
                        Search
                        <Input
                            type="search"
                            value={search}
                            maxLength={60}
                            onChange={(event) => setSearch(event.target.value)}
                        />
                    </label>
                    {select('barangay', 'Barangay', directory.barangays)}
                    {select(
                        'category',
                        'Category',
                        directory.categories.map((category) => ({
                            value: category.code,
                            label: category.name,
                        })),
                    )}
                    {select('type', 'Business Type', directory.types)}
                    {select('industry', 'Industry', directory.industries)}
                    {select('status', 'Status', directory.statuses)}
                    {active && (
                        <Button
                            variant="ghost"
                            onClick={() => {
                                setSearch('');
                                go({
                                    q: null,
                                    barangay: null,
                                    category: null,
                                    type: null,
                                    industry: null,
                                    status: null,
                                });
                            }}
                        >
                            Clear Filters
                        </Button>
                    )}
                </WorkspaceFilters>
            </div>
        </>
    );
}
