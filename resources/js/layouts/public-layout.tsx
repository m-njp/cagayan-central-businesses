import { Link, usePage } from '@inertiajs/react';
import {
    ChartNoAxesCombined,
    House,
    MessageSquarePlus,
    Store,
} from 'lucide-react';
import type { ReactNode } from 'react';
import { AppBottomBar } from '@/components/app-bottom-bar';
import { AppearanceToggle } from '@/components/appearance-toggle';
import { KitaLogo } from '@/components/kita-logo';
import { isCurrentSection } from '@/lib/navigation';
import { cn } from '@/lib/utils';
import { home } from '@/routes';
import { economicPerformance } from '@/routes/public';
import { index as businesses } from '@/routes/public/businesses';
import { index as suggestions } from '@/routes/public/wishes';
import type { NavItem } from '@/types/ui';

// The start-a-business guide (P4) joins this list when it is built.
const items: NavItem[] = [
    { title: 'Home', href: home(), icon: House },
    {
        title: 'Economy',
        href: economicPerformance(),
        icon: ChartNoAxesCombined,
    },
    { title: 'Businesses', href: businesses(), icon: Store },
    { title: 'Suggestions', href: suggestions(), icon: MessageSquarePlus },
];

// Published sources behind the public figures and the map.
const SOURCES = [
    {
        name: 'Philippine Statistics Authority',
        href: 'https://psa.gov.ph/statistics/population-and-housing/node/1684077888',
    },
    { name: 'Commission on Audit', href: 'https://www.coa.gov.ph' },
    { name: 'OpenStreetMap', href: 'https://www.openstreetmap.org/copyright' },
];

// A slim top line that scrolls away with the page: the Kita lockup, then text links from lg (design.md,
// "App shell"). Phones keep the bottom bar. No public page links to the Capitol side, not even to its login (decisions.md, D20).
export default function PublicLayout({ children }: { children: ReactNode }) {
    const { props, url } = usePage();

    return (
        <div className="flex min-h-screen flex-col bg-background pb-14 lg:pb-0">
            <header className="border-b bg-card">
                <div className="mx-auto flex min-h-20 w-full max-w-6xl items-center gap-6 px-gutter py-3 md:px-6">
                    <Link
                        href={home()}
                        className="shrink-0 rounded-sm outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50"
                    >
                        <KitaLogo label={props.name} className="h-7 w-auto" />
                    </Link>
                    <nav
                        aria-label="Main"
                        className="ml-auto hidden items-center gap-1 rounded-sm border bg-muted/40 p-1 lg:flex"
                    >
                        {items.map((item) => {
                            const current = isCurrentSection(url, item.href);

                            return (
                                <Link
                                    key={item.title}
                                    href={item.href}
                                    prefetch
                                    aria-current={current ? 'page' : undefined}
                                    className={cn(
                                        'inline-flex min-h-10 items-center justify-center rounded-sm px-5 py-2 type-body font-medium outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50 motion-safe:transition-colors motion-safe:duration-(--duration-quick)',
                                        current
                                            ? 'bg-primary text-primary-foreground'
                                            : 'text-muted-foreground hover:bg-accent hover:text-foreground',
                                    )}
                                >
                                    {item.title}
                                </Link>
                            );
                        })}
                    </nav>
                    <div className="ml-auto flex shrink-0 items-center gap-2 lg:ml-2 lg:border-l lg:pl-4">
                        <AppearanceToggle />
                    </div>
                </div>
            </header>
            <main className="mx-auto flex w-full max-w-6xl flex-1 flex-col gap-section px-gutter pb-section md:px-6">
                {children}
            </main>
            <footer className="mt-auto bg-brand text-brand-foreground">
                <div className="mx-auto grid w-full max-w-6xl gap-8 px-gutter py-10 sm:grid-cols-2 md:px-6 lg:grid-cols-[minmax(0,1.5fr)_repeat(3,minmax(0,1fr))]">
                    <div className="flex flex-col gap-4">
                        <KitaLogo
                            variant="stacked"
                            reverse
                            label={props.name}
                            className="w-28"
                        />
                        <p className="max-w-xs type-meta text-brand-foreground/75">
                            {props.name} is a public information and citizen
                            engagement initiative of the Provincial Government
                            of Cagayan.
                        </p>
                    </div>
                    <nav aria-label="Footer" className="flex flex-col gap-3">
                        <h2 className="type-label text-brand-foreground/75">
                            Explore
                        </h2>
                        <ul className="flex flex-col gap-2 type-body">
                            {items.map((item) => (
                                <li key={item.title}>
                                    <Link
                                        href={item.href}
                                        className="underline-offset-4 hover:underline"
                                    >
                                        {item.title}
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    </nav>
                    <div className="flex flex-col gap-3">
                        <h2 className="type-label text-brand-foreground/75">
                            Sources
                        </h2>
                        <ul className="flex flex-col gap-2 type-body">
                            {SOURCES.map((source) => (
                                <li key={source.name}>
                                    <a
                                        href={source.href}
                                        target="_blank"
                                        rel="noreferrer"
                                        className="underline-offset-4 hover:underline"
                                    >
                                        {source.name}
                                        <span className="sr-only">
                                            {' '}
                                            (opens in a new tab)
                                        </span>
                                    </a>
                                </li>
                            ))}
                        </ul>
                    </div>
                    <div className="flex flex-col gap-3">
                        <h2 className="type-label text-brand-foreground/75">
                            Government
                        </h2>
                        <p className="flex flex-col gap-2 type-body">
                            <span>Republic of the Philippines</span>
                            <span>Provincial Government of Cagayan</span>
                        </p>
                    </div>
                </div>
                <div className="border-t border-brand-foreground/20">
                    <div className="mx-auto flex w-full max-w-6xl flex-wrap items-center justify-between gap-3 px-gutter py-4 type-meta text-brand-foreground/75 md:px-6">
                        <span className="inline-flex items-center gap-2">
                            <KitaLogo variant="icon" className="size-6" />
                            {props.name}
                        </span>
                        <span>Digital Innovation Challenge 2026</span>
                    </div>
                </div>
            </footer>
            <AppBottomBar items={items} />
        </div>
    );
}
