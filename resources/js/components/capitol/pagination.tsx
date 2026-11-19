import { Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import type { Pagination as PaginationData } from '@/types/workspace';

// Server pages link to the next URL; a list that is already loaded passes `onPage` and pages locally.
export function Pagination({
    data,
    onPage,
    label = 'Pagination',
    showSinglePage = false,
}: {
    data: Pick<PaginationData, 'page' | 'lastPage'> &
        Partial<Pick<PaginationData, 'previous' | 'next'>>;
    onPage?: (page: number) => void;
    label?: string;
    showSinglePage?: boolean;
}) {
    if (data.lastPage <= 1 && !showSinglePage) return null;
    const step = (label: string, target: number, href?: string | null) => {
        if (onPage)
            return (
                <Button
                    variant="ghost"
                    size="sm"
                    disabled={target < 1 || target > data.lastPage}
                    onClick={() => onPage(target)}
                >
                    {label}
                </Button>
            );
        return href ? (
            <Button variant="ghost" size="sm" asChild>
                <Link href={href} preserveScroll>
                    {label}
                </Link>
            </Button>
        ) : (
            <Button variant="ghost" size="sm" disabled>
                {label}
            </Button>
        );
    };

    return (
        <nav aria-label={label} className="flex items-center justify-end gap-2">
            {step('Previous', data.page - 1, data.previous)}
            <span className="type-meta">
                Page <span className="font-mono tabular-nums">{data.page}</span>{' '}
                of{' '}
                <span className="font-mono tabular-nums">{data.lastPage}</span>
            </span>
            {step('Next', data.page + 1, data.next)}
        </nav>
    );
}
