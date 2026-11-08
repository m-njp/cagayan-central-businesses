import { Head } from '@inertiajs/react';
import { PageHeader } from '@/components/capitol/page-header';
import { WorkspaceTable } from '@/components/capitol/workspace-table';
import type { WorkspaceTable as Data } from '@/types/workspace';

export default function CommunityWishes({ table }: { table: Data }) {
    return (
        <>
            <Head title="Community Wishes" />
            <PageHeader title="Community Wishes" />
            <WorkspaceTable data={table} kind="wishes" />
        </>
    );
}
