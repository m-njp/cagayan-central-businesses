import type { ReactNode } from 'react';
import { icons } from '@/lib/icons';
import { cn } from '@/lib/utils';

export function Disclosure({ title, open, className, children }: {
    title: ReactNode;
    open?: boolean;
    className?: string;
    children: ReactNode;
}) {
    return (
        <details open={open} className={cn('group/disclosure min-w-0', className)}>
            <summary className="flex min-h-touch cursor-pointer list-none items-center justify-between gap-2 rounded-sm px-2 type-body font-medium hover:bg-current/5 focus-visible:outline-2 focus-visible:outline-ring md:min-h-control-compact [&::-webkit-details-marker]:hidden">
                {title}
                <icons.expand aria-hidden="true" className="size-4 shrink-0 group-open/disclosure:rotate-180 motion-safe:transition-transform" />
            </summary>
            {children}
        </details>
    );
}
