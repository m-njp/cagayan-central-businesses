import type { ComponentProps, ReactNode } from 'react';
import { cn } from '@/lib/utils';

// The one card of the workspace: a bordered bg-card surface with an optional heading row and footer.
// Tables go in flush (the surface draws the border), forms and prose use `padded`.
export function Surface({
    title,
    toolbar,
    footer,
    padded,
    className,
    children,
    ...props
}: Omit<ComponentProps<'section'>, 'title'> & {
    title?: ReactNode;
    toolbar?: ReactNode;
    footer?: ReactNode;
    padded?: boolean;
}) {
    return (
        <section
            className={cn(
                'flex min-w-0 flex-col rounded-sm border bg-card text-card-foreground',
                className,
            )}
            {...props}
        >
            {(title || toolbar) && (
                <header className="flex min-h-row flex-wrap items-center justify-between gap-2 border-b px-panel py-1.5">
                    {title ? <h2 className="type-item">{title}</h2> : <span />}
                    {toolbar && (
                        <div className="flex flex-wrap items-center gap-1.5">
                            {toolbar}
                        </div>
                    )}
                </header>
            )}
            <div
                className={cn(
                    'min-w-0',
                    padded
                        ? 'flex flex-col gap-2 p-panel'
                        : '[&_[data-slot=table-container]]:rounded-none [&_[data-slot=table-container]]:border-0',
                )}
            >
                {children}
            </div>
            {footer && (
                <footer className="border-t px-panel py-1.5">{footer}</footer>
            )}
        </section>
    );
}
