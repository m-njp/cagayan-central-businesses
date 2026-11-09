import { Link, usePage } from '@inertiajs/react';
import { isCurrentSection } from '@/lib/navigation';
import { cn } from '@/lib/utils';
import type { NavItem } from '@/types/ui';

// Phone navigation (design.md, "App shell"). At most four items, each with a label.
export function AppBottomBar({ items }: { items: NavItem[] }) {
    const { url } = usePage();

    return (
        <nav
            aria-label="Main"
            className="fixed inset-x-0 bottom-0 z-20 border-t bg-card pb-[env(safe-area-inset-bottom)] lg:hidden"
        >
            <ul className="grid auto-cols-fr grid-flow-col divide-x">
                {items.slice(0, 4).map((item) => {
                    const current = isCurrentSection(url, item.href);

                    return (
                        <li key={item.title}>
                            <Link
                                href={item.href}
                                prefetch
                                aria-current={current ? 'page' : undefined}
                                className={cn(
                                    'relative flex h-14 flex-col items-center justify-center gap-1 type-pin text-foreground transition-colors outline-none hover:bg-accent focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:ring-inset',
                                    current && 'bg-accent',
                                )}
                            >
                                {current && (
                                    <span
                                        aria-hidden="true"
                                        className="absolute inset-x-0 top-0 h-0.5 bg-highlight"
                                    />
                                )}
                                <item.icon
                                    className="size-5"
                                    aria-hidden="true"
                                />
                                {item.title}
                            </Link>
                        </li>
                    );
                })}
            </ul>
        </nav>
    );
}
