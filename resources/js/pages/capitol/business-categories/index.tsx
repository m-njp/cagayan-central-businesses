import { Head, router, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { CategoryChip } from '@/components/capitol/category-chip';
import { BusinessCategoryForm, BusinessTypeForm } from './category-forms';
import { EditButton } from '@/components/capitol/edit-button';
import { PageHeader } from '@/components/capitol/page-header';
import { Pagination } from '@/components/capitol/pagination';
import { SearchField } from '@/components/search-field';
import { Button } from '@/components/ui/button';
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
import { Surface } from '@/components/ui/surface';
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
    visibleTypes,
} from '@/lib/category-table';
import type { CategoryView } from '@/lib/category-table';
import { formatCount } from '@/lib/format';
import { pageSearchParams } from '@/lib/navigation';
import { index } from '@/routes/capitol/categories';
import type { BusinessCategories } from '@/types/workspace';
import { BusinessTypesTable } from './business-types-table';

type Panel = { kind: 'type' | 'category' | 'categories'; code: string | null };

// The URL carries only what differs from the one table's defaults, so the fixed view is left out.
function urlQuery(state: CategoryView) {
    const query = categoryQuery(state);
    delete query.view;
    return query;
}

export default function BusinessCategoriesPage({
    categories,
}: {
    categories: BusinessCategories;
}) {
    const { url } = usePage();
    // One table of Business Types: the old view parameter is ignored.
    const params = pageSearchParams(url);
    params.set('view', 'types');
    const state = parseCategoryView(
        params,
        categories.categories,
        categories.industries,
    );
    const [sheet, setSheet] = useState<Panel | null>(null);
    const [search, setSearch] = useState(state.q);
    const names = Object.fromEntries(
        categories.categories.map((item) => [item.code, item.name]),
    );
    const page = paginate(
        visibleTypes(categories.types, state, names),
        state.page,
        categories.pageSize,
    );
    const filtered = !!(state.q || state.category || state.industry);
    const editingType =
        categories.types.find((item) => item.code === sheet?.code) ?? null;
    const editingCategory =
        categories.categories.find((item) => item.code === sheet?.code) ?? null;

    // Every row is loaded, so sorting, filters and pages only change local history.
    const go = (patch: Partial<CategoryView>, replace = false) => {
        const query = urlQuery({ ...state, page: 1, ...patch });
        if (JSON.stringify(query) === JSON.stringify(urlQuery(state))) return;
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

    const filter = (
        label: string,
        value: string | null,
        options: { value: string; label: string }[],
        onChange: (value: string | null) => void,
    ) => (
        <Select
            value={value ?? 'all'}
            onValueChange={(next) => onChange(next === 'all' ? null : next)}
        >
            <SelectTrigger
                variant="quiet"
                size="sm"
                aria-label={label}
                className="w-40"
            >
                <SelectValue />
            </SelectTrigger>
            <SelectContent>
                <SelectItem value="all">{`All ${label}`}</SelectItem>
                {options.map((option) => (
                    <SelectItem key={option.value} value={option.value}>
                        {option.label}
                    </SelectItem>
                ))}
            </SelectContent>
        </Select>
    );

    return (
        <>
            <Head title="Business Categories" />
            <PageHeader title="Business Categories">
                <Button
                    variant="outline"
                    onClick={() => setSheet({ kind: 'categories', code: null })}
                >
                    Categories
                </Button>
                <Button onClick={() => setSheet({ kind: 'type', code: null })}>
                    Add Business Type
                </Button>
            </PageHeader>
            <Surface
                toolbar={
                    <>
                        <SearchField
                            compact
                            value={search}
                            onChange={setSearch}
                        />
                        {filter(
                            'Categories',
                            state.category,
                            categories.categories.map((item) => ({
                                value: item.code,
                                label: item.name,
                            })),
                            (category) => go({ category }),
                        )}
                        {filter(
                            'Industries',
                            state.industry,
                            categories.industries,
                            (industry) => go({ industry }),
                        )}
                        {filtered && (
                            <Button
                                variant="ghost"
                                size="sm"
                                onClick={() => {
                                    setSearch('');
                                    go({
                                        q: '',
                                        category: null,
                                        industry: null,
                                    });
                                }}
                            >
                                Clear Filters
                            </Button>
                        )}
                    </>
                }
                footer={
                    <Pagination
                        data={page}
                        onPage={(target) => go({ page: target })}
                    />
                }
            >
                <BusinessTypesTable
                    rows={page.rows}
                    categories={categories.categories}
                    year={categories.year}
                    filtered={filtered}
                    sort={state.sort}
                    dir={state.dir}
                    onSort={sortBy}
                    onEdit={(code) => setSheet({ kind: 'type', code })}
                />
            </Surface>
            <Sheet
                open={sheet !== null}
                onOpenChange={(open) => !open && setSheet(null)}
            >
                <SheetContent className="w-full overflow-y-auto sm:max-w-md">
                    <SheetHeader>
                        <SheetTitle>
                            {sheet?.kind === 'categories'
                                ? 'Categories'
                                : sheet?.kind === 'category'
                                  ? (editingCategory?.name ?? 'Add Category')
                                  : (editingType?.name ?? 'Add Business Type')}
                        </SheetTitle>
                    </SheetHeader>
                    {sheet?.kind === 'categories' && (
                        <div className="flex flex-col gap-3 px-4 pb-4">
                            <Button
                                className="self-end"
                                onClick={() =>
                                    setSheet({ kind: 'category', code: null })
                                }
                            >
                                Add Category
                            </Button>
                            <Surface>
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Category</TableHead>
                                            <TableHead className="text-right">
                                                Business Types
                                            </TableHead>
                                            <TableHead className="w-12">
                                                <span className="sr-only">
                                                    Actions
                                                </span>
                                            </TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {categories.categories.map((item) => (
                                            <TableRow key={item.code}>
                                                <TableCell>
                                                    <CategoryChip
                                                        name={item.name}
                                                        color={item.color}
                                                    />
                                                </TableCell>
                                                <TableCell className="text-right font-mono tabular-nums">
                                                    {formatCount(item.types)}
                                                </TableCell>
                                                <TableCell className="w-12 px-1 text-right">
                                                    <EditButton
                                                        label={`Edit ${item.name}`}
                                                        onClick={() =>
                                                            setSheet({
                                                                kind: 'category',
                                                                code: item.code,
                                                            })
                                                        }
                                                    />
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            </Surface>
                        </div>
                    )}
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
                            onSaved={() =>
                                setSheet({ kind: 'categories', code: null })
                            }
                        />
                    )}
                </SheetContent>
            </Sheet>
        </>
    );
}
