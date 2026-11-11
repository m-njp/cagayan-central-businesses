import { lazy, Suspense } from 'react';
import { Skeleton } from '@/components/ui/skeleton';
import { Surface } from '@/components/ui/surface';
import { VisualErrorBoundary } from '@/components/visual-error-boundary';
import { useNearViewport } from '@/hooks/use-near-viewport';
import { useTownShape } from '@/hooks/use-town-shape';
import { formatCount, NOT_AVAILABLE } from '@/lib/format';
import type { PopulationRow } from '@/types/population';

const AreaMap = lazy(() => import('@/components/map/area-map'));

// The municipality's barangays beside the count table: a barangay chosen here is the row chosen there.
export function PopulationMap({
    code,
    name,
    year,
    rows,
    focused,
    onFocus,
}: {
    code: string;
    name: string;
    year: number;
    rows: PopulationRow[];
    focused: string | null;
    onFocus: (place: string) => void;
}) {
    const { shape, failed } = useTownShape(code);
    const { ref, nearViewport } = useNearViewport();
    const loading = (
        <Skeleton
            role="status"
            aria-label="Loading the map"
            className="size-full"
        />
    );
    const unavailable = (
        <p role="status" className="p-panel type-body">
            {NOT_AVAILABLE}
        </p>
    );

    return (
        <Surface
            title={name}
            className="order-first lg:sticky lg:top-4 lg:order-last"
        >
            <div ref={ref} className="h-64 lg:h-96">
                {shape && nearViewport ? (
                    <VisualErrorBoundary key={code} fallback={unavailable}>
                        <Suspense fallback={loading}>
                            <AreaMap
                                source={shape}
                                label={`Population by barangay in ${name}, ${year}`}
                                selected={focused}
                                describe={(place) => {
                                    const row = rows.find(
                                        (item) => item.place === place,
                                    );
                                    if (!row) return undefined;
                                    const count =
                                        row.value === null
                                            ? NOT_AVAILABLE
                                            : `${formatCount(row.value)} people`;
                                    return `${row.name}: ${count}`;
                                }}
                                onSelect={onFocus}
                            />
                        </Suspense>
                    </VisualErrorBoundary>
                ) : failed ? (
                    unavailable
                ) : (
                    loading
                )}
            </div>
        </Surface>
    );
}
