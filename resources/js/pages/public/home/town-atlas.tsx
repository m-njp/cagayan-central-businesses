import { router, usePage } from '@inertiajs/react';
import { Grip, Square } from 'lucide-react';
import { useRef, useState } from 'react';
import {
    FinanceMapControls,
    FinanceMapFigures,
} from '@/components/map/finance-map-panel';
import {
    financeMapSelection,
    financeMapShade,
    financeMapObservation,
    MAP_MEASURE,
} from '@/lib/finance-map';
import { observationLabel } from '@/lib/performance-format';
import type { FinanceMap } from '@/types/finance-map';
import { HomeFocusView } from './home-focus-view';
import { LazyProvinceMap } from '@/components/map/lazy-province-map';
import type { MapStyle, ProvinceMapProps } from '@/components/map/province-map';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group';
import { pageSearchParams } from '@/lib/navigation';
import { home } from '@/routes';
import type { TownAtlas as Atlas } from '@/types/data';

const NONE = 'province';

export function TownAtlas({
    atlas,
    finances,
    mapStyle,
    onMapStyleChange,
}: {
    atlas: Atlas;
    finances: FinanceMap;
    mapStyle: MapStyle;
    onMapStyleChange: (style: MapStyle) => void;
}) {
    const { url } = usePage();
    const [expanded, setExpanded] = useState(false);
    const expandTrigger = useRef<HTMLButtonElement>(null);
    const params = pageSearchParams(url);
    const townCode = params.get('town');
    const selection = financeMapSelection(finances, params);
    const town = atlas.towns.find((item) => item.code === townCode) ?? null;
    const shown = town ?? atlas.province;
    const change = (patch: Record<string, string | null>) => {
        const query = Object.fromEntries(params);
        for (const [key, value] of Object.entries(patch)) {
            if (value) query[key] = value;
            else delete query[key];
        }
        router.push({
            url: home({ query }).url,
            preserveScroll: true,
            preserveState: true,
        });
    };
    const choose = (code: string | null) => change({ town: code });
    // The province, then each municipality, in a loop.
    const order = [null, ...atlas.towns.map((item) => item.code)];
    const step = (direction: -1 | 1) =>
        choose(
            order[
                (order.indexOf(town?.code ?? null) + direction + order.length) %
                    order.length
            ],
        );

    // One definition of the map for the hero and the full screen view.
    const map: ProvinceMapProps = {
        mapStyle,
        water: true,
        zoomable: true,
        label: `Government Income by municipality and city in Cagayan for ${selection.year ?? 'the latest available year'}`,
        shades: Object.fromEntries(
            atlas.towns.map((item) => [
                item.code,
                financeMapShade(
                    finances,
                    item.code,
                    selection.year,
                    MAP_MEASURE,
                ),
            ]),
        ),
        highlight: town ? [town.code] : [],
        describe: (code, name) => {
            const point = financeMapObservation(
                finances,
                code,
                selection.year,
                MAP_MEASURE,
            );
            return (
                <span className="flex flex-col gap-0.5">
                    <span className="font-medium">{name}</span>
                    <span className="tabular-nums opacity-80">
                        {observationLabel(point, 'php')}
                    </span>
                </span>
            );
        },
        onSelect: choose,
    };
    const figures = (
        <>
            <div className="flex flex-col gap-1">
                <p className="type-label">
                    {shown.kind === 'province'
                        ? 'Province'
                        : shown.kind === 'city'
                          ? 'City'
                          : 'Municipality'}
                </p>
                <h2 aria-live="polite" className="type-title">
                    {shown.kind === 'province'
                        ? 'Cagayan Province'
                        : shown.name}
                </h2>
            </div>

            <FinanceMapFigures
                finances={finances}
                code={shown.code}
                {...selection}
                population={shown.population}
                aggregate={shown.kind === 'province'}
            />
        </>
    );

    return (
        <div className="grid gap-6 lg:grid-cols-[minmax(0,1fr)_20rem] lg:gap-8">
            <div className="dark h-[28rem] md:h-[36rem] lg:h-[44rem]">
                <LazyProvinceMap
                    {...map}
                    onExpand={(trigger) => {
                        expandTrigger.current = trigger;
                        setExpanded(true);
                    }}
                    className="size-full"
                />
            </div>

            <aside className="flex flex-col gap-5 border-t border-current/15 pt-5 lg:border-t-0 lg:border-l lg:pt-0 lg:pl-8">
                <div className="flex items-center gap-2">
                    <Select
                        value={town?.code ?? NONE}
                        onValueChange={(value) =>
                            choose(value === NONE ? null : value)
                        }
                    >
                        <SelectTrigger
                            variant="quiet"
                            aria-label="Choose a town"
                            className="min-w-0 flex-1"
                        >
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value={NONE}>All of Cagayan</SelectItem>
                            {atlas.towns.map((item) => (
                                <SelectItem key={item.code} value={item.code}>
                                    {item.name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <ToggleGroup
                        type="single"
                        value={mapStyle}
                        aria-label="Home map style"
                        onValueChange={(value) => {
                            if (value === 'dotted' || value === 'solid')
                                onMapStyleChange(value);
                        }}
                        className="shrink-0"
                    >
                        <ToggleGroupItem
                            value="dotted"
                            className="size-touch border-b border-transparent p-0 text-brand-foreground/75 hover:bg-brand-foreground/10 hover:text-brand-foreground data-[state=on]:border-current data-[state=on]:bg-transparent data-[state=on]:text-brand-foreground"
                            aria-label="Dotted map"
                            title="Dotted Map"
                        >
                            <Grip aria-hidden="true" />
                        </ToggleGroupItem>
                        <ToggleGroupItem
                            value="solid"
                            className="size-touch border-b border-transparent p-0 text-brand-foreground/75 hover:bg-brand-foreground/10 hover:text-brand-foreground data-[state=on]:border-current data-[state=on]:bg-transparent data-[state=on]:text-brand-foreground"
                            aria-label="Solid map"
                            title="Solid Map"
                        >
                            <Square
                                className="fill-current"
                                aria-hidden="true"
                            />
                        </ToggleGroupItem>
                    </ToggleGroup>
                </div>

                <FinanceMapControls
                    finances={finances}
                    {...selection}
                    onChange={change}
                />

                <section
                    aria-label="Figures"
                    className="flex flex-col gap-4 rounded-lg border border-current/15 p-4 [&_.type-meta]:text-brand-foreground/90"
                >
                    {figures}
                </section>
            </aside>
            <HomeFocusView
                open={expanded}
                onOpenChange={setExpanded}
                onCloseAutoFocus={(event) => {
                    const trigger = expandTrigger.current;
                    if (!trigger?.isConnected) return;
                    event.preventDefault();
                    requestAnimationFrame(() =>
                        trigger.focus({ preventScroll: true }),
                    );
                }}
                map={map}
                figures={figures}
                finances={finances}
                area={{
                    code: shown.code,
                    name:
                        shown.kind === 'province'
                            ? 'Cagayan Province'
                            : shown.name,
                }}
                townCode={town?.code ?? null}
                onStep={step}
            />
        </div>
    );
}
