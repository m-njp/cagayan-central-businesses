import { EntryCell } from '@/components/capitol/entry-cell';
import { FigureStatus } from '@/components/registry-badges';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { formatCount, NOT_AVAILABLE } from '@/lib/format';
import type { PopulationRow } from '@/types/population';

const STICKY = 'sticky top-0 z-20 h-8 border-b bg-card';

export function PopulationTable({
    rows,
    year,
    loading,
    focused,
    onFocus,
    canReview,
    selected,
    onSelect,
    entryFor,
    onUnlock,
    onValue,
}: {
    rows: PopulationRow[];
    year: number;
    loading: boolean;
    focused: string | null;
    onFocus: (place: string) => void;
    canReview: boolean;
    selected: string[];
    onSelect: (ids: string[]) => void;
    entryFor: (row: PopulationRow) => {
        locked: boolean;
        value: string;
        changed: boolean;
        error?: string;
    };
    onUnlock: (place: string) => void;
    onValue: (place: string, value: string) => void;
}) {
    const reviewable = new Map<string, string>();
    if (canReview) {
        for (const row of rows) {
            if (row.pendingId !== null && !row.enteredByYou) {
                reviewable.set(row.place, row.pendingId);
            }
        }
    }
    const picking = reviewable.size > 0;

    return (
        <div className="isolate max-h-[calc(100dvh-14rem)] overflow-auto [&_[data-slot=table-container]]:overflow-visible">
            <Table
                className={`text-sm transition-opacity ${loading ? 'opacity-60' : ''}`}
            >
                <TableHeader>
                    <TableRow>
                        {picking && (
                            <TableHead className={`${STICKY} w-0 px-2`}>
                                <input
                                    type="checkbox"
                                    className="size-4 accent-primary"
                                    aria-label="Select all pending counts"
                                    checked={
                                        selected.length === reviewable.size
                                    }
                                    onChange={(event) =>
                                        onSelect(
                                            event.target.checked
                                                ? [...reviewable.values()]
                                                : [],
                                        )
                                    }
                                />
                            </TableHead>
                        )}
                        <TableHead className={STICKY}>Place</TableHead>
                        <TableHead className={`${STICKY} text-right`}>
                            Population {year}
                        </TableHead>
                        <TableHead className={STICKY}>Status</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    {rows.length === 0 && (
                        <TableRow>
                            <TableCell
                                colSpan={4}
                                className="py-6 text-center text-muted-foreground"
                            >
                                No places are listed for this municipality.
                            </TableCell>
                        </TableRow>
                    )}
                    {rows.map((row) => {
                        const { place, name } = row;
                        const pick = reviewable.get(place);
                        const status =
                            row.returnedNote !== null ? 'rejected' : row.status;
                        const active = focused === place;
                        return (
                            <TableRow
                                key={place}
                                id={`place-${place}`}
                                className={active ? 'bg-accent' : undefined}
                            >
                                {picking && (
                                    <TableCell className="w-0 px-2">
                                        {pick && (
                                            <input
                                                type="checkbox"
                                                className="size-4 accent-primary"
                                                aria-label={`Select ${name}`}
                                                checked={selected.includes(
                                                    pick,
                                                )}
                                                onChange={(event) =>
                                                    onSelect(
                                                        event.target.checked
                                                            ? [
                                                                  ...selected,
                                                                  pick,
                                                              ]
                                                            : selected.filter(
                                                                  (id) =>
                                                                      id !==
                                                                      pick,
                                                              ),
                                                    )
                                                }
                                            />
                                        )}
                                    </TableCell>
                                )}
                                <TableCell
                                    className={`whitespace-normal ${place === 'town' ? 'font-medium' : ''}`}
                                >
                                    {place === 'town' ? (
                                        name
                                    ) : (
                                        <button
                                            type="button"
                                            aria-pressed={active}
                                            className="text-left"
                                            onClick={() => onFocus(place)}
                                        >
                                            {name}
                                        </button>
                                    )}
                                </TableCell>
                                <TableCell className="p-0.5">
                                    <EntryCell
                                        {...entryFor(row)}
                                        display={
                                            row.value === null
                                                ? NOT_AVAILABLE
                                                : formatCount(row.value)
                                        }
                                        label={`${name} population ${year}`}
                                        onUnlock={() => onUnlock(place)}
                                        inputMode="numeric"
                                        onChange={(value) =>
                                            onValue(place, value)
                                        }
                                    />
                                </TableCell>
                                <TableCell className="py-0 whitespace-normal">
                                    {status && (
                                        <FigureStatus
                                            status={status}
                                            enteredByYou={row.enteredByYou}
                                            note={row.returnedNote}
                                        />
                                    )}
                                </TableCell>
                            </TableRow>
                        );
                    })}
                </TableBody>
            </Table>
        </div>
    );
}
