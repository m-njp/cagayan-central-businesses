import type { ReactNode } from 'react';
import { NOT_AVAILABLE } from '@/lib/format';

// Labels over values in two columns from sm, ruled like a table: a line above, lines between cells, and lines that
// run to the card's edges (-mx-3 cancels ProfileSection's p-3, px-3 keeps text on the card's left edge). A lone last cell spans the row.
export function DetailGrid({ children }: { children: ReactNode }) {
    return (
        <dl className="-mx-3 grid gap-px border-t bg-border sm:grid-cols-2 sm:[&>*:last-child:nth-child(odd)]:col-span-2">
            {children}
        </dl>
    );
}

// One label over its value. A missing value reads "Not available", never blank or 0.
export function DetailField({
    label,
    children,
}: {
    label: string;
    children?: ReactNode;
}) {
    const empty =
        children === null ||
        children === undefined ||
        children === false ||
        children === '';

    return (
        <div className="flex min-w-0 flex-col gap-0.5 bg-card px-3 py-2">
            <dt className="type-meta">{label}</dt>
            <dd className="type-body break-words">
                {empty ? (
                    <span className="text-muted-foreground">
                        {NOT_AVAILABLE}
                    </span>
                ) : (
                    children
                )}
            </dd>
        </div>
    );
}
