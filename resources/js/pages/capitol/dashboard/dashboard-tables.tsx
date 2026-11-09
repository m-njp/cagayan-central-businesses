import { Link, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { Pagination } from '@/components/capitol/pagination';
import { ReviewQueueTable } from '@/components/capitol/review-queue-table';
import { Figure } from '@/components/charts/finance-panel';
import { FinanceYearsTable } from '@/components/finance-years-table';
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
    financeMapObservation,
    financeMapSelection,
    MAP_MEASURE,
} from '@/lib/finance-map';
import { NOT_AVAILABLE } from '@/lib/format';
import { pageSearchParams } from '@/lib/navigation';
import { navigateDashboard } from '@/lib/workspace-map-navigation';
import { dataEntry } from '@/routes/capitol';
import type { DataEntryReviewRow } from '@/types/data-entry';
import type { WorkspaceMap } from '@/types/workspace';

const RANK_PAGE = 8;
const number = 'text-right font-mono tabular-nums';

/** The tables under the dashboard map, all read from what the map already loaded. */
export function DashboardTables({
    data,
    waiting,
}: {
    data: WorkspaceMap;
    waiting: DataEntryReviewRow[] | null;
}) {
    const { url } = usePage();
    const params = pageSearchParams(url);
    const { finances } = data;
    const { year } = financeMapSelection(finances, params);
    const townCode = params.get('town');
    const town = data.atlas.towns.find((item) => item.code === townCode);
    const area = town ?? data.atlas.province;
    const [rankPage, setRankPage] = useState(1);

    const income = finances.metrics.find((metric) => metric.id === MAP_MEASURE);
    const ranked = data.atlas.towns
        .map((item) => {
            const point = financeMapObservation(
                finances,
                item.code,
                year,
                MAP_MEASURE,
            );
            return {
                item,
                point,
                value: point?.state === 'shown' ? Number(point.value) : null,
            };
        })
        .sort(
            (a, b) =>
                (b.value ?? -Infinity) - (a.value ?? -Infinity) ||
                a.item.name.localeCompare(b.item.name),
        );
    const top = Math.max(0, ...ranked.map((row) => row.value ?? 0));
    const lastPage = Math.max(1, Math.ceil(ranked.length / RANK_PAGE));
    const current = Math.min(rankPage, lastPage);
    const visible = ranked.slice(
        (current - 1) * RANK_PAGE,
        current * RANK_PAGE,
    );

    return (
        <>
            <div className="grid min-w-0 items-start gap-4 lg:grid-cols-2">
                <Surface title="Government Finances">
                    <FinanceYearsTable
                        finances={finances}
                        code={area.code}
                        name={area.name}
                    />
                </Surface>
                <Surface
                    title="Government Income by Municipality"
                    footer={
                        <Pagination
                            data={{ page: current, lastPage }}
                            onPage={setRankPage}
                        />
                    }
                >
                    {income && ranked.some((row) => row.value !== null) ? (
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead className="w-10">#</TableHead>
                                    <TableHead>Municipality</TableHead>
                                    <TableHead className={number}>
                                        {year ?? ''}
                                    </TableHead>
                                    <TableHead className="w-1/4">
                                        <span className="sr-only">Share</span>
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {visible.map(
                                    ({ item, point, value }, index) => (
                                        <TableRow
                                            key={item.code}
                                            data-state={
                                                item.code === townCode
                                                    ? 'selected'
                                                    : undefined
                                            }
                                        >
                                            <TableCell className="font-mono tabular-nums">
                                                {value === null
                                                    ? ''
                                                    : (current - 1) *
                                                          RANK_PAGE +
                                                      index +
                                                      1}
                                            </TableCell>
                                            <TableCell className="whitespace-normal">
                                                <button
                                                    type="button"
                                                    aria-pressed={
                                                        item.code === townCode
                                                    }
                                                    className="text-left underline-offset-2 hover:underline"
                                                    onClick={() =>
                                                        void navigateDashboard(
                                                            item.code,
                                                        ).catch(() => undefined)
                                                    }
                                                >
                                                    {item.name}
                                                </button>
                                            </TableCell>
                                            <TableCell className={number}>
                                                <Figure
                                                    point={point}
                                                    metric={income}
                                                    alwaysShort
                                                    withChange
                                                />
                                            </TableCell>
                                            <TableCell className="w-1/4">
                                                {value !== null && top > 0 && (
                                                    <div
                                                        className="h-2 rounded-sm bg-map-3"
                                                        style={{
                                                            width: `${Math.max(2, (value / top) * 100)}%`,
                                                        }}
                                                        aria-hidden="true"
                                                    />
                                                )}
                                            </TableCell>
                                        </TableRow>
                                    ),
                                )}
                            </TableBody>
                        </Table>
                    ) : (
                        <p role="status" className="p-panel type-body">
                            {NOT_AVAILABLE}
                        </p>
                    )}
                </Surface>
            </div>
            {waiting && (
                <ReviewQueueTable
                    rows={waiting}
                    footer={
                        <Link
                            href={dataEntry()}
                            className="type-meta underline underline-offset-2"
                        >
                            Open Data Entry
                        </Link>
                    }
                />
            )}
        </>
    );
}
