import { Head } from '@inertiajs/react';
import { PageHeader } from '@/components/capitol/page-header';
import { WorkspaceTable } from '@/components/capitol/workspace-table';
import type { WorkspaceTable as Data } from '@/types/workspace';

export default function ActivityLogs({ table }: { table: Data }) {
    return (
        <>
            <Head title="Activity Logs" />
            <PageHeader title="Activity Logs" />
            <WorkspaceTable data={table} kind="logs" />
        </>
    );
}
