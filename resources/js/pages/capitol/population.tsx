import { Head, router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import { EntryCell } from '@/components/capitol/entry-cell';
import { PageHeader } from '@/components/capitol/page-header';
import { TabRow } from '@/components/capitol/tab-row';
import { PopulationEntryFields } from '@/components/capitol/population-entry-fields';
import { PopulationMap } from '@/components/capitol/population-map';
import { FigureStatus } from '@/components/registry-badges';
import { Button } from '@/components/ui/button';
import { Field, FieldError, FieldLabel } from '@/components/ui/field';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Surface } from '@/components/ui/surface';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Textarea } from '@/components/ui/textarea';
import { formatCount, NOT_AVAILABLE } from '@/lib/format';
import { show, store } from '@/routes/capitol/population';
import { store as storeReview } from '@/routes/capitol/population/reviews';
import type { PopulationPage } from '@/types/population';

const digits = (value: string) => value.replace(/\D/g, '');
// The header stays in view while the barangays scroll beneath it.
const STICKY = 'sticky top-0 z-10 h-8 border-b bg-card';

/** One municipality's census counts for one year: type over any count, save once for review, and Admins verify or return. */
export default function Population({
    population,
}: {
    population: PopulationPage;
}) {
    const { town, year, rows } = population;
    const [values, setValues] = useState<Record<string, string>>({});
    const [selected, setSelected] = useState<string[]>([]);
    const [returning, setReturning] = useState(false);
    const [loading, setLoading] = useState(false);
    // The barangay chosen on the map or in the table; each one finds the other.
    const [focused, setFocused] = useState<string | null>(null);
    // Verified counts stay read-only until someone opens that one place for correction.
    const [unlocked, setUnlocked] = useState<string[]>([]);
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
    const reviewable = rows.flatMap((row) =>
        row.pendingId !== null && !row.enteredByYou ? [row.pendingId] : [],
    );
    const picking = population.canReview && reviewable.length > 0;
    const open = (next: { town?: string; year?: number }) =>
        router.get(
            show({ town: next.town ?? town.value, year: next.year ?? year })
                .url,
            {},
            {
                onStart: () => setLoading(true),
                onFinish: () => setLoading(false),
            },
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
                setUnlocked([]);
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
                setReturning(false);
                review.reset();
            },
        });
    };
    const focus = (place: string) => {
        const next = focused === place ? null : place;
        setFocused(next);
        if (next) {
            document
                .getElementById(`place-${next}`)
                ?.scrollIntoView({ block: 'nearest' });
        }
    };
    const errors = form.errors as Record<string, string>;
    const reviewNote = (review.errors as Record<string, string>).note;
    const toggle = (id: string, on: boolean) =>
        setSelected(on ? [...selected, id] : selected.filter((x) => x !== id));
    const errorFor = (place: string) => {
        const index = changes.findIndex(({ row }) => row.place === place);
        return index < 0 ? undefined : errors[`changes.${index}.value`];
    };

    return (
        <>
            <Head title={`${town.label} Population`} />
            <PageHeader title="Population" />
            <Surface padded>
                <div className="flex flex-wrap items-center gap-x-6 gap-y-2">
                    <Field orientation="horizontal" className="w-auto gap-2">
                        <FieldLabel htmlFor="population-municipality">
                            Municipality
                        </FieldLabel>
                        <Select
                            value={town.value}
                            onValueChange={(value) => open({ town: value })}
                        >
                            <SelectTrigger
                                id="population-municipality"
                                className="h-8 w-52"
                            >
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
                    <TabRow
                        label="Census Year"
                        value={String(year)}
                        tabs={population.years.map((item) => ({
                            value: String(item),
                            label: String(item),
                            mono: true,
                        }))}
                        onChange={(value) => value && open({ year: +value })}
                    />
                </div>
            </Surface>
            <div className="grid items-start gap-4 lg:grid-cols-[minmax(0,1fr)_24rem]">
                <form onSubmit={save} className="flex min-w-0 flex-col gap-4">
                    <Surface
                        aria-busy={loading}
                        toolbar={
                            selected.length > 0 && (
                                <>
                                    <span className="type-meta">
                                        {formatCount(selected.length)} Selected
                                    </span>
                                    <Button
                                        type="button"
                                        size="sm"
                                        variant="outline"
                                        disabled={review.processing}
                                        onClick={() => setReturning(true)}
                                    >
                                        Return
                                    </Button>
                                    <Button
                                        type="button"
                                        size="sm"
                                        disabled={review.processing}
                                        onClick={() => decide('verified')}
                                    >
                                        Verify
                                    </Button>
                                </>
                            )
                        }
                        footer={
                            <div className="flex flex-col gap-2">
                                {returning && selected.length > 0 && (
                                    <Field className="gap-1">
                                        <FieldLabel htmlFor="population-note">
                                            Reason for Returning
                                        </FieldLabel>
                                        <Textarea
                                            id="population-note"
                                            rows={2}
                                            maxLength={
                                                population.limits.noteMax
                                            }
                                            value={review.data.note}
                                            onChange={(event) =>
                                                review.setData(
                                                    'note',
                                                    event.target.value,
                                                )
                                            }
                                        />
                                        {reviewNote && (
                                            <FieldError>
                                                {reviewNote}
                                            </FieldError>
                                        )}
                                        <div className="flex justify-end gap-2">
                                            <Button
                                                type="button"
                                                variant="outline"
                                                onClick={() =>
                                                    setReturning(false)
                                                }
                                            >
                                                Cancel
                                            </Button>
                                            <Button
                                                type="button"
                                                disabled={review.processing}
                                                onClick={() =>
                                                    decide('rejected')
                                                }
                                            >
                                                Return Selected
                                            </Button>
                                        </div>
                                    </Field>
                                )}
                                <PopulationEntryFields
                                    data={form.data}
                                    errors={errors}
                                    limits={population.limits}
                                    correctsVerified={correctsVerified}
                                    processing={form.processing}
                                    changeCount={changes.length}
                                    onChange={form.setData}
                                />
                            </div>
                        }
                    >
                        <div className="max-h-[calc(100dvh-14rem)] overflow-y-auto [&_[data-slot=table-container]]:overflow-visible">
                            <Table
                                className={`text-sm transition-opacity ${loading ? 'opacity-60' : ''}`}
                            >
                                <TableHeader>
                                    <TableRow>
                                        {picking && (
                                            <TableHead
                                                className={`${STICKY} w-0 px-2`}
                                            >
                                                <input
                                                    type="checkbox"
                                                    className="size-4 accent-primary"
                                                    aria-label="Select all pending counts"
                                                    checked={
                                                        selected.length ===
                                                        reviewable.length
                                                    }
                                                    onChange={(event) =>
                                                        setSelected(
                                                            event.target.checked
                                                                ? reviewable
                                                                : [],
                                                        )
                                                    }
                                                />
                                            </TableHead>
                                        )}
                                        <TableHead className={STICKY}>
                                            Place
                                        </TableHead>
                                        <TableHead
                                            className={`${STICKY} text-right`}
                                        >
                                            Population {year}
                                        </TableHead>
                                        <TableHead className={STICKY}>
                                            Status
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {rows.length === 0 && (
                                        <TableRow>
                                            <TableCell
                                                colSpan={4}
                                                className="py-6 text-center text-muted-foreground"
                                            >
                                                No places are listed for this
                                                municipality.
                                            </TableCell>
                                        </TableRow>
                                    )}
                                    {rows.map((row) => {
                                        const pick =
                                            row.pendingId !== null &&
                                            !row.enteredByYou
                                                ? row.pendingId
                                                : null;
                                        return (
                                            <TableRow
                                                key={row.place}
                                                id={`place-${row.place}`}
                                                className={
                                                    focused === row.place
                                                        ? 'bg-accent'
                                                        : undefined
                                                }
                                            >
                                                {picking && (
                                                    <TableCell className="w-0 px-2">
                                                        {pick && (
                                                            <input
                                                                type="checkbox"
                                                                className="size-4 accent-primary"
                                                                aria-label={`Select ${row.name}`}
                                                                checked={selected.includes(
                                                                    pick,
                                                                )}
                                                                onChange={(
                                                                    event,
                                                                ) =>
                                                                    toggle(
                                                                        pick,
                                                                        event
                                                                            .target
                                                                            .checked,
                                                                    )
                                                                }
                                                            />
                                                        )}
                                                    </TableCell>
                                                )}
                                                <TableCell
                                                    className={`whitespace-normal ${row.place === 'town' ? 'font-medium' : ''}`}
                                                >
                                                    {row.place === 'town' ? (
                                                        row.name
                                                    ) : (
                                                        <button
                                                            type="button"
                                                            aria-pressed={
                                                                focused ===
                                                                row.place
                                                            }
                                                            className="text-left"
                                                            onClick={() =>
                                                                focus(row.place)
                                                            }
                                                        >
                                                            {row.name}
                                                        </button>
                                                    )}
                                                </TableCell>
                                                <TableCell className="p-0.5">
                                                    <EntryCell
                                                        locked={
                                                            row.status ===
                                                                'verified' &&
                                                            !unlocked.includes(
                                                                row.place,
                                                            )
                                                        }
                                                        display={
                                                            row.value === null
                                                                ? NOT_AVAILABLE
                                                                : formatCount(
                                                                      row.value,
                                                                  )
                                                        }
                                                        label={`${row.name} population ${year}`}
                                                        onUnlock={() =>
                                                            setUnlocked([
                                                                ...unlocked,
                                                                row.place,
                                                            ])
                                                        }
                                                        inputMode="numeric"
                                                        changed={changes.some(
                                                            (change) =>
                                                                change.row
                                                                    .place ===
                                                                row.place,
                                                        )}
                                                        error={errorFor(
                                                            row.place,
                                                        )}
                                                        value={
                                                            values[row.place] ??
                                                            (row.value === null
                                                                ? ''
                                                                : formatCount(
                                                                      row.value,
                                                                  ))
                                                        }
                                                        onChange={(value) =>
                                                            setValues({
                                                                ...values,
                                                                [row.place]:
                                                                    value,
                                                            })
                                                        }
                                                    />
                                                </TableCell>
                                                <TableCell className="py-0 whitespace-normal">
                                                    {(row.returnedNote !==
                                                        null ||
                                                        row.status) && (
                                                        <FigureStatus
                                                            status={
                                                                row.returnedNote !==
                                                                null
                                                                    ? 'rejected'
                                                                    : row.status!
                                                            }
                                                            enteredByYou={
                                                                row.enteredByYou
                                                            }
                                                            note={
                                                                row.returnedNote
                                                            }
                                                        />
                                                    )}
                                                </TableCell>
                                            </TableRow>
                                        );
                                    })}
                                </TableBody>
                            </Table>
                        </div>
                    </Surface>
                </form>
                <PopulationMap
                    code={town.value}
                    name={town.label}
                    year={year}
                    rows={rows}
                    focused={focused}
                    onFocus={focus}
                />
            </div>
        </>
    );
}
