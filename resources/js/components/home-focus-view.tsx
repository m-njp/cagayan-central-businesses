import { lazy, Suspense } from 'react';
import type { ReactNode } from 'react';
import { FinanceYearsTable } from '@/components/finance-years-table';
import { LazyProvinceMap } from '@/components/lazy-province-map';
import type { ProvinceMapProps } from '@/components/province-map';
import { Button } from '@/components/ui/button';
import { DialogDescription, DialogTitle } from '@/components/ui/dialog';
import { FocusDialog } from '@/components/ui/focus-dialog';
import { Skeleton } from '@/components/ui/skeleton';
import { icons } from '@/lib/icons';
import { useTownShape } from '@/hooks/use-town-shape';
import type { FinancialHistory } from '@/types/economic-performance';
import type { FinanceMap } from '@/types/finance-map';

const FinanceHistoryChart = lazy(
    () => import('@/components/charts/finance-history-chart'),
);

// The Home map at full size over a blurred page: the area on its own (the whole province, or the chosen municipality
// by itself), its figures, then its yearly trend as a chart and as a table. Four bare areas, no cards or lines.
// Selection is the same URL state as the hero, so opening it requests nothing new.
export function HomeFocusView({
    open,
    onOpenChange,
    map,
    figures,
    finances,
    area,
    townCode,
    onStep,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    map: ProvinceMapProps;
    figures: ReactNode;
    finances: FinanceMap;
    area: { code: string; name: string };
    townCode: string | null;
    // Moves to the previous (-1) or next (1) area: the province, then each municipality.
    onStep: (direction: -1 | 1) => void;
}) {
    // The chosen municipality is drawn from its barangay file: boundaries and names only, no figure per barangay.
    const { shape } = useTownShape(open ? townCode : null);
    const history: FinancialHistory = {
        metrics: finances.metrics,
        rows: [...finances.years]
            .sort((a, b) => a - b)
            .flatMap((year) => {
                const observations = finances.observations[area.code]?.[year];
                return observations ? [{ year, observations }] : [];
            }),
        axisMaximum: '',
        note: null,
    };
    const shade = townCode ? (map.shades?.[townCode] ?? null) : null;

    return (
        <FocusDialog
            open={open}
            onOpenChange={onOpenChange}
            closeButton
            overlayClassName="bg-brand/70"
            className="dark max-h-dvh overflow-y-auto p-4 text-foreground sm:max-w-6xl"
        >
            <DialogTitle className="sr-only">Municipality Overview</DialogTitle>
            <DialogDescription className="sr-only">
                Map, figures and yearly trend for the selected area.
            </DialogDescription>
            <div className="grid gap-8 pt-12 md:grid-cols-2 md:gap-x-12 md:pt-0">
                <div className="h-80 md:h-[26rem]">
                    {!townCode ? (
                        <LazyProvinceMap {...map} className="size-full" />
                    ) : shape ? (
                        <LazyProvinceMap
                            key={townCode}
                            source={shape.raw}
                            mapStyle={map.mapStyle}
                            zoomable
                            label={`Government Income in ${area.name}`}
                            shades={Object.fromEntries(
                                shape.features.map((feature) => [
                                    feature.properties.psgc_code,
                                    shade,
                                ]),
                            )}
                            className="size-full"
                        />
                    ) : (
                        <Skeleton
                            role="status"
                            aria-label="Loading the map"
                            className="size-full bg-current/10"
                        />
                    )}
                </div>
                <div className="flex flex-col gap-4">
                    <div className="flex justify-end gap-1 max-md:absolute max-md:top-2 max-md:left-4 md:pr-12">
                        <Button
                            variant="outline"
                            size="icon"
                            aria-label="Previous area"
                            className="size-10"
                            onClick={() => onStep(-1)}
                        >
                            <icons.previous aria-hidden="true" />
                        </Button>
                        <Button
                            variant="outline"
                            size="icon"
                            aria-label="Next area"
                            className="size-10"
                            onClick={() => onStep(1)}
                        >
                            <icons.next aria-hidden="true" />
                        </Button>
                    </div>
                    {figures}
                </div>
                <div className="min-w-0">
                    {history.rows.length > 0 && (
                        <Suspense
                            fallback={<Skeleton className="h-56 w-full" />}
                        >
                            <FinanceHistoryChart
                                history={history}
                                variant="outcome"
                            />
                        </Suspense>
                    )}
                </div>
                <div className="min-w-0">
                    <FinanceYearsTable
                        finances={finances}
                        code={area.code}
                        name={area.name}
                        limit={3}
                    />
                </div>
            </div>
        </FocusDialog>
    );
}
