import { Head, router, useForm } from '@inertiajs/react';
import { useState } from 'react';
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
import { formatCount } from '@/lib/format';
import { show, store } from '@/routes/capitol/population';
import { store as storeReview } from '@/routes/capitol/population/reviews';
import type { PopulationPage } from '@/types/population';

const STATUS: Record<string, string> = {
    pending: 'Pending',
    verified: 'Verified',
};
const digits = (value: string) => value.replace(/\D/g, '');

/** One town's census counts for one year: type over any count, save once for review, and Admins verify or return. */
export default function Population({
    population,
}: {
    population: PopulationPage;
}) {
    const { town, year, rows } = population;
    const [values, setValues] = useState<Record<string, string>>({});
    const [selected, setSelected] = useState<string[]>([]);
    const form = useForm({
        submission: crypto.randomUUID(),
        // Most entries cite the same census release already on record for this year.
        source_url: rows.find((row) => row.source)?.source ?? '',
        reason: '',
    });
    const review = useForm({ note: '' });

    const changes = rows.flatMap((row) => {
        if (!(row.place in values)) return [];
        const value = digits(values[row.place]);
        if (value === '' || Number(value) === row.value) return [];
        return [{ row, value }];
    });
    const correctsVerified = changes.some(
        ({ row }) => row.status === 'verified',
    );
    const reviewable = rows.filter(
        (row) => row.pendingId !== null && !row.enteredByYou,
    );
    const open = (next: { town?: string; year?: number }) =>
        router.get(
            show({ town: next.town ?? town.value, year: next.year ?? year })
                .url,
        );

    const save = (event: React.FormEvent) => {
        event.preventDefault();
        form.transform((data) => ({
            submission: data.submission,
            source_url: data.source_url,
            reason: data.reason === '' ? null : data.reason,
            changes: changes.map(({ row, value }) => ({
                place: row.place,
                value,
                base: row.value === null ? null : String(row.value),
            })),
        }));
        form.post(store({ town: town.value, year }).url, {
            preserveScroll: true,
            onSuccess: () => {
                setValues({});
                form.reset();
                form.setData('submission', crypto.randomUUID());
            },
        });
    };
    const decide = (decision: 'verified' | 'rejected') => {
        review.transform((data) => ({
            decision,
            figures: selected,
            note: decision === 'rejected' ? data.note : null,
        }));
        review.post(storeReview({ town: town.value, year }).url, {
            preserveScroll: true,
            onSuccess: () => {
                setSelected([]);
                review.reset();
            },
        });
    };
    const errors = form.errors as Record<string, string>;

    return (
        <>
            <Head title={`${town.label} Population`} />
            <header className="flex flex-wrap items-end justify-between gap-3">
                <h1 className="type-heading">Population</h1>
                <div className="flex flex-wrap items-end gap-2">
                    <Field className="w-56 gap-1">
                        <FieldLabel htmlFor="population-town">Town</FieldLabel>
                        <Select
                            value={town.value}
                            onValueChange={(value) => open({ town: value })}
                        >
                            <SelectTrigger id="population-town">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                {population.towns.map((item) => (
                                    <SelectItem
                                        key={item.value}
                                        value={item.value}
                                    >
                                        {item.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </Field>
                    <Field className="gap-1">
                        <FieldLabel id="population-year">
                            Census Year
                        </FieldLabel>
                        <ToggleGroup
                            type="single"
                            variant="outline"
                            size="sm"
                            aria-labelledby="population-year"
                            value={String(year)}
                            onValueChange={(value) => {
                                if (value) open({ year: Number(value) });
                            }}
                        >
                            {population.years.map((item) => (
                                <ToggleGroupItem
                                    key={item}
                                    value={String(item)}
                                    className="h-9 px-3 font-mono tabular-nums"
                                >
                                    {item}
                                </ToggleGroupItem>
                            ))}
                        </ToggleGroup>
                    </Field>
                </div>
            </header>
            <form onSubmit={save} className="flex min-w-0 flex-col gap-3">
                <Field className="gap-1">
                    <FieldLabel htmlFor="population-source" required>
                        PSA Source Link
                    </FieldLabel>
                    <Input
                        id="population-source"
                        type="url"
                        value={form.data.source_url}
                        onChange={(event) =>
                            form.setData('source_url', event.target.value)
                        }
                    />
                    {errors.source_url && (
                        <FieldError>{errors.source_url}</FieldError>
                    )}
                </Field>
                <Table className="type-data sm:type-body">
                    <TableHeader>
                        <TableRow>
                            {population.canReview && reviewable.length > 0 && (
                                <TableHead className="w-0 px-2">
                                    <span className="sr-only">Select</span>
                                </TableHead>
                            )}
                            <TableHead>Place</TableHead>
                            <TableHead className="text-right">
                                Population {year}
                            </TableHead>
                            <TableHead>Status</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {rows.map((row) => {
                            const changed = changes.some(
                                (change) => change.row.place === row.place,
                            );
                            const canPick =
                                row.pendingId !== null && !row.enteredByYou;
                            return (
                                <TableRow key={row.place}>
                                    {population.canReview &&
                                        reviewable.length > 0 && (
                                            <TableCell className="w-0 px-2">
                                                {canPick && (
                                                    <input
                                                        type="checkbox"
                                                        className="size-4 accent-primary"
                                                        aria-label={`Select ${row.name}`}
                                                        checked={selected.includes(
                                                            row.pendingId!,
                                                        )}
                                                        onChange={(event) =>
                                                            setSelected(
                                                                event.target
                                                                    .checked
                                                                    ? [
                                                                          ...selected,
                                                                          row.pendingId!,
                                                                      ]
                                                                    : selected.filter(
                                                                          (
                                                                              id,
                                                                          ) =>
                                                                              id !==
                                                                              row.pendingId,
                                                                      ),
                                                            )
                                                        }
                                                    />
                                                )}
                                            </TableCell>
                                        )}
                                    <TableHead
                                        scope="row"
                                        className={`h-auto font-normal whitespace-normal ${row.place === 'town' ? 'type-item' : ''}`}
                                    >
                                        {row.name}
                                    </TableHead>
                                    <TableCell className="p-1">
                                        <Input
                                            aria-label={`${row.name} population ${year}`}
                                            inputMode="numeric"
                                            className={`h-8 min-w-32 text-right font-mono tabular-nums ${changed ? 'border-primary bg-accent' : ''}`}
                                            value={
                                                values[row.place] ??
                                                (row.value === null
                                                    ? ''
                                                    : formatCount(row.value))
                                            }
                                            onChange={(event) =>
                                                setValues({
                                                    ...values,
                                                    [row.place]:
                                                        event.target.value,
                                                })
                                            }
                                        />
                                    </TableCell>
                                    <TableCell className="type-meta">
                                        {row.status ? STATUS[row.status] : null}
                                        {row.enteredByYou && ' by you'}
                                    </TableCell>
                                </TableRow>
                            );
                        })}
                    </TableBody>
                </Table>
                {correctsVerified && (
                    <Field className="gap-1">
                        <FieldLabel htmlFor="population-reason" required>
                            Reason for Correction
                        </FieldLabel>
                        <Textarea
                            id="population-reason"
                            rows={2}
                            maxLength={500}
                            value={form.data.reason}
                            onChange={(event) =>
                                form.setData('reason', event.target.value)
                            }
                        />
                    </Field>
                )}
                {['changes', 'reason'].map(
                    (key) =>
                        errors[key] && (
                            <FieldError key={key}>{errors[key]}</FieldError>
                        ),
                )}
                {Object.keys(errors).some((key) =>
                    key.startsWith('changes.'),
                ) && (
                    <FieldError>
                        Check the highlighted counts: whole numbers only.
                    </FieldError>
                )}
                <div className="flex flex-wrap items-center justify-end gap-2">
                    <Button
                        type="submit"
                        disabled={
                            form.processing ||
                            changes.length === 0 ||
                            changes.length > population.limits.changesMax
                        }
                    >
                        {form.processing && <Spinner />}
                        Save for Review
                    </Button>
                </div>
            </form>
            {population.canReview && reviewable.length > 0 && (
                <section
                    aria-labelledby="population-review"
                    className="flex flex-col gap-2 rounded-sm border p-panel"
                >
                    <h2 id="population-review" className="type-item">
                        Review
                    </h2>
                    <Field className="gap-1">
                        <FieldLabel htmlFor="population-note">
                            Reason for Returning
                        </FieldLabel>
                        <Textarea
                            id="population-note"
                            rows={2}
                            maxLength={500}
                            value={review.data.note}
                            onChange={(event) =>
                                review.setData('note', event.target.value)
                            }
                        />
                        {(review.errors as Record<string, string>).note && (
                            <FieldError>
                                {(review.errors as Record<string, string>).note}
                            </FieldError>
                        )}
                    </Field>
                    <div className="flex flex-wrap justify-end gap-2">
                        <Button
                            variant="outline"
                            disabled={
                                review.processing || selected.length === 0
                            }
                            onClick={() => decide('rejected')}
                        >
                            Return Selected
                        </Button>
                        <Button
                            disabled={
                                review.processing || selected.length === 0
                            }
                            onClick={() => decide('verified')}
                        >
                            Verify Selected
                        </Button>
                    </div>
                </section>
            )}
        </>
    );
}
