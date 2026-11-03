import { Head, Link, router, usePage } from '@inertiajs/react';
import { ArrowDown, ArrowUp, ArrowUpDown } from 'lucide-react';
import { useEffect, useState } from 'react';
import { CategoryChip } from '@/components/capitol/category-chip';
import {
    BusinessCategoryForm,
    BusinessTypeForm,
} from '@/components/capitol/category-forms';
import { WorkspaceFilters } from '@/components/capitol/workspace-filters';
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
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import {
    categoryQuery,
    paginate,
    parseCategoryView,
    visibleCategories,
    visibleTypes,
} from '@/lib/category-table';
import type { CategoryView } from '@/lib/category-table';
import { NOT_AVAILABLE, formatCount } from '@/lib/format';
import { pageSearchParams } from '@/lib/navigation';
import { index as businesses } from '@/routes/capitol/businesses';
import { index } from '@/routes/capitol/categories';
import type { BusinessCategories } from '@/types/workspace';

const count = (value: number | null) =>
    value === null ? NOT_AVAILABLE : formatCount(value);

export default function BusinessCategoriesPage({
    categories,
}: {
    categories: BusinessCategories;
}) {
    const { url } = usePage();
    const state = parseCategoryView(
        pageSearchParams(url),
        categories.categories,
        categories.industries,
    );
    const [sheet, setSheet] = useState<{
        kind: 'type' | 'category';
        code: string | null;
    } | null>(null);
    const [search, setSearch] = useState(state.q);
    const byCode = Object.fromEntries(
        categories.categories.map((item) => [item.code, item]),
    );
    const names = Object.fromEntries(
        categories.categories.map((item) => [item.code, item.name]),
    );
    const typePage = paginate(
        visibleTypes(categories.types, state, names),
        state.page,
        categories.pageSize,
    );
    const categoryPage = paginate(
        visibleCategories(categories.categories, state),
        state.page,
        categories.pageSize,
    );
    const page = state.view === 'types' ? typePage : categoryPage;
    const filtered = !!(state.q || state.category || state.industry);
    const noun = state.view === 'types' ? 'Business Types' : 'Categories';
    const editingType =
        categories.types.find((item) => item.code === sheet?.code) ?? null;
    const editingCategory =
        categories.categories.find((item) => item.code === sheet?.code) ?? null;

    // Every row is loaded, so sorting, filters and pages only change local history.
    const go = (patch: Partial<CategoryView>, replace = false) => {
        const query = categoryQuery({ ...state, page: 1, ...patch });
        if (JSON.stringify(query) === JSON.stringify(categoryQuery(state)))
            return;
        const visit = {
            url: index({ query }).url,
            preserveState: true,
            preserveScroll: true,
        };
        if (replace) router.replace(visit);
        else router.push(visit);
    };
    const sortBy = (key: string) =>
        go({
            sort: key,
            dir:
                state.sort === key
                    ? state.dir === 'asc'
                        ? 'desc'
                        : 'asc'
                    : key === 'registered'
                      ? 'desc'
                      : 'asc',
        });

    useEffect(() => setSearch(state.q), [state.q]);
    useEffect(() => {
        const timer = setTimeout(() => go({ q: search }, true), 250);
        return () => clearTimeout(timer);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [search]);

    const SortHead = ({
        label,
        field,
        align,
    }: {
        label: string;
        field: string;
        align?: 'right';
    }) => {
        const Icon =
            state.sort !== field
                ? ArrowUpDown
                : state.dir === 'asc'
                  ? ArrowUp
                  : ArrowDown;
        return (
            <TableHead
                aria-sort={
                    state.sort === field
                        ? state.dir === 'asc'
                            ? 'ascending'
                            : 'descending'
                        : 'none'
                }
                className={align ? 'text-right' : ''}
            >
                <button
                    type="button"
                    onClick={() => sortBy(field)}
                    className="inline-flex items-center gap-1 underline-offset-2 hover:underline"
                >
                    {label}
                    <Icon className="size-3.5" aria-hidden="true" />
                </button>
            </TableHead>
        );
    };
    const registered =
        categories.year === null
            ? 'Registered'
            : `Registered (${categories.year})`;
    const actions = (
        <TableHead className="w-24">
            <span className="sr-only">Actions</span>
        </TableHead>
    );

    return (
        <>
            <Head title="Business Categories" />
            <header className="flex flex-wrap items-center justify-between gap-3">
                <h1 className="type-heading">Business Categories</h1>
                <div className="flex flex-wrap items-center gap-2">
                    <div
                        role="tablist"
                        aria-label="View"
                        className="flex gap-1"
                    >
                        {(['categories', 'types'] as const).map((view) => (
                            <Button
                                key={view}
                                role="tab"
                                aria-selected={state.view === view}
                                variant={
                                    state.view === view ? 'default' : 'outline'
                                }
                                size="sm"
                                onClick={() =>
                                    go({ view, sort: 'name', dir: 'asc' })
                                }
                            >
                                {view === 'categories'
                                    ? 'Categories'
                                    : 'Business Types'}
                            </Button>
                        ))}
                    </div>
                    <Button
                        onClick={() =>
                            setSheet({
                                kind:
                                    state.view === 'types'
                                        ? 'type'
                                        : 'category',
                                code: null,
                            })
                        }
                    >
                        {state.view === 'types'
                            ? 'Add Business Type'
                            : 'Add Category'}
                    </Button>
                </div>
            </header>
            <div className="grid items-start gap-4 lg:grid-cols-[minmax(0,1fr)_16rem]">
                <div className="min-w-0 space-y-4">
                    <div className="overflow-x-auto rounded-sm bg-card">
                        <Table>
                            <TableHeader>
                                {state.view === 'types' ? (
                                    <TableRow>
                                        <SortHead
                                            label="Business Type"
                                            field="name"
                                        />
                                        <SortHead
                                            label="Category"
                                            field="category"
                                        />
                                        <SortHead
                                            label="Industry"
                                            field="industry"
                                        />
                                        <SortHead
                                            label={registered}
                                            field="registered"
                                            align="right"
                                        />
                                        {actions}
                                    </TableRow>
                                ) : (
                                    <TableRow>
                                        <SortHead
                                            label="Category"
                                            field="name"
                                        />
                                        <SortHead
                                            label="Business Types"
                                            field="types"
                                            align="right"
                                        />
                                        <SortHead
                                            label={registered}
                                            field="registered"
                                            align="right"
                                        />
                                        {actions}
                                    </TableRow>
                                )}
                            </TableHeader>
                            <TableBody>
                                {page.total === 0 && (
                                    <TableRow>
                                        <TableCell
                                            colSpan={5}
                                            className="py-8 text-center text-muted-foreground"
                                        >
                                            {filtered
                                                ? `No ${noun} match.`
                                                : `No ${noun} yet.`}
                                        </TableCell>
                                    </TableRow>
                                )}
                                {state.view === 'types' &&
                                    typePage.rows.map((row) => (
                                        <TableRow key={row.code}>
                                            <TableCell className="whitespace-normal">
                                                {row.name}
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
                                            <TableCell className="max-w-52 whitespace-normal text-muted-foreground">
                                                {row.industry}
                                            </TableCell>
                                            <TableCell className="text-right font-mono tabular-nums">
                                                {row.registered === null ? (
                                                    count(row.registered)
                                                ) : (
                                                    <Link
                                                        href={
                                                            businesses({
                                                                query: {
                                                                    type: row.code,
                                                                },
                                                            }).url
                                                        }
                                                        className="underline underline-offset-2"
                                                    >
                                                        {count(row.registered)}
                                                    </Link>
                                                )}
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    aria-label={`Edit ${row.name}`}
                                                    onClick={() =>
                                                        setSheet({
                                                            kind: 'type',
                                                            code: row.code,
                                                        })
                                                    }
                                                >
                                                    Edit
                                                </Button>
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                {state.view === 'categories' &&
                                    categoryPage.rows.map((row) => (
                                        <TableRow key={row.code}>
                                            <TableCell>
                                                <CategoryChip
                                                    name={row.name}
                                                    color={row.color}
                                                />
                                            </TableCell>
                                            <TableCell className="text-right font-mono tabular-nums">
                                                {formatCount(row.types)}
                                            </TableCell>
                                            <TableCell className="text-right font-mono tabular-nums">
                                                {row.registered === null ? (
                                                    count(row.registered)
                                                ) : (
                                                    <Link
                                                        href={
                                                            businesses({
                                                                query: {
                                                                    category:
                                                                        row.code,
                                                                },
                                                            }).url
                                                        }
                                                        className="underline underline-offset-2"
                                                    >
                                                        {count(row.registered)}
                                                    </Link>
                                                )}
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    aria-label={`Edit ${row.name}`}
                                                    onClick={() =>
                                                        setSheet({
                                                            kind: 'category',
                                                            code: row.code,
                                                        })
                                                    }
                                                >
                                                    Edit
                                                </Button>
                                            </TableCell>
                                        </TableRow>
                                    ))}
                            </TableBody>
                        </Table>
                    </div>
                    {page.lastPage > 1 && (
                        <nav
                            aria-label="Pagination"
                            className="flex items-center justify-end gap-2 border-t pt-2"
                        >
                            <Button
                                variant="ghost"
                                size="sm"
                                disabled={page.page <= 1}
                                onClick={() => go({ page: page.page - 1 })}
                            >
                                Previous
                            </Button>
                            <span className="type-meta">
                                Page{' '}
                                <span className="font-mono tabular-nums">
                                    {page.page}
                                </span>{' '}
                                of{' '}
                                <span className="font-mono tabular-nums">
                                    {page.lastPage}
                                </span>
                            </span>
                            <Button
                                variant="ghost"
                                size="sm"
                                disabled={page.page >= page.lastPage}
                                onClick={() => go({ page: page.page + 1 })}
                            >
                                Next
                            </Button>
                        </nav>
                    )}
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
                    {state.view === 'types' && (
                        <>
                            <label id="category-label" className="type-label">
                                Category
                            </label>
                            <Select
                                value={state.category ?? 'all'}
                                onValueChange={(value) =>
                                    go({
                                        category:
                                            value === 'all' ? null : value,
                                    })
                                }
                            >
                                <SelectTrigger
                                    aria-labelledby="category-label"
                                    className="w-full"
                                >
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All</SelectItem>
                                    {categories.categories.map((item) => (
                                        <SelectItem
                                            key={item.code}
                                            value={item.code}
                                        >
                                            {item.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <label id="industry-label" className="type-label">
                                Industry
                            </label>
                            <Select
                                value={state.industry ?? 'all'}
                                onValueChange={(value) =>
                                    go({
                                        industry:
                                            value === 'all' ? null : value,
                                    })
                                }
                            >
                                <SelectTrigger
                                    aria-labelledby="industry-label"
                                    className="w-full"
                                >
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All</SelectItem>
                                    {categories.industries.map((industry) => (
                                        <SelectItem
                                            key={industry.value}
                                            value={industry.value}
                                        >
                                            {industry.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </>
                    )}
                    {filtered && (
                        <Button
                            variant="ghost"
                            onClick={() => {
                                setSearch('');
                                go({ q: '', category: null, industry: null });
                            }}
                        >
                            Clear Filters
                        </Button>
                    )}
                </WorkspaceFilters>
            </div>
            <Sheet
                open={sheet !== null}
                onOpenChange={(open) => !open && setSheet(null)}
            >
                <SheetContent className="w-full overflow-y-auto sm:max-w-md">
                    <SheetHeader>
                        <SheetTitle>
                            {sheet?.kind === 'category'
                                ? (editingCategory?.name ?? 'Add Category')
                                : (editingType?.name ?? 'Add Business Type')}
                        </SheetTitle>
                    </SheetHeader>
                    {sheet?.kind === 'type' && (
                        <BusinessTypeForm
                            key={sheet.code ?? 'new'}
                            type={editingType}
                            categories={categories}
                            onSaved={() => setSheet(null)}
                        />
                    )}
                    {sheet?.kind === 'category' && (
                        <BusinessCategoryForm
                            key={sheet.code ?? 'new'}
                            category={editingCategory}
                            categories={categories}
                            onSaved={() => setSheet(null)}
                        />
                    )}
                </SheetContent>
            </Sheet>
        </>
    );
}
