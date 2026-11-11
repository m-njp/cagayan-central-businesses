import type { ReactNode } from 'react';
import { NOT_AVAILABLE } from '@/lib/format';

// Match the padded Surface inset so rules meet the card edge and text aligns with its heading.
export function DetailGrid({ children }: { children: ReactNode }) {
    return (
        <dl className="-mx-panel grid gap-px border-t bg-border first:-mt-panel first:border-t-0 last:-mb-panel sm:grid-cols-2 sm:[&>*:last-child:nth-child(odd)]:col-span-2">
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
        <div className="flex min-w-0 flex-col gap-0.5 bg-card p-panel">
            <dt className="type-meta">{label}</dt>
            <dd className="type-body wrap-anywhere">
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
