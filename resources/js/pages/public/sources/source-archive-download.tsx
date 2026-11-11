import { Download } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { Button } from '@/components/ui/button';
import { DialogDescription, DialogTitle } from '@/components/ui/dialog';
import { FocusDialog } from '@/components/ui/focus-dialog';
import { TaskProgress } from '@/components/ui/task-progress';
import { sourcesDownload } from '@/routes/public';

type Phase = 'preparing' | 'downloading' | 'ready' | 'failed';

const feedback: Record<Phase, [string, string]> = {
    preparing: [
        'Preparing Reports',
        'Checking the stored reports and building the ZIP.',
    ],
    downloading: ['Downloading Reports', 'Receiving the verified ZIP.'],
    ready: [
        'Download Started',
        'Look for cagayan-coa-reports.zip in your downloads.',
    ],
    failed: [
        'Download Unavailable',
        'The report ZIP could not be prepared. Try again in a moment.',
    ],
};

export function SourceArchiveDownload() {
    const [phase, setPhase] = useState<Phase | null>(null);
    const request = useRef<AbortController | null>(null);
    const trigger = useRef<HTMLButtonElement>(null);
    const running = phase === 'preparing' || phase === 'downloading';

    useEffect(() => () => request.current?.abort(), []);

    async function download() {
        if (request.current) return;
        const controller = new AbortController();
        request.current = controller;
        setPhase('preparing');

        try {
            const response = await fetch(sourcesDownload.url(), {
                signal: controller.signal,
            });
            if (
                !response.ok ||
                !response.headers
                    .get('content-type')
                    ?.startsWith('application/zip')
            ) {
                throw new Error('Report archive unavailable');
            }
            setPhase('downloading');
            const archive = await response.blob();
            if (controller.signal.aborted) return;
            const url = URL.createObjectURL(archive);
            const link = document.createElement('a');
            link.href = url;
            link.download = 'cagayan-coa-reports.zip';
            document.body.append(link);
            link.click();
            link.remove();
            window.setTimeout(() => URL.revokeObjectURL(url), 1000);
            setPhase('ready');
        } catch {
            if (!controller.signal.aborted) setPhase('failed');
        } finally {
            if (request.current === controller) request.current = null;
        }
    }

    function close() {
        request.current?.abort();
        request.current = null;
        setPhase(null);
    }

    return (
        <>
            <Button
                ref={trigger}
                variant="outline"
                size="sm"
                onClick={() => void download()}
            >
                <Download aria-hidden="true" />
                Download All Reports
            </Button>
            <FocusDialog
                open={phase !== null}
                onOpenChange={(open) => {
                    if (!open) close();
                }}
                onCloseAutoFocus={(event) => {
                    event.preventDefault();
                    trigger.current?.focus();
                }}
                className="px-gutter sm:max-w-md"
            >
                {phase && (
                    <div className="flex flex-col items-center gap-4 text-center">
                        <div aria-live="polite" className="flex flex-col gap-1">
                            <DialogTitle className="type-heading">
                                {feedback[phase][0]}
                            </DialogTitle>
                            <DialogDescription className="type-meta">
                                {feedback[phase][1]}
                            </DialogDescription>
                        </div>
                        {running && (
                            <TaskProgress aria-label={feedback[phase][0]} />
                        )}
                        <Button variant="outline" onClick={close}>
                            {running ? 'Cancel' : 'Close'}
                        </Button>
                    </div>
                )}
            </FocusDialog>
        </>
    );
}
