import { Head, Link, router, usePage } from '@inertiajs/react';
import { useMemo, useRef } from 'react';
import { PageHeader } from '@/components/capitol/page-header';
import { Pagination } from '@/components/capitol/pagination';
import { ReviewQueueTable } from '@/components/capitol/review-queue-table';
import { RowChevron, RowLink } from '@/components/row-link';
import { Button } from '@/components/ui/button';
import { Surface } from '@/components/ui/surface';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { formatCount, formatEntryTimestamp, NOT_AVAILABLE } from '@/lib/format';
import { icons } from '@/lib/icons';
import { pageSearchParams } from '@/lib/navigation';
import { dataEntry } from '@/routes/capitol';
import type { DataEntry } from '@/types/data-entry';
import { DataEntryWorkflow } from './data-entry-workflow';
import { entryWorkflows } from './workflow-definitions';

const number = 'text-right font-mono tabular-nums';

/** Where every dataset is entered, and for Admins one list of everything waiting to be verified. */
export default function DataEntryPage({ entry }: { entry: DataEntry }) {
    const { url } = usePage();
    const params = pageSearchParams(url);
    const canReview = entry.review !== null;
    const canManageCategories = entry.datasets.some(
        ({ id }) => id === 'categories',
    );
    const canAddBusiness = entry.datasets.some(
        ({ id, actionHref }) => id === 'businesses' && actionHref !== null,
    );
    const workflows = useMemo(
        () =>
            entryWorkflows({ canReview, canManageCategories, canAddBusiness }),
        [canReview, canManageCategories, canAddBusiness],
    );
    const options = [
        { id: 'overview', label: 'Start Here' },
        ...entry.datasets.filter(({ id }) => Object.hasOwn(workflows, id)),
    ];
    const requested = params.get('workflow');
    const active = options.find(({ id }) => id === requested)?.id ?? 'overview';
    const guideOpen = params.get('guide') === '1';
    const guideTrigger = useRef<HTMLButtonElement>(null);
    const workflow = active === 'overview' ? undefined : active;
    const pagination = entry.pagination;
    const queueUrl = (page: number) =>
        dataEntry({ query: { page, workflow } }).url;
    const guideUrl = (chapter: string, open: boolean) =>
        dataEntry({
            query: {
                page: params.get('page') ?? undefined,
                workflow: chapter === 'overview' ? undefined : chapter,
                guide: open ? 1 : undefined,
            },
        }).url;
    const setGuideOpen = (open: boolean) => {
        router.replace({
            url: guideUrl(active, open),
            preserveScroll: true,
            preserveState: true,
        });
    };
    const changeWorkflow = (value: string) => {
        if (value === active) return;
        router.push({
            url: guideUrl(value, true),
            preserveScroll: true,
            preserveState: true,
        });
    };

    return (
        <>
            <Head title="Data Entry" />
            <PageHeader title="Data Entry">
                <Button
                    ref={guideTrigger}
                    variant="outline"
                    onClick={() => setGuideOpen(true)}
                >
                    <icons.help aria-hidden="true" />
                    How Data Entry Works
                </Button>
            </PageHeader>
            <div className="grid min-w-0 gap-4">
                {entry.review && (
                    <ReviewQueueTable
                        rows={entry.review}
                        total={pagination?.total}
                        footer={
                            pagination &&
                            pagination.total > 0 && (
                                <Pagination
                                    data={{
                                        ...pagination,
                                        previous: pagination.previous
                                            ? queueUrl(pagination.page - 1)
                                            : null,
                                        next: pagination.next
                                            ? queueUrl(pagination.page + 1)
                                            : null,
                                    }}
                                    label="Review Queue Pagination"
                                    showSinglePage
                                />
                            )
                        }
                    />
                )}
                <Surface title="Datasets">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Dataset</TableHead>
                                <TableHead>Shows On</TableHead>
                                <TableHead className="text-right">
                                    Pending
                                </TableHead>
                                <TableHead className="text-right">
                                    Last Change
                                </TableHead>
                                <TableHead className="w-0">
                                    <span className="sr-only">Action</span>
                                </TableHead>
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
                                        {dataset.lastChange
                                            ? formatEntryTimestamp(
                                                  dataset.lastChange,
                                              )
                                            : NOT_AVAILABLE}
                                    </TableCell>
                                    <TableCell className="py-1">
                                        {dataset.actionHref ? (
                                            <Button
                                                asChild
                                                size="sm"
                                                variant="outline"
                                                className="relative z-10"
                                            >
                                                <Link href={dataset.actionHref}>
                                                    Add Business
                                                </Link>
                                            </Button>
                                        ) : (
                                            <RowChevron />
                                        )}
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </Surface>
            </div>
            <DataEntryWorkflow
                open={guideOpen}
                onOpenChange={setGuideOpen}
                onCloseAutoFocus={(event) => {
                    event.preventDefault();
                    guideTrigger.current?.focus();
                }}
                value={active}
                options={options}
                workflow={workflows[active as keyof typeof workflows]}
                onChange={changeWorkflow}
            />
        </>
    );
}
