import { categoryColor } from '@/lib/category-colors';

// The name is always printed, so color is never the only signal.
export function CategoryChip({ name, color }: { name: string; color: number }) {
    const palette = categoryColor(color);
    return (
        <span
            className={`inline-flex max-w-48 items-center gap-1.5 rounded-sm px-2 py-0.5 type-meta whitespace-normal text-foreground ${palette.chip}`}
        >
            <span
                className={`size-2 shrink-0 rounded-full ${palette.dot}`}
                aria-hidden="true"
            />
            <span className="min-w-0 wrap-anywhere">{name}</span>
        </span>
    );
}
