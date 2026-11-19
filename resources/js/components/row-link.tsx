import { Link } from '@inertiajs/react';
import type { InertiaLinkProps } from '@inertiajs/react';
import { ChevronRight } from 'lucide-react';
import type { ReactNode } from 'react';

/**
 * The link that makes a whole table row clickable (design.md, "Dense Data Rule"): its hit area stretches over the
 * row, the row shows the hover and focus, and a chevron replaces the underline. Put it in the row's first cell.
 */
export function RowLink({
    href,
    onClick,
    children,
}: {
    href: InertiaLinkProps['href'];
    /** For a row that opens in place: prevent the visit and handle it locally. */
    onClick?: InertiaLinkProps['onClick'];
    children: ReactNode;
}) {
    return (
        <Link
            href={href}
            onClick={onClick}
            data-row-link
            className="outline-none after:absolute after:inset-0 after:content-['']"
        >
            {children}
        </Link>
    );
}

/** The trailing chevron cell of a clickable row. */
export function RowChevron() {
    return (
        <ChevronRight
            aria-hidden="true"
            className="ml-auto size-4 shrink-0 text-muted-foreground"
        />
    );
}
