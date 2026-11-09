import { WorkspaceFilters } from '@/components/capitol/workspace-filters';
import { router } from '@inertiajs/react';
import { Pagination } from '@/components/capitol/pagination';
import { Surface } from '@/components/ui/surface';
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
import { formatCount, formatDateTime } from '@/lib/format';
import { index as wishes } from '@/routes/capitol/wishes';
import { index as logs } from '@/routes/capitol/logs';
import type { WorkspaceTable as Data } from '@/types/workspace';

export function WorkspaceTable({
    data,
    kind,
}: {
    data: Data;
    kind: 'wishes' | 'logs';
}) {
    const choose = (field: string, value: string) => {
        const filters = {
            ...data.filters,
            [field]: value === 'all' ? undefined : value,
        };
        router.get(
            (kind === 'wishes' ? wishes : logs)({ query: filters }).url,
            {},
            { preserveState: true, preserveScroll: true },
        );
    };
    const columns =
        kind === 'wishes'
            ? ['Municipality', 'Business Type', 'Wishes']
            : ['Event', 'Account', 'Date and Time'];
    const filters =
        kind === 'wishes'
            ? [
                  { key: 'town', label: 'Municipality', options: data.towns },
                  {
                      key: 'category',
                      label: 'Business Type',
                      options: data.categories,
                  },
              ]
            : [{ key: 'event', label: 'Event', options: data.categories }];

    return (
        <section className="grid min-w-0 items-start gap-4 lg:grid-cols-[minmax(0,1fr)_16rem]">
            <div className="min-w-0 space-y-4">
                <Surface footer={<Pagination data={data.pagination} />}>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                {columns.map((column, i) => (
                                    <TableHead
                                        key={column}
                                        className={
                                            kind === 'wishes' && i === 2
                                                ? 'text-right'
                                                : ''
                                        }
                                    >
                                        {column}
                                    </TableHead>
                                ))}
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {data.rows.length ? (
                                data.rows.map((row, i) => (
                                    <TableRow key={String(row.id ?? i)}>
                                        {kind === 'wishes' ? (
                                            <>
                                                <TableCell>
                                                    {row.town}
                                                </TableCell>
                                                <TableCell>
                                                    {row.category}
                                                </TableCell>
                                                <TableCell className="text-right font-mono tabular-nums">
                                                    {formatCount(
                                                        Number(row.count),
                                                    )}
                                                </TableCell>
                                            </>
                                        ) : (
                                            <>
                                                <TableCell>
                                                    {row.event}
                                                </TableCell>
                                                <TableCell>
                                                    {row.actor}
                                                </TableCell>
                                                <TableCell className="font-mono tabular-nums">
                                                    {formatDateTime(
                                                        String(row.occurredAt),
                                                    )}
                                                </TableCell>
                                            </>
                                        )}
                                    </TableRow>
                                ))
                            ) : (
                                <TableRow>
                                    <TableCell
                                        colSpan={3}
                                        className="py-8 text-center text-muted-foreground"
                                    >
                                        {kind === 'wishes'
                                            ? 'No community wishes match these filters.'
                                            : 'No activity events match these filters.'}
                                    </TableCell>
                                </TableRow>
                            )}
                        </TableBody>
                    </Table>
                </Surface>
            </div>
            <WorkspaceFilters>
                {filters.map(({ key, label, options }) => (
                    <div key={key} className="flex w-full flex-col gap-2">
                        <label id={key + '-label'} className="type-label">
                            {label}
                        </label>
                        <Select
                            value={data.filters[key] ?? 'all'}
                            onValueChange={(value) => choose(key, value)}
                        >
                            <SelectTrigger
                                variant="quiet"
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
            </WorkspaceFilters>
        </section>
    );
}
