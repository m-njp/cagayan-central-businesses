import { Link } from '@inertiajs/react';
import { ArrowDown, ArrowUp, ArrowUpDown } from 'lucide-react';
import { CategoryChip } from '@/components/capitol/category-chip';
import { EditButton } from '@/components/capitol/edit-button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import type { CategoryView } from '@/lib/category-table';
import { NOT_AVAILABLE, formatCount } from '@/lib/format';
import { index as businesses } from '@/routes/capitol/businesses';
import type { BusinessCategoryRow, BusinessTypeRow } from '@/types/workspace';

const count = (value: number | null) =>
    value === null ? NOT_AVAILABLE : formatCount(value);

function SortHead({
    label,
    field,
    align,
    sort,
    dir,
    onSort,
}: {
    label: string;
    field: string;
    align?: 'right';
    sort: CategoryView['sort'];
    dir: CategoryView['dir'];
    onSort: (field: string) => void;
}) {
    const direction = sort === field ? dir : null;
    const Icon =
        direction === 'asc'
            ? ArrowUp
            : direction === 'desc'
              ? ArrowDown
              : ArrowUpDown;
    return (
        <TableHead
            aria-sort={
                direction === 'asc'
                    ? 'ascending'
                    : direction === 'desc'
                      ? 'descending'
                      : 'none'
            }
            className={align ? 'text-right' : ''}
        >
            <button
                type="button"
                onClick={() => onSort(field)}
                className="inline-flex items-center gap-1 underline-offset-2 hover:underline"
            >
                {label}
                <Icon className="size-3.5" aria-hidden="true" />
            </button>
        </TableHead>
    );
}

export function BusinessTypesTable({
    rows,
    categories,
    year,
    filtered,
    sort,
    dir,
    onSort,
    onEdit,
}: {
    rows: BusinessTypeRow[];
    categories: BusinessCategoryRow[];
    year: number | null;
    filtered: boolean;
    sort: CategoryView['sort'];
    dir: CategoryView['dir'];
    onSort: (field: string) => void;
    onEdit: (code: string) => void;
}) {
    const byCode = Object.fromEntries(
        categories.map((item) => [item.code, item]),
    );
    const columns = [
        { field: 'name', label: 'Business Type' },
        { field: 'category', label: 'Category' },
        { field: 'industry', label: 'Industry' },
        {
            field: 'registered',
            label: year === null ? 'Registered' : `Registered (${year})`,
        },
    ];

    return (
        <Table>
            <TableHeader>
                <TableRow>
                    {columns.map(({ field, label }) => (
                        <SortHead
                            key={field}
                            field={field}
                            label={label}
                            align={field === 'registered' ? 'right' : undefined}
                            sort={sort}
                            dir={dir}
                            onSort={onSort}
                        />
                    ))}
                    <TableHead className="w-12">
                        <span className="sr-only">Actions</span>
                    </TableHead>
                </TableRow>
            </TableHeader>
            <TableBody>
                {rows.length === 0 && (
                    <TableRow>
                        <TableCell
                            colSpan={5}
                            className="py-8 text-center text-muted-foreground"
                        >
                            {filtered
                                ? 'No Business Types match.'
                                : 'No Business Types yet.'}
                        </TableCell>
                    </TableRow>
                )}
                {rows.map((row) => (
                    <TableRow key={row.code}>
                        <TableCell className="whitespace-normal">
                            {row.name}
                        </TableCell>
                        <TableCell>
                            {byCode[row.category] && (
                                <CategoryChip
                                    name={byCode[row.category].name}
                                    color={byCode[row.category].color}
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
                                            query: { type: row.code },
                                        }).url
                                    }
                                    className="underline underline-offset-2"
                                >
                                    {count(row.registered)}
                                </Link>
                            )}
                        </TableCell>
                        <TableCell className="w-12 px-1 text-right">
                            <EditButton
                                label={`Edit ${row.name}`}
                                onClick={() => onEdit(row.code)}
                            />
                        </TableCell>
                    </TableRow>
                ))}
            </TableBody>
        </Table>
    );
}
