import { lazy, Suspense } from 'react';
import {
    useInBrowser,
    VisualErrorBoundary,
} from '@/components/lazy-province-map';
import type { BusinessMapProps } from '@/components/map/business-map';
import { Skeleton } from '@/components/ui/skeleton';

const BusinessMap = lazy(() => import('@/components/map/business-map'));

// MapLibre loads only in the browser, after the page, with a skeleton of the same size.
export function LazyBusinessMap(props: BusinessMapProps) {
    const inBrowser = useInBrowser();
    const skeleton = <Skeleton className="size-full rounded-sm" />;

    if (!inBrowser) return skeleton;

    return (
        <VisualErrorBoundary
            fallback={
                <p
                    role="status"
                    className="flex h-full items-center justify-center border bg-muted p-4 type-body"
                >
                    The map could not load.
                </p>
            }
        >
            <Suspense fallback={skeleton}>
                <BusinessMap {...props} />
            </Suspense>
        </VisualErrorBoundary>
    );
}
