import { lazy, Suspense } from 'react';
import type { ComponentProps, ReactNode } from 'react';
import { FinanceYearsTable } from '@/components/finance-years-table';
import { LazyProvinceMap } from '@/components/map/lazy-province-map';
import type { ProvinceMapProps } from '@/components/map/province-map';
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

export function HomeFocusView({
    open,
    onOpenChange,
    onCloseAutoFocus,
    map,
    figures,
    finances,
    area,
    townCode,
    onStep,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    onCloseAutoFocus: ComponentProps<typeof FocusDialog>['onCloseAutoFocus'];
    map: ProvinceMapProps;
    figures: ReactNode;
    finances: FinanceMap;
    area: { code: string; name: string };
    townCode: string | null;
    // Moves to the previous (-1) or next (1) area: the province, then each municipality.
    onStep: (direction: -1 | 1) => void;
}) {
    // The chosen municipality is drawn from its barangay file: boundaries and names only, no figure per barangay.
    const { shape, failed } = useTownShape(open ? townCode : null);
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
            onCloseAutoFocus={onCloseAutoFocus}
            closeButton
            closeClassName="max-md:right-1/2 max-md:translate-x-1/2"
            overlayClassName="bg-background/55 backdrop-blur-lg dark:bg-background/75"
            className="inset-0 block h-dvh max-w-none translate-x-0 translate-y-0 text-foreground sm:max-w-none"
        >
            <DialogTitle className="sr-only">Municipality Overview</DialogTitle>
            <DialogDescription className="sr-only">
                Map, figures and yearly trend for the selected area.
            </DialogDescription>
            <div className="pointer-events-none absolute inset-x-0 top-[max(--spacing(2),env(safe-area-inset-top))] z-10 flex justify-between pr-[max(--spacing(2),env(safe-area-inset-right))] pl-[max(--spacing(2),env(safe-area-inset-left))] md:top-1/2 md:-translate-y-1/2">
                <Button
                    variant="subtle"
                    size="touch"
                    aria-label="Previous area"
                    className="pointer-events-auto"
                    onClick={() => onStep(-1)}
                >
                    <icons.previous aria-hidden="true" />
                </Button>
                <Button
                    variant="subtle"
                    size="touch"
                    aria-label="Next area"
                    className="pointer-events-auto"
                    onClick={() => onStep(1)}
                >
                    <icons.next aria-hidden="true" />
                </Button>
            </div>
            <div className="absolute inset-x-0 top-[calc(var(--spacing-touch)+max(1rem,env(safe-area-inset-top)))] bottom-0 overflow-y-auto overscroll-contain md:static md:h-full">
                <div className="mx-auto grid min-h-full max-w-6xl content-center gap-5 px-gutter pb-[max(1rem,env(safe-area-inset-bottom))] md:grid-cols-2 md:gap-x-12 md:gap-y-8 md:pt-[calc(var(--spacing-touch)+max(1rem,env(safe-area-inset-top)))] md:pr-[calc(var(--spacing-touch)+max(1rem,env(safe-area-inset-right)))] md:pl-[calc(var(--spacing-touch)+max(1rem,env(safe-area-inset-left)))]">
                    <div className="h-60 md:h-[26rem]">
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
                        ) : failed ? (
                            <p
                                role="status"
                                className="grid size-full place-items-center type-body"
                            >
                                Map boundaries are not available.
                            </p>
                        ) : (
                            <Skeleton
                                role="status"
                                aria-label="Loading the map"
                                className="size-full bg-current/10"
                            />
                        )}
                    </div>
                    <div className="flex min-w-0 flex-col gap-4">{figures}</div>
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
            </div>
        </FocusDialog>
    );
}
