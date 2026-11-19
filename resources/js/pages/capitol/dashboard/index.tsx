import { Head, usePage } from '@inertiajs/react';
import { useWorkspaceClock } from '@/hooks/use-workspace-clock';
import {
    formatClock,
    formatWorkspaceDate,
    greeting,
} from '@/lib/workspace-format';
import { DashboardTables } from './dashboard-tables';
import { DashboardAtlas } from './dashboard-atlas';
import type { DataEntryReviewRow } from '@/types/data-entry';
import type { Workspace, WorkspaceMap } from '@/types/workspace';

function DashboardWelcome() {
    const { displayName, serverNow } = usePage<{ workspace: Workspace }>().props
        .workspace;
    const now = useWorkspaceClock(serverNow);

    return (
        <header className="flex flex-wrap items-center justify-between gap-x-4 gap-y-1 rounded-sm bg-brand px-4 py-3 text-brand-foreground">
            <h1 className="min-w-0 type-heading wrap-anywhere">
                {greeting(now)}, {displayName}
            </h1>
            <time
                className="flex flex-wrap gap-x-3 type-body"
                dateTime={now.toISOString()}
            >
                <span>{formatWorkspaceDate(now)}</span>
                <span className="font-mono tabular-nums">
                    {formatClock(now)}
                </span>
            </time>
        </header>
    );
}

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
            <DashboardWelcome />
            <DashboardAtlas data={map} />
            <DashboardTables data={map} waiting={waiting} />
        </>
    );
}
