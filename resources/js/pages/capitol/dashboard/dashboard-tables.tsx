import { Link, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { Pagination } from '@/components/capitol/pagination';
import { ReviewQueueTable } from '@/components/capitol/review-queue-table';
import { Figure } from '@/components/charts/finance-panel';
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
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import {
    financeMapSelection,
    MAP_MEASURE,
    municipalityIncomeRanking,
} from '@/lib/finance-map';
import { NOT_AVAILABLE } from '@/lib/format';
import { pageSearchParams } from '@/lib/navigation';
import { paginate } from '@/lib/pagination';
import { trendOf } from '@/lib/trend';
import { navigateDashboard } from '@/lib/workspace-map-navigation';
import { dataEntry } from '@/routes/capitol';
import type { DataEntryReviewRow } from '@/types/data-entry';
import type { WorkspaceMap } from '@/types/workspace';

const number = 'text-right font-mono tabular-nums';

function Rank({
    rank,
    change,
}: {
    rank: number;
    change: number | 'new' | null;
}) {
    const trend = trendOf(change);
    const places = Math.abs(Number(change));
    let detail = 'No prior fiscal-year rank.';
    if (change === 'new')
        detail = 'First verified appearance in the available history.';
    else if (change === 0) detail = 'Same rank as last fiscal year.';
    else if (trend)
        detail = `${trend.direction > 0 ? 'Up' : 'Down'} ${places} ${places === 1 ? 'place' : 'places'} from last fiscal year.`;
    return (
        <Tooltip>
            <TooltipTrigger asChild>
                <span
                    tabIndex={0}
                    className="inline-flex cursor-help items-baseline gap-1"
                >
                    <span className="font-semibold">{rank}</span>
                    {trend && (
                        <span
                            className={`type-data ${trend.tone}`}
                            aria-hidden="true"
                        >
                            {trend.glyph}
                            {places || null}
                        </span>
                    )}
                    {change === 'new' && (
                        <span className="font-sans type-data text-muted-foreground">
                            New
                        </span>
                    )}
                </span>
            </TooltipTrigger>
            <TooltipContent>{detail}</TooltipContent>
        </Tooltip>
    );
}

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
    const [rankPage, setRankPage] = useState(1);
    const [reviewPage, setReviewPage] = useState(1);

    const income = finances.metrics.find((metric) => metric.id === MAP_MEASURE);
    const ranked = municipalityIncomeRanking(finances, data.atlas.towns, year);
    const top = Math.max(0, ...ranked.map((row) => row.value ?? 0));
    const ranking = paginate(ranked, rankPage, 8);
    const review = paginate(waiting ?? [], reviewPage, 10);

    return (
        <div
            className={
                waiting
                    ? 'grid min-w-0 items-start gap-4 xl:grid-cols-2'
                    : 'min-w-0'
            }
        >
            <Surface
                title="Government Income by Municipality"
                footer={
                    <Pagination
                        data={ranking}
                        onPage={setRankPage}
                        label="Municipality Ranking Pagination"
                    />
                }
            >
                {income && ranked.some((row) => row.value !== null) ? (
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead className="w-10">Rank</TableHead>
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
                            {ranking.rows.map(
                                ({ town, point, value, rank, change }) => (
                                    <TableRow
                                        key={town.code}
                                        data-state={
                                            town.code === townCode
                                                ? 'selected'
                                                : undefined
                                        }
                                    >
                                        <TableCell className="font-mono tabular-nums">
                                            {rank !== null && (
                                                <Rank
                                                    rank={rank}
                                                    change={change}
                                                />
                                            )}
                                        </TableCell>
                                        <TableCell className="whitespace-normal">
                                            <button
                                                type="button"
                                                aria-pressed={
                                                    town.code === townCode
                                                }
                                                className="text-left underline-offset-2 hover:underline"
                                                onClick={() =>
                                                    void navigateDashboard(
                                                        town.code,
                                                    ).catch(() => undefined)
                                                }
                                            >
                                                {town.name}
                                            </button>
                                        </TableCell>
                                        <TableCell className={number}>
                                            <Figure
                                                point={point}
                                                metric={income}
                                                alwaysShort
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
            {waiting && (
                <ReviewQueueTable
                    rows={review.rows}
                    footer={
                        <div className="flex flex-wrap items-center justify-between gap-2">
                            <Link
                                href={dataEntry()}
                                className="type-meta underline underline-offset-2"
                            >
                                Open Data Entry
                            </Link>
                            <Pagination
                                data={review}
                                onPage={setReviewPage}
                                label="Waiting for Review Pagination"
                            />
                        </div>
                    }
                />
            )}
        </div>
    );
}
