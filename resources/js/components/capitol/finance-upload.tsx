import { router, useForm, usePoll } from '@inertiajs/react';
import { useEffect } from 'react';
import { Button } from '@/components/ui/button';
import { Disclosure } from '@/components/ui/disclosure';
import { Field, FieldError, FieldLabel } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import { formatDate } from '@/lib/format';
import { store } from '@/routes/capitol/finances/uploads';
import type { FinanceReport } from '@/types/finance';

/** Choose the Executive Summary PDF; the parser reads it on the queue and the figures arrive as pending. */
export function FinanceUploadForm({
    report,
    onSaved,
}: {
    report: FinanceReport;
    onSaved: (id: string) => void;
}) {
    const form = useForm<{ kind: string; file: File | null }>({
        kind: report.uploadKinds[0]?.value ?? '',
        file: null,
    });
    const submit = (event: React.FormEvent) => {
        event.preventDefault();
        form.post(store({ town: report.town.value, year: report.year }).url, {
            forceFormData: true,
            onSuccess: (page) => {
                const id = page.flash.financeUpload;
                if (typeof id === 'string') onSaved(id);
            },
        });
    };
    return (
        <form onSubmit={submit} className="flex flex-col gap-4">
            {form.progress && (
                <div role="status" className="font-mono type-meta tabular-nums">
                    Uploading {form.progress.percentage}%
                </div>
            )}
            <Field className="gap-1">
                <FieldLabel htmlFor="upload-file">
                    Executive Summary PDF
                </FieldLabel>
                <Input
                    id="upload-file"
                    type="file"
                    accept=".pdf"
                    onChange={(event) =>
                        form.setData('file', event.target.files?.[0] ?? null)
                    }
                />
                {form.errors.file && (
                    <FieldError>{form.errors.file}</FieldError>
                )}
            </Field>
            <Button
                type="submit"
                disabled={form.processing || form.data.file === null}
            >
                {form.processing && <Spinner />}
                Upload
            </Button>
        </form>
    );
}

/** The latest uploads for this report, refreshed in the background only while one is still being read. */
export function FinanceUploads({
    report,
    onOpen,
    watching = false,
}: {
    report: FinanceReport;
    onOpen: (id: string) => void;
    watching?: boolean;
}) {
    const reading = report.uploads.some(
        (upload) => upload.status === 'queued' || upload.status === 'running',
    );
    const { start, stop } = usePoll(
        3000,
        {
            only: ['uploadStatuses'],
            headers: { 'X-Workspace-Background': 'true' },
            onSuccess: (page) => {
                const statuses = page.props.uploadStatuses;
                if (
                    Array.isArray(statuses) &&
                    statuses.some((status) => {
                        const previous = report.uploads.find(
                            (upload) => upload.id === status.id,
                        );
                        return previous && previous.status !== status.status;
                    })
                )
                    router.reload({
                        only: ['report'],
                        headers: { 'X-Workspace-Background': 'true' },
                    });
            },
        },
        { autoStart: false },
    );
    useEffect(() => {
        if (reading && !watching) start();
        else stop();
        return stop;
    }, [reading, watching, start, stop]);

    if (report.uploads.length === 0) return null;
    return (
        <Disclosure
            className="border-t"
            title={
                <span className="flex min-w-0 flex-1 flex-wrap items-center justify-between gap-x-2">
                    Recent Uploads
                    {reading && <span className="type-meta">In Progress</span>}
                </span>
            }
        >
            <ul aria-label="Uploaded Reports" className="divide-y px-2">
                {report.uploads.map((upload) => (
                    <li key={upload.id}>
                        <button
                            type="button"
                            onClick={() => onOpen(upload.id)}
                            className="flex min-h-touch w-full flex-wrap items-baseline justify-between gap-x-2 gap-y-0.5 rounded-sm py-2 text-left focus-visible:outline-2 focus-visible:outline-ring md:min-h-control-compact"
                            title={upload.name}
                        >
                            <span className="line-clamp-2 min-w-0 type-body wrap-anywhere underline underline-offset-2">
                                {upload.name}
                            </span>
                            <span className="flex basis-full flex-wrap items-baseline justify-between gap-x-2 gap-y-0.5 type-meta">
                                <span
                                    className={
                                        upload.status === 'failed'
                                            ? 'text-destructive'
                                            : undefined
                                    }
                                >
                                    {upload.status === 'done'
                                        ? upload.figures > 0
                                            ? `${upload.figures} to Verify`
                                            : 'Processed'
                                        : upload.statusLabel}
                                </span>
                                <time
                                    dateTime={upload.uploadedAt.replace(
                                        ' ',
                                        'T',
                                    )}
                                    className="font-mono tabular-nums"
                                >
                                    {formatDate(upload.uploadedAt)}{' '}
                                    {upload.uploadedAt.slice(11)}
                                </time>
                            </span>
                        </button>
                    </li>
                ))}
            </ul>
        </Disclosure>
    );
}
