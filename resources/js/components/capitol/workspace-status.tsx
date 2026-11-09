import { router } from '@inertiajs/react';
import { useEffect, useRef } from 'react';
import { Button } from '@/components/ui/button';
import { useWorkspaceClock } from '@/hooks/use-workspace-clock';
import { useWorkspaceActivity } from '@/hooks/use-workspace-activity';
import type { Workspace } from '@/types/workspace';

export function WorkspaceStatus({
    workspace: initialWorkspace,
}: {
    workspace: Workspace;
}) {
    const workspace = useWorkspaceActivity(initialWorkspace);
    const now = useWorkspaceClock(workspace.serverNow);
    const expiry = Math.min(
        workspace.idleExpiresAt,
        workspace.absoluteExpiresAt,
    );
    const remaining = expiry - Math.floor(now.getTime() / 1000);
    const ending = remaining <= workspace.warningSeconds;
    const expired = useRef(false);
    useEffect(() => {
        expired.current = false;
    }, [workspace.idleExpiresAt, workspace.absoluteExpiresAt]);
    useEffect(() => {
        if (remaining <= 0 && !expired.current) {
            expired.current = true;
            router.reload({ headers: { 'X-Workspace-Background': 'true' } });
        }
    }, [remaining]);

    return (
        <>
            {ending && (
                <div
                    role="status"
                    className="flex flex-wrap items-center justify-between gap-3 rounded-md border bg-muted p-3 type-body"
                >
                    <p>
                        {remaining <= 0
                            ? 'Checking your session.'
                            : 'Your session will end shortly. Save any changes before continuing.'}
                    </p>
                    {remaining > 0 && expiry === workspace.idleExpiresAt && (
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={() => router.reload()}
                        >
                            Stay Signed In
                        </Button>
                    )}
                </div>
            )}
        </>
    );
}
