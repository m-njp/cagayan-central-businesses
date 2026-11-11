import { Head } from '@inertiajs/react';
import { lazy, Suspense } from 'react';
import { PageHeader } from '@/components/capitol/page-header';
import { WorkspaceTable } from '@/components/capitol/workspace-table';
import { Skeleton } from '@/components/ui/skeleton';
import { Surface } from '@/components/ui/surface';
import type { CommunityWishSnapshot } from '@/types/economic-performance';
import type { WorkspaceTable as Data } from '@/types/workspace';

const WishCloud = lazy(() => import('@/components/charts/wish-cloud'));

export default function CommunityWishes({
    table,
    summary,
}: {
    table: Data;
    summary: Pick<CommunityWishSnapshot, 'items' | 'townName'>;
}) {
    return (
        <>
            <Head title="Community Wishes" />
            <PageHeader title="Community Wishes" />
            <WorkspaceTable
                data={table}
                kind="wishes"
                afterTable={
                    <Surface title="Most Requested Business Types" padded>
                        {summary.items.length ? (
                            <Suspense
                                fallback={
                                    <Skeleton
                                        className="h-72"
                                        aria-label="Loading word cloud"
                                    />
                                }
                            >
                                <div className="mx-auto w-full max-w-xl">
                                    <WishCloud
                                        key={`${table.filters.town}:${table.filters.category}`}
                                        snapshot={summary}
                                    />
                                </div>
                            </Suspense>
                        ) : (
                            <p role="status" className="type-meta">
                                No business type wishes match these filters.
                            </p>
                        )}
                    </Surface>
                }
            />
        </>
    );
}
