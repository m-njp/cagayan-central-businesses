import { router, useForm, usePoll } from '@inertiajs/react';
import { useEffect } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Field, FieldError, FieldLabel } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import { store } from '@/routes/capitol/finances/uploads';
import type { FinanceReport } from '@/types/finance';

const ACCEPT: Record<string, string> = {
    executive_summary: '.pdf',
    fs_workbook: '.xlsx',
    annex_workbook: '.xlsx',
    archive: '.zip',
};

/** Choose the report and its file; the parser reads it on the queue and the figures arrive as pending. */
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
            <Field className="gap-1">
                <FieldLabel htmlFor="upload-kind">Report</FieldLabel>
                <Select
                    value={form.data.kind}
                    onValueChange={(kind) => form.setData('kind', kind)}
                >
                    <SelectTrigger id="upload-kind" className="w-full">
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        {report.uploadKinds.map((kind) => (
                            <SelectItem key={kind.value} value={kind.value}>
                                {kind.label}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
                {form.errors.kind && (
                    <FieldError>{form.errors.kind}</FieldError>
                )}
            </Field>
            {form.progress && (
                <div role="status" className="font-mono type-meta tabular-nums">
                    Uploading {form.progress.percentage}%
                </div>
            )}
            <Field className="gap-1">
                <FieldLabel htmlFor="upload-file">File</FieldLabel>
                <Input
                    id="upload-file"
                    type="file"
                    accept={ACCEPT[form.data.kind]}
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
        <ul aria-label="Uploaded Reports" className="flex flex-col gap-2">
            {report.uploads.map((upload) => (
                <li key={upload.id} className="flex flex-col gap-1 type-meta">
                    <button
                        type="button"
                        onClick={() => onOpen(upload.id)}
                        className="truncate text-left underline underline-offset-4"
                        title={upload.name}
                    >
                        {upload.name}
                    </button>
                    <span className="flex flex-wrap items-center gap-2">
                        <Badge
                            variant={
                                upload.status === 'failed'
                                    ? 'destructive'
                                    : 'outline'
                            }
                        >
                            {upload.status === 'queued' ||
                            upload.status === 'running' ? (
                                <Spinner />
                            ) : null}
                            {upload.statusLabel}
                        </Badge>
                        {upload.status === 'done' && upload.figures > 0 && (
                            <span>
                                {upload.figures}{' '}
                                {upload.figures === 1 ? 'figure' : 'figures'} to
                                verify
                            </span>
                        )}
                    </span>
                    {upload.message && <span>{upload.message}</span>}
                </li>
            ))}
        </ul>
    );
}
