import { useForm } from '@inertiajs/react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Field, FieldError, FieldLabel } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Textarea } from '@/components/ui/textarea';
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group';
import { store } from '@/routes/capitol/finances/figures';
import type { FinanceReport } from '@/types/finance';

const OTHER_SOURCE = 'other';
const STATUS_LABELS: Record<string, string> = {
    pending: 'Pending',
    verified: 'Verified',
};

/** Pasted amounts often carry thousands separators; the server takes plain digits. */
const plain = (value: string) => value.replace(/[,\s₱]/g, '');
const cellKey = (line: string, year: number) => `${line} ${year}`;
const shown = new Intl.NumberFormat('en-PH', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
});

/**
 * The whole report at once, laid out like the Executive Summary: lines grouped by section, the report's fiscal years
 * as columns, each cell starting at the figure on record. Only changed cells are sent, as pending proposals.
 */
export function FinanceReportGrid({
    report,
    onSaved,
}: {
    report: FinanceReport;
    onSaved: () => void;
}) {
    // The Executive Summary is the report most entries copy from, so it comes first.
    const sources = report.documents
        .filter((document) => document.available)
        .sort(
            (a, b) =>
                Number(b.kind === 'executive_summary') -
                Number(a.kind === 'executive_summary'),
        );
    const current = new Map(
        report.cells
            .filter((cell) => cell.basis === 'actual')
            .map((cell) => [cellKey(cell.line, cell.fiscalYear), cell]),
    );
    const [values, setValues] = useState<Record<string, string>>({});
    const form = useForm({
        submission: crypto.randomUUID(),
        document_kind: sources[0]?.kind ?? OTHER_SOURCE,
        source_url: '',
        page: '',
        reason: '',
    });

    const changes = report.lines.flatMap((line) =>
        report.entryYears.flatMap((year) => {
            const key = cellKey(line.value, year);
            if (!(key in values)) return [];
            const base = current.get(key)?.value ?? null;
            const value = plain(values[key]);
            const same =
                value === '' ? base === null : Number(value) === Number(base);
            return same
                ? []
                : [{ key, line, year, value, base, cell: current.get(key) }];
        }),
    );
    const correctsVerified = changes.some(
        (change) => change.cell?.status === 'verified',
    );
    const errorFor = (key: string) => {
        const index = changes.findIndex((change) => change.key === key);
        return index < 0
            ? undefined
            : (form.errors as Record<string, string>)[`changes.${index}.value`];
    };

    const submit = (event: React.FormEvent) => {
        event.preventDefault();
        form.transform((data) => ({
            submission: data.submission,
            document_kind:
                data.document_kind === OTHER_SOURCE ? null : data.document_kind,
            source_url:
                data.document_kind === OTHER_SOURCE ? data.source_url : null,
            page: data.page === '' ? null : data.page,
            reason: data.reason === '' ? null : data.reason,
            changes: changes.map((change) => ({
                line_code: change.line.value,
                fiscal_year: change.year,
                basis: 'actual',
                value: change.value,
                base: change.base,
            })),
        }));
        form.post(store({ town: report.town.value, year: report.year }).url, {
            preserveScroll: true,
            onSuccess: () => {
                setValues({});
                form.reset();
                form.setData('submission', crypto.randomUUID());
                onSaved();
            },
        });
    };

    const groups = report.lines.reduce<
        { id: string; label: string; lines: FinanceReport['lines'] }[]
    >((all, line) => {
        const group = all.find((item) => item.id === line.group);
        if (group) group.lines.push(line);
        else
            all.push({ id: line.group, label: line.groupLabel, lines: [line] });
        return all;
    }, []);

    return (
        <form onSubmit={submit} className="flex flex-col gap-3 px-4 pb-6">
            <div className="grid gap-3 sm:grid-cols-[minmax(0,1fr)_6rem]">
                <Field className="gap-1">
                    <FieldLabel htmlFor="entry-source" required>
                        Source Report
                    </FieldLabel>
                    <ToggleGroup
                        id="entry-source"
                        type="single"
                        variant="outline"
                        size="sm"
                        className="flex-wrap justify-start"
                        value={form.data.document_kind}
                        onValueChange={(kind) => {
                            if (kind) form.setData('document_kind', kind);
                        }}
                    >
                        {sources.map((document) => (
                            <ToggleGroupItem
                                key={document.kind}
                                value={document.kind}
                                className="h-8 px-3"
                            >
                                {document.label}
                            </ToggleGroupItem>
                        ))}
                        <ToggleGroupItem
                            value={OTHER_SOURCE}
                            className="h-8 px-3"
                        >
                            COA Link
                        </ToggleGroupItem>
                    </ToggleGroup>
                    {form.errors.document_kind && (
                        <FieldError>{form.errors.document_kind}</FieldError>
                    )}
                </Field>
                <Field className="gap-1">
                    <FieldLabel htmlFor="entry-page">Page</FieldLabel>
                    <Input
                        id="entry-page"
                        inputMode="numeric"
                        className="font-mono tabular-nums"
                        value={form.data.page}
                        onChange={(event) =>
                            form.setData(
                                'page',
                                event.target.value.replace(/\D/g, ''),
                            )
                        }
                    />
                    {form.errors.page && (
                        <FieldError>{form.errors.page}</FieldError>
                    )}
                </Field>
            </div>
            {form.data.document_kind === OTHER_SOURCE && (
                <Field className="gap-1">
                    <FieldLabel htmlFor="entry-link" required>
                        COA Link
                    </FieldLabel>
                    <Input
                        id="entry-link"
                        type="url"
                        value={form.data.source_url}
                        onChange={(event) =>
                            form.setData('source_url', event.target.value)
                        }
                    />
                    {form.errors.source_url && (
                        <FieldError>{form.errors.source_url}</FieldError>
                    )}
                </Field>
            )}
            <Table className="type-data">
                <TableHeader>
                    <TableRow>
                        <TableHead>Item</TableHead>
                        {report.entryYears.map((year) => (
                            <TableHead
                                key={year}
                                className="text-right font-mono tabular-nums"
                            >
                                {year}
                            </TableHead>
                        ))}
                        <TableHead>Status</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    {groups.map((group) => [
                        <TableRow key={group.id} data-plain>
                            <TableHead
                                colSpan={report.entryYears.length + 2}
                                className="h-auto pt-2 pb-1 type-item"
                            >
                                {group.label}
                            </TableHead>
                        </TableRow>,
                        ...group.lines.map((line) => {
                            const latest = current.get(
                                cellKey(line.value, report.year),
                            );
                            return (
                                <TableRow key={line.value}>
                                    <TableHead
                                        scope="row"
                                        className="h-auto py-1 font-normal whitespace-normal"
                                    >
                                        {line.label}
                                    </TableHead>
                                    {report.entryYears.map((year) => {
                                        const key = cellKey(line.value, year);
                                        const changed = changes.some(
                                            (change) => change.key === key,
                                        );
                                        const error = errorFor(key);
                                        return (
                                            <TableCell
                                                key={year}
                                                className="p-1"
                                            >
                                                <Input
                                                    aria-label={`${line.label} ${year}`}
                                                    aria-invalid={
                                                        error ? true : undefined
                                                    }
                                                    title={error}
                                                    inputMode="decimal"
                                                    className={`h-8 min-w-36 text-right font-mono tabular-nums ${changed ? 'border-primary bg-accent' : ''}`}
                                                    value={
                                                        values[key] ??
                                                        (current.has(key)
                                                            ? shown.format(
                                                                  Number(
                                                                      current.get(
                                                                          key,
                                                                      )?.value,
                                                                  ),
                                                              )
                                                            : '')
                                                    }
                                                    onChange={(event) =>
                                                        setValues({
                                                            ...values,
                                                            [key]: event.target
                                                                .value,
                                                        })
                                                    }
                                                />
                                            </TableCell>
                                        );
                                    })}
                                    <TableCell className="type-meta">
                                        {latest
                                            ? STATUS_LABELS[latest.status]
                                            : null}
                                    </TableCell>
                                </TableRow>
                            );
                        }),
                    ])}
                </TableBody>
            </Table>
            {correctsVerified && (
                <Field className="gap-1">
                    <FieldLabel htmlFor="entry-reason" required>
                        Reason for Correction
                    </FieldLabel>
                    <Textarea
                        id="entry-reason"
                        rows={2}
                        maxLength={report.limits.noteMax}
                        value={form.data.reason}
                        onChange={(event) =>
                            form.setData('reason', event.target.value)
                        }
                    />
                </Field>
            )}
            {(['changes', 'reason'] as const).map(
                (key) =>
                    (form.errors as Record<string, string>)[key] && (
                        <FieldError key={key}>
                            {(form.errors as Record<string, string>)[key]}
                        </FieldError>
                    ),
            )}
            {Object.keys(form.errors).some((key) =>
                key.startsWith('changes.'),
            ) && (
                <FieldError>
                    Check the highlighted amounts: digits with up to two
                    decimals.
                </FieldError>
            )}
            <div className="flex justify-end">
                <Button
                    type="submit"
                    disabled={
                        form.processing ||
                        changes.length === 0 ||
                        changes.length > report.limits.changesMax
                    }
                >
                    {form.processing && <Spinner />}
                    Save for Review
                </Button>
            </div>
        </form>
    );
}
