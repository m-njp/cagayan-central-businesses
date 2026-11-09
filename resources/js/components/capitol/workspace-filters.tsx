import type { ReactNode } from 'react';
import { Surface } from '@/components/ui/surface';

export function WorkspaceFilters({
    children,
    title,
}: {
    children: ReactNode;
    title?: string;
}) {
    return (
        <Surface
            padded
            title={title}
            aria-label={title ?? 'Filters'}
            className="order-first lg:order-last"
        >
            {children}
        </Surface>
    );
}
