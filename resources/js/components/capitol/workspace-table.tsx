import {
    WorkspaceFilterLayout,
    WorkspaceFilters,
    WorkspaceFilterSelect,
} from '@/components/capitol/workspace-filters';
import { router } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { Pagination } from '@/components/capitol/pagination';
import { Surface } from '@/components/ui/surface';
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
    afterTable,
}: {
    data: Data;
    kind: 'wishes' | 'logs';
    afterTable?: ReactNode;
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

    const table = (
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
                                        <TableCell>{row.town}</TableCell>
                                        <TableCell>{row.category}</TableCell>
                                        <TableCell className="text-right font-mono tabular-nums">
                                            {formatCount(Number(row.count))}
                                        </TableCell>
                                    </>
                                ) : (
                                    <>
                                        <TableCell>{row.event}</TableCell>
                                        <TableCell>{row.actor}</TableCell>
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
    );

    return (
        <WorkspaceFilterLayout
            results={
                <div className="grid min-w-0 gap-4">
                    {table}
                    {afterTable}
                </div>
            }
        >
            <WorkspaceFilters>
                {filters.map(({ key, label, options }) => (
                    <WorkspaceFilterSelect
                        key={key}
                        label={label}
                        value={data.filters[key] ?? 'all'}
                        options={options}
                        onChange={(value) => choose(key, value)}
                    />
                ))}
            </WorkspaceFilters>
        </WorkspaceFilterLayout>
    );
}
