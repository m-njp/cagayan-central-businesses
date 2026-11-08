import { router } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import { show } from '@/routes/capitol/finances/uploads';
import type { FinanceUploadTask } from '@/types/finance';

export function useFinanceUploadTask(
    town: string,
    year: number,
    id: string | null,
    open: boolean,
) {
    const [task, setTask] = useState<FinanceUploadTask | null>(null);
    const [error, setError] = useState<string | null>(null);
    const [connection, setConnection] = useState(0);
    const refreshed = useRef(new Set<string>());

    useEffect(() => {
        if (!id || !open) return;
        const controller = new AbortController();
        let timer: ReturnType<typeof setTimeout>;
        const poll = async () => {
            try {
                const response = await fetch(
                    show({ town, year, upload: id }).url,
                    {
                        credentials: 'same-origin',
                        headers: {
                            Accept: 'application/json',
                            'X-Workspace-Background': 'true',
                        },
                        signal: controller.signal,
                    },
                );
                if (!response.ok) throw new Error('status_unavailable');
                const next: FinanceUploadTask = await response.json();
                if (controller.signal.aborted) return;
                setTask(next);
                setError(null);
                if (next.status === 'done' || next.status === 'failed') {
                    const key = `${id}:${next.progress.attempt}`;
                    if (!refreshed.current.has(key)) {
                        refreshed.current.add(key);
                        router.reload({
                            only: ['report'],
                            headers: { 'X-Workspace-Background': 'true' },
                        });
                    }
                    return;
                }
                timer = setTimeout(() => void poll(), next.pollMs);
            } catch {
                if (!controller.signal.aborted)
                    setError(
                        'Live updates are disconnected. Reconnect to check parsing status.',
                    );
            }
        };
        void poll();
        return () => {
            controller.abort();
            clearTimeout(timer);
        };
    }, [town, year, id, open, connection]);

    return {
        task: task?.id === id ? task : null,
        error,
        reconnect: () => setConnection((value) => value + 1),
    };
}
