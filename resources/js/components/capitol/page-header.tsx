import type { ReactNode } from 'react';

// The title bar every workspace page starts with: its own card, the page actions on the right.
export function PageHeader({
    title,
    children,
}: {
    title: ReactNode;
    children?: ReactNode;
}) {
    return (
        <header
            data-theme-surface
            className="flex min-h-12 flex-wrap items-center justify-between gap-2 rounded-sm border bg-card px-panel py-1.5"
        >
            <h1 className="type-heading">{title}</h1>
            {children && (
                <div className="flex flex-wrap items-center gap-2">
                    {children}
                </div>
            )}
        </header>
    );
}
