import { Head, Link, usePage } from '@inertiajs/react';
import { lazy, useEffect, useState } from 'react';
import { BarangayAtlas } from '@/components/barangay-atlas';
import PerformanceVisual from '@/components/charts/performance-visual';
import { DefinitionList } from '@/components/definition-list';
import type { MapStyle } from '@/components/province-map';
import { TownAtlas } from '@/components/town-atlas';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardFooter, CardHeader } from '@/components/ui/card';
import { pageSearchParams } from '@/lib/navigation';
import { economicPerformance } from '@/routes/public';
import { index as suggestions } from '@/routes/public/wishes';
import type { HomeOverview } from '@/types/data';

const WishCloud = lazy(() => import('@/components/charts/wish-cloud'));

type Props = {
    overview: HomeOverview;
};

export default function Home({ overview }: Props) {
    const { url, props } = usePage();
    const townCode = pageSearchParams(url).get('town');
    const town = overview.atlas.towns.find((item) => item.code === townCode);
    const query = town ? { town: town.code } : {};
    const [mapStyle, setMapStyle] = useState<MapStyle>('dotted');
    useEffect(() => {
        try {
            const stored = localStorage.getItem('map-style');
            if (stored === 'solid' || stored === 'dotted') setMapStyle(stored);
        } catch {
            /* Storage may be disabled; the control still works for this visit. */
        }
    }, []);
    const chooseMapStyle = (style: MapStyle) => {
        setMapStyle(style);
        try {
            localStorage.setItem('map-style', style);
        } catch {
            /* Keep the in-memory preference. */
        }
    };
    return (
        <>
            <Head title="Home" />

            <div className="flex flex-col">
                <section className="overflow-hidden rounded-2xl bg-brand text-brand-foreground">
                    {/* Kept for screen readers and the document outline. The map is the visible hero. */}
                    <h1 className="sr-only">See where Cagayan does business</h1>
                    <div className="px-5 py-6 md:px-8">
                        <TownAtlas
                            atlas={overview.atlas}
                            finances={overview.finances}
                            mapStyle={mapStyle}
                            onMapStyleChange={chooseMapStyle}
                        />
                    </div>
                </section>
            </div>

            <section
                aria-label="Explore Your Town"
                className="grid reveal gap-3"
            >
                <Card className="gap-3 py-4 shadow-none">
                    <CardHeader className="flex-row items-start justify-between gap-3 px-4">
                        <h2 className="min-w-0 type-heading">
                            What Businesses Would You Like in Your Town?
                        </h2>
                        {overview.wishes && (
                            <Badge
                                variant="outline"
                                className="ml-auto border-destructive-foreground/30 bg-destructive-foreground/10 type-pin text-destructive-foreground"
                            >
                                {overview.wishes.source}
                            </Badge>
                        )}
                    </CardHeader>
                    {overview.wishes && (
                        <div className="px-4">
                            <PerformanceVisual>
                                <WishCloud snapshot={overview.wishes} />
                            </PerformanceVisual>
                        </div>
                    )}
                    <CardFooter className="px-4">
                        <Button asChild>
                            <Link href={suggestions({ query })}>
                                Share Your Wish
                            </Link>
                        </Button>
                    </CardFooter>
                </Card>
                <Card className="flex-row flex-wrap items-center justify-between gap-3 px-4 py-4 shadow-none">
                    <h2 className="type-heading">
                        Explore Your Town's Economy
                    </h2>
                    <Button asChild variant="outline">
                        <Link href={economicPerformance({ query })}>
                            Explore the Economy
                        </Link>
                    </Button>
                </Card>
            </section>

            <section
                id="about-data"
                aria-labelledby="about-data-heading"
                className="grid reveal scroll-mt-6 gap-6 lg:grid-cols-[minmax(0,2fr)_minmax(0,3fr)] lg:gap-10"
            >
                <div className="flex flex-col gap-4">
                    <h2 id="about-data-heading" className="type-heading">
                        About the Data
                    </h2>
                    <BarangayAtlas
                        towns={overview.atlas.towns}
                        mapStyle={mapStyle}
                    />
                </div>
                <DefinitionList
                    items={[
                        {
                            term: 'Town Figures',
                            description: (
                                <>
                                    Population:{' '}
                                    <a
                                        href="https://psa.gov.ph/statistics/population-and-housing/node/1684077888"
                                        target="_blank"
                                        rel="noreferrer"
                                        className="underline underline-offset-2"
                                    >
                                        PSA 2024 Census of Population
                                        <span className="sr-only">
                                            {' '}
                                            (opens in a new tab)
                                        </span>
                                    </a>
                                    . Government revenue and expenses: verified
                                    municipal and city financial statements
                                    published by the Commission on Audit.
                                </>
                            ),
                        },
                        {
                            term: 'Records',
                            description: props.simulated
                                ? 'Population and geographic boundaries come from published sources. Business records are simulated for this presentation.'
                                : 'Population and geographic boundaries come from published sources. Business records appear once the Capitol approves them.',
                        },
                        {
                            term: 'Area',
                            description:
                                'Cagayan’s 28 municipalities and Tuguegarao City. Barangay population and finance breakdowns beyond totals are loaded for Tuguegarao City only.',
                        },
                        {
                            term: 'Period',
                            description:
                                'Official figures retain their source reference years.',
                        },
                        {
                            term: 'Sales',
                            description:
                                'Individual business sales are not displayed. Future public sales summaries require disclosure controls.',
                        },
                        {
                            term: 'Boundaries',
                            description:
                                'Simplified 2023 PSGC boundaries paired with 2024 census populations. For geographic comparison only; not for surveying or determining legal boundaries.',
                        },
                    ]}
                />
            </section>
        </>
    );
}
