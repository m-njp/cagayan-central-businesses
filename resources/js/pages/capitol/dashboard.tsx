import { Head } from '@inertiajs/react';
import { DashboardTables } from '@/components/capitol/dashboard-tables';
import { DashboardAtlas } from '@/components/map/dashboard-atlas';
import type { DataEntryReviewRow } from '@/types/data-entry';
import type { WorkspaceMap } from '@/types/workspace';

export default function Dashboard({
    map,
    waiting,
}: {
    map: WorkspaceMap;
    waiting: DataEntryReviewRow[] | null;
}) {
    return (
        <>
            <Head title="Dashboard" />
            <DashboardAtlas data={map} />
            <DashboardTables data={map} waiting={waiting} />
        </>
    );
}
