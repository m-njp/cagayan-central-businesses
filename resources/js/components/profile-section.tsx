import type { ReactNode } from 'react';
import { Surface } from '@/components/ui/surface';

// One card of a business profile, with a Title Case heading.
export function ProfileSection({
    title,
    className,
    children,
}: {
    title: string;
    className?: string;
    children: ReactNode;
}) {
    return (
        <Surface padded title={title} className={className}>
            {children}
        </Surface>
    );
}
