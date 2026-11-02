import { SimulatedLabel } from '@/components/privacy/simulated-label';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { BirMark, MilestoneMark } from '@/components/business-badges';
import { CategoryIcon } from '@/components/category-icon';
import { LazyBusinessMap } from '@/components/map/lazy-business-map';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Skeleton } from '@/components/ui/skeleton';
import { Toggle } from '@/components/ui/toggle';
import { icons } from '@/lib/icons';
import { pageSearchParams } from '@/lib/navigation';
import {
    businessQuery,
    PAGE_SIZE,
    parseBusinessView,
    visibleBusinesses,
} from '@/lib/public-businesses';
import type { BusinessView, TownBusinesses } from '@/lib/public-businesses';
import { index, show } from '@/routes/public/businesses';
import { index as suggestions } from '@/routes/public/wishes';

// One request per town for the whole visit; revisiting a town or changing any other filter asks the server for nothing.
const towns = new Map<string, TownBusinesses>();

type Directory = {
    towns: { value: string; label: string }[];
    categories: { code: string; name: string; color: number }[];
};

export default function PublicBusinesses({
    directory,
    businesses,
}: {
    directory: Directory;
    businesses: TownBusinesses | null;
}) {
    const { url } = usePage();
    const view = parseBusinessView(
        pageSearchParams(url),
        directory.towns,
        businesses?.barangays ?? [],
        directory.categories,
    );
    const town =
        businesses && businesses.town === view.town ? businesses : null;
    const [search, setSearch] = useState(view.q);
    const [highlight, setHighlight] = useState<string | null>(null);
    const [loading, setLoading] = useState(false);
    const [failed, setFailed] = useState(false);
    const categories = Object.fromEntries(
        directory.categories.map((c) => [c.code, c]),
    );
    const barangayName = Object.fromEntries(
        (town?.barangays ?? []).map((b) => [b.value, b.label]),
    );
    const SearchIcon = icons.search;
    const rows = town ? visibleBusinesses(town.rows, view) : [];

    if (businesses) towns.set(businesses.town, businesses);

    const go = (patch: Partial<BusinessView>, replace = false) => {
        const visit = {
            url: index({
                query: businessQuery({ ...view, shown: PAGE_SIZE, ...patch }),
            }).url,
            preserveState: true,
            preserveScroll: true,
        };
        if (replace) router.replace(visit);
        else router.push(visit);
    };

    const chooseTown = (code: string) => {
        const next = {
            ...view,
            town: code,
            barangay: null,
            q: '',
            shown: PAGE_SIZE,
        };
        const target = index({ query: businessQuery(next) }).url;
        const cached = towns.get(code);
        setSearch('');
        setFailed(false);
        if (cached) {
            router.push({
                url: target,
                preserveState: true,
                preserveScroll: true,
                props: (props) => ({ ...props, businesses: cached }),
            });
            return;
        }
        setLoading(true);
        router.get(
            target,
            {},
            {
                only: ['businesses'],
                preserveState: true,
                preserveScroll: true,
                onError: () => setFailed(true),
                onNetworkError: () => setFailed(true),
                onHttpException: () => setFailed(true),
                onFinish: () => setLoading(false),
            },
        );
    };

    useEffect(() => {
        if (search === view.q) return;
        const timer = setTimeout(() => go({ q: search }, true), 250);
        return () => clearTimeout(timer);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [search]);

    const choose = (
        label: string,
        value: string | null,
        options: { value: string; label: string }[],
        onChange: (value: string | null) => void,
        all?: string,
    ) => (
        <Select
            value={value ?? 'all'}
            onValueChange={(next) => onChange(next === 'all' ? null : next)}
            disabled={!town && !!all}
        >
            <SelectTrigger aria-label={label} className="w-full">
                <SelectValue placeholder={label} />
            </SelectTrigger>
            <SelectContent>
                {all && <SelectItem value="all">{all}</SelectItem>}
                {options.map((option) => (
                    <SelectItem key={option.value} value={option.value}>
                        {option.label}
                    </SelectItem>
                ))}
            </SelectContent>
        </Select>
    );

    return (
        <>
            <Head title="Businesses" />
            <div className="flex flex-col gap-3">
                <h1 className="type-title">Businesses</h1>
                <div className="grid gap-2 sm:grid-cols-2 lg:grid-cols-[repeat(4,minmax(0,1fr))_auto]">
                    <Select
                        value={view.town ?? undefined}
                        onValueChange={chooseTown}
                    >
                        <SelectTrigger aria-label="Town" className="w-full">
                            <SelectValue placeholder="Choose a Town" />
                        </SelectTrigger>
                        <SelectContent>
                            {directory.towns.map((option) => (
                                <SelectItem
                                    key={option.value}
                                    value={option.value}
                                >
                                    {option.label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <div className="relative">
                        <SearchIcon
                            className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                            aria-hidden="true"
                        />
                        <Input
                            type="search"
                            aria-label="Search by name"
                            placeholder="Search by name"
                            value={search}
                            maxLength={60}
                            disabled={!town}
                            className="pl-9"
                            onChange={(event) => setSearch(event.target.value)}
                        />
                    </div>
                    {choose(
                        'Barangay',
                        view.barangay,
                        town?.barangays ?? [],
                        (barangay) => go({ barangay }),
                        'All Barangays',
                    )}
                    {choose(
                        'Category',
                        view.category,
                        directory.categories.map((c) => ({
                            value: c.code,
                            label: c.name,
                        })),
                        (category) => go({ category }),
                        'All Categories',
                    )}
                    <div className="flex gap-2">
                        <Toggle
                            variant="outline"
                            disabled={!town}
                            pressed={view.bir}
                            onPressedChange={(bir) => go({ bir })}
                        >
                            BIR Registered
                        </Toggle>
                        {town && (
                            <Button
                                variant="outline"
                                className="lg:hidden"
                                onClick={() => go({ map: !view.map })}
                            >
                                {view.map ? 'Show List' : 'Show Map'}
                            </Button>
                        )}
                    </div>
                </div>
            </div>

            {!view.town || (!town && !loading && !failed) ? (
                <p className="rounded-sm border bg-card p-6 type-body text-muted-foreground">
                    Choose a town to see its registered businesses.
                </p>
            ) : failed ? (
                <div
                    role="alert"
                    className="flex flex-wrap items-center gap-3 rounded-sm border bg-card p-6 type-body"
                >
                    Businesses could not load.
                    <Button
                        variant="outline"
                        size="sm"
                        onClick={() => chooseTown(view.town as string)}
                    >
                        Try Again
                    </Button>
                </div>
            ) : (
                // From lg the list and the map share one fixed-height panel: the list scrolls inside it, so both edges line up.
                <div className="grid gap-3 lg:h-[calc(100dvh-12rem)] lg:min-h-[28rem] lg:grid-cols-2">
                    <div
                        className={
                            view.map
                                ? 'hidden min-h-0 lg:flex lg:flex-col'
                                : 'flex min-h-0 flex-col'
                        }
                    >
                        {!town || loading ? (
                            <div
                                className="flex flex-col gap-2"
                                aria-busy="true"
                            >
                                {Array.from({ length: 6 }, (_, i) => (
                                    <Skeleton
                                        key={i}
                                        className="h-11 w-full rounded-sm"
                                    />
                                ))}
                            </div>
                        ) : rows.length === 0 ? (
                            <p className="rounded-sm border bg-card p-4 type-body text-muted-foreground">
                                {town.rows.length === 0
                                    ? 'No registered businesses here yet.'
                                    : 'No businesses match these filters.'}
                            </p>
                        ) : (
                            <div className="min-h-0 flex-1 overflow-y-auto overscroll-contain rounded-sm border bg-card">
                                <div className="flex justify-end px-3 pt-3">
                                    <SimulatedLabel />
                                </div>
                                <ul className="divide-y">
                                    {rows.slice(0, view.shown).map((row) => {
                                        const category =
                                            categories[row.category];
                                        return (
                                            <li
                                                key={row.id}
                                                onMouseEnter={() =>
                                                    setHighlight(row.id)
                                                }
                                                onMouseLeave={() =>
                                                    setHighlight(null)
                                                }
                                            >
                                                <Link
                                                    href={show(row.id)}
                                                    onFocus={() =>
                                                        setHighlight(row.id)
                                                    }
                                                    className="flex items-center gap-3 px-3 py-2.5 hover:bg-accent focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none focus-visible:ring-inset"
                                                >
                                                    {category && (
                                                        <CategoryIcon
                                                            code={category.code}
                                                            name={category.name}
                                                        />
                                                    )}
                                                    <span className="min-w-0 flex-1 truncate type-item">
                                                        {row.name}
                                                    </span>
                                                    {/* Two fixed slots, so every row lines up whichever marks it has. */}
                                                    <span className="grid shrink-0 grid-cols-2 gap-2 *:grid *:size-5 *:place-items-center">
                                                        <span>
                                                            {row.milestone && (
                                                                <MilestoneMark
                                                                    years={
                                                                        row.milestone
                                                                    }
                                                                />
                                                            )}
                                                        </span>
                                                        <span>
                                                            {row.bir && (
                                                                <BirMark />
                                                            )}
                                                        </span>
                                                    </span>
                                                </Link>
                                            </li>
                                        );
                                    })}
                                </ul>
                                {rows.length > view.shown && (
                                    <div className="border-t p-2">
                                        <Button
                                            variant="outline"
                                            className="w-full"
                                            onClick={() =>
                                                go(
                                                    {
                                                        shown:
                                                            view.shown +
                                                            PAGE_SIZE,
                                                    },
                                                    true,
                                                )
                                            }
                                        >
                                            Show More
                                        </Button>
                                    </div>
                                )}
                            </div>
                        )}
                        {view.barangay && town && (
                            <p className="pt-2 type-body">
                                Missing a business in{' '}
                                {barangayName[view.barangay]}?{' '}
                                <Link
                                    href={suggestions()}
                                    className="underline underline-offset-2"
                                >
                                    Suggest One
                                </Link>
                            </p>
                        )}
                    </div>
                    <div
                        className={`h-[70dvh] lg:h-full ${view.map ? '' : 'hidden lg:block'}`}
                    >
                        {view.town && (
                            <LazyBusinessMap
                                townCode={view.town}
                                barangay={view.barangay}
                                businesses={rows}
                                highlight={highlight}
                                onSelect={(id) => router.visit(show(id))}
                                label="Map of registered businesses"
                            />
                        )}
                    </div>
                </div>
            )}
        </>
    );
}
