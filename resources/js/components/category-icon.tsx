import { categoryIcon } from '@/lib/icons';
import { cn } from '@/lib/utils';

// The category's icon in muted ink, named for screen readers, in place of an uppercase label.
export function CategoryIcon({
    code,
    name,
    className,
}: {
    code: string;
    name: string;
    className?: string;
}) {
    const Icon = categoryIcon(code);

    return (
        <Icon
            role="img"
            aria-label={name}
            className={cn('size-5 shrink-0 text-muted-foreground', className)}
        />
    );
}
