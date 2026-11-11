import { useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { FocusDialog } from '@/components/ui/focus-dialog';
import { Spinner } from '@/components/ui/spinner';
import { TaskProgress } from '@/components/ui/task-progress';
import { useFinanceUploadTask } from '@/hooks/use-finance-upload-task';
import { icons } from '@/lib/icons';
import { formatCount } from '@/lib/format';
import { formatExactPesos } from '@/lib/performance-format';
import { formatClock } from '@/lib/workspace-format';
import { retry } from '@/routes/capitol/finances/uploads';
import type { FinanceReport, FinanceUploadTask } from '@/types/finance';
import { FinanceUploadForm } from './finance-upload';

type Progress = FinanceUploadTask['progress'];

const LINES = 7;
// Strokes the check in; the dash length covers the whole path.
const DRAW =
    '[&_path]:[stroke-dasharray:24] [&_path]:animate-draw motion-reduce:[&_path]:animate-none';

function LogLine({
    event,
    rows,
    active,
    className,
}: {
    event: Progress['activity'][number];
    rows: Progress['rows'];
    active: boolean;
    className?: string;
}) {
    const row = event.line
        ? rows.find(
              (item) =>
                  item.line === event.line && item.location === event.location,
          )
        : undefined;
    const Icon = event.kind === 'issue' ? icons.warning : icons.parsed;
    return (
        <li
            className={`grid h-6 grid-cols-[4.25rem_minmax(0,1fr)_auto_1rem] items-center gap-x-2 motion-safe:animate-rise sm:grid-cols-[4.25rem_3rem_minmax(0,1fr)_auto_1rem] ${className ?? ''}`}
        >
            <time dateTime={event.at} className="text-muted-foreground">
                {formatClock(new Date(event.at))}
            </time>
            <span className="hidden truncate text-muted-foreground sm:block">
                {event.location?.replace(/^page /, 'p.').replace(/^.*!/, '')}
            </span>
            <span className="truncate">
                {row?.label ??
                    (event.kind === 'figure'
                        ? event.line
                        : event.message.replace(/\.$/, ''))}
            </span>
            <span className="text-right">
                {row && formatExactPesos(row.value)}
            </span>
            {active ? (
                <Spinner className="size-3.5 motion-reduce:animate-none" />
            ) : (
                <Icon
                    aria-hidden="true"
                    className={`size-3.5 ${event.kind === 'issue' ? 'text-destructive' : `text-primary ${DRAW}`}`}
                />
            )}
        </li>
    );
}

export function FinanceParsingTask({
    report,
    id,
    open,
    onOpenChange,
    onUploaded,
}: {
    report: FinanceReport;
    id: string | null;
    open: boolean;
    onOpenChange: (open: boolean) => void;
    onUploaded: (id: string) => void;
}) {
    const { task, error, reconnect } = useFinanceUploadTask(
        report.town.value,
        report.year,
        id,
        open,
    );
    const retryForm = useForm({});
    const progress = task?.progress;
    const finished = task?.status === 'done';
    const failed = task?.status === 'failed';
    const running = !finished && !failed;
    const issues =
        progress?.issues.filter((issue) => issue.severity !== 'info').length ??
        0;
    const events = progress?.activity.slice(-LINES) ?? [];
    const scanning = events.at(-1)?.kind === 'scan' && progress?.total != null;
    const Outcome = failed ? icons.close : icons.parsed;

    const title = failed
        ? 'Parsing Stopped'
        : finished
          ? issues
              ? 'Completed With Issues'
              : progress?.found === 0
                ? 'No Eligible Figures'
                : 'Report Ready'
          : 'Parsing Report';
    const detail = failed
        ? task?.message
        : finished
          ? progress?.found == null
              ? 'Detailed results are not available for this earlier upload.'
              : `${task?.message ?? `${formatCount(task?.saved ?? 0)} waiting for review.`}${issues ? ` ${formatCount(issues)} to check.` : ''}`
          : (task?.name ?? `${report.town.label} ${report.year}`);
    // Both stamps come from the server clock, so the browser's clock cannot skew it.
    const seconds = progress?.startedAt
        ? Math.round(
              (Date.parse(progress.finishedAt ?? progress.updatedAt) -
                  Date.parse(progress.startedAt)) /
                  1000,
          )
        : 0;
    const built = [
        ['Figures', formatCount(progress?.found ?? 0)],
        [
            'Elapsed',
            `${Math.floor(seconds / 60)}:${String(seconds % 60).padStart(2, '0')}`,
        ],
    ];

    if (!id) {
        return (
            <Dialog open={open} onOpenChange={onOpenChange}>
                <DialogContent className="max-h-[90dvh] overflow-y-auto">
                    <DialogHeader className="items-center gap-2 text-center sm:text-center">
                        <DialogTitle className="type-heading">
                            Upload Report
                        </DialogTitle>
                        <DialogDescription className="max-w-full type-meta break-words">
                            {report.town.label} {report.year}
                        </DialogDescription>
                    </DialogHeader>
                    <FinanceUploadForm report={report} onSaved={onUploaded} />
                </DialogContent>
            </Dialog>
        );
    }

    return (
        <FocusDialog
            open={open}
            onOpenChange={onOpenChange}
            className="sm:max-w-md"
        >
            <div className="flex flex-col items-center gap-4 text-center font-mono tabular-nums">
                <div aria-live="polite" className="flex flex-col gap-1">
                    <DialogTitle className="type-heading">{title}</DialogTitle>
                    <DialogDescription className="type-meta break-words">
                        {detail}
                    </DialogDescription>
                </div>
                {running ? (
                    <TaskProgress
                        aria-label="Source Scan"
                        value={scanning ? (progress?.current ?? 0) : undefined}
                        max={scanning ? (progress?.total ?? 1) : undefined}
                    />
                ) : (
                    <Outcome
                        aria-hidden="true"
                        className={`size-8 ${failed ? 'text-destructive' : `text-primary ${DRAW}`}`}
                    />
                )}
                <ol
                    aria-label="Parsing Activity"
                    className="flex h-42 w-full flex-col justify-end overflow-hidden text-left type-meta"
                >
                    {events.map((event, index) => (
                        <LogLine
                            key={event.sequence}
                            event={event}
                            rows={progress?.rows ?? []}
                            active={running && index === events.length - 1}
                            className={
                                events.length === LINES
                                    ? ['opacity-40', 'opacity-70'][index]
                                    : undefined
                            }
                        />
                    ))}
                </ol>
                {progress && progress.found !== null && (
                    <dl className="grid w-full grid-cols-2 border-t pt-2 type-meta">
                        {built.map(([label, value]) => (
                            <div key={label}>
                                <dt className="text-muted-foreground">
                                    {label}
                                </dt>
                                <dd className="type-heading">{value}</dd>
                            </div>
                        ))}
                    </dl>
                )}
                {error && (
                    <p role="alert" className="type-meta text-destructive">
                        {error}
                    </p>
                )}
                <div className="flex gap-2">
                    {error && (
                        <Button variant="outline" onClick={reconnect}>
                            Reconnect
                        </Button>
                    )}
                    {(failed || finished) && (
                        <Button
                            variant="outline"
                            disabled={retryForm.processing}
                            onClick={() =>
                                retryForm.post(
                                    retry({
                                        town: report.town.value,
                                        year: report.year,
                                        upload: id,
                                    }).url,
                                    {
                                        preserveScroll: true,
                                        onSuccess: reconnect,
                                    },
                                )
                            }
                        >
                            {retryForm.processing && (
                                <Spinner className="motion-reduce:animate-none" />
                            )}
                            {failed ? 'Retry Parsing' : 'Parse Again'}
                        </Button>
                    )}
                    <Button
                        variant={finished ? 'default' : 'outline'}
                        onClick={() => onOpenChange(false)}
                    >
                        {finished ? 'View Figures' : 'Continue in Background'}
                    </Button>
                </div>
            </div>
        </FocusDialog>
    );
}
