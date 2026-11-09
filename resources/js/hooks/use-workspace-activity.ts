import { router, useHttp } from '@inertiajs/react';
import { useEffect, useRef } from 'react';
import { activity } from '@/routes/capitol/session';
import type { Workspace } from '@/types/workspace';

export function useWorkspaceActivity(workspace: Workspace): Workspace {
    const request = useHttp<Record<string, never>, Workspace>({});
    const current =
        request.response && request.response.serverNow > workspace.serverNow
            ? request.response
            : workspace;
    const lastAttempt = useRef(Date.now());

    useEffect(() => {
        const recordActivity = (event: Event) => {
            if (
                !event.isTrusted ||
                document.visibilityState !== 'visible' ||
                request.processing
            )
                return;
            const now = Date.now();
            if (
                now - lastAttempt.current <
                current.activityIntervalSeconds * 1000
            )
                return;
            lastAttempt.current = now;
            // Only direct user input renews idle time; never poll or renew on clock ticks.
            void request.post(activity().url).catch(() => {
                router.reload({
                    headers: { 'X-Workspace-Background': 'true' },
                    only: ['workspace'],
                });
            });
        };
        const events = ['pointerdown', 'keydown', 'wheel', 'touchmove'];
        events.forEach((name) =>
            window.addEventListener(name, recordActivity, { passive: true }),
        );
        return () =>
            events.forEach((name) =>
                window.removeEventListener(name, recordActivity),
            );
    }, [current.activityIntervalSeconds, request]);

    return current;
}
