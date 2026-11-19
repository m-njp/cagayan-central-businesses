import { lazy, Suspense } from 'react';
import type { ComponentProps, ReactNode } from 'react';
import { FinanceYearsTable } from '@/components/finance-years-table';
import { LazyProvinceMap } from '@/components/map/lazy-province-map';
import type { ProvinceMapProps } from '@/components/map/province-map';
import { DialogDescription, DialogTitle } from '@/components/ui/dialog';
import { FocusDialog, FocusNavigation } from '@/components/ui/focus-dialog';
import { Skeleton } from '@/components/ui/skeleton';
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
    history,
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
    history: FinancialHistory;
    area: { code: string; name: string };
    townCode: string | null;
    // Moves to the previous (-1) or next (1) area: the province, then each municipality.
    onStep: (direction: -1 | 1) => void;
}) {
    // The chosen municipality is drawn from its barangay file: boundaries and names only, no figure per barangay.
    const { shape, failed } = useTownShape(open ? townCode : null);
    const shade = townCode ? (map.shades?.[townCode] ?? null) : null;

    return (
        <FocusDialog
            open={open}
            onOpenChange={onOpenChange}
            onCloseAutoFocus={onCloseAutoFocus}
            closeButton
            closeClassName="max-md:right-1/2 max-md:translate-x-1/2"
            className="inset-0 block h-dvh max-w-none translate-x-0 translate-y-0 text-foreground sm:max-w-none"
        >
            <DialogTitle className="sr-only">Municipality Overview</DialogTitle>
            <DialogDescription className="sr-only">
                Map, figures and yearly trend for the selected area.
            </DialogDescription>
            <FocusNavigation
                previousLabel="Previous area"
                nextLabel="Next area"
                onStep={onStep}
            />
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
                    <div className="flex min-w-0 flex-col gap-3 md:self-center [&_.type-meta]:text-foreground">
                        {figures}
                    </div>
                    {history.rows.length > 0 && (
                        <div className="min-w-0 [&_.recharts-cartesian-axis-tick-value]:fill-foreground [&_.recharts-cartesian-axis-tick-value]:font-medium [&_[aria-label=Legend]_li]:text-foreground">
                            <Suspense
                                fallback={
                                    <Skeleton className="h-48 w-full sm:h-56" />
                                }
                            >
                                <FinanceHistoryChart
                                    history={history}
                                    variant="outcome"
                                />
                            </Suspense>
                        </div>
                    )}
                    <div className="min-w-0 md:col-start-2">
                        <FinanceYearsTable
                            finances={finances}
                            code={area.code}
                            name={area.name}
                            limit={3}
                            plain
                        />
                    </div>
                </div>
            </div>
        </FocusDialog>
    );
}
