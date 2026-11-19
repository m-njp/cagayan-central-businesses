import { lazy, Suspense } from 'react';
import { Skeleton } from '@/components/ui/skeleton';
import { VisualErrorBoundary } from '@/components/visual-error-boundary';
import { useNearViewport } from '@/hooks/use-near-viewport';
import type { BusinessMapProps } from './business-map';

const BusinessMap = lazy(() => import('./business-map'));

export function LazyBusinessMap(props: BusinessMapProps) {
    const { ref, nearViewport } = useNearViewport();
    const skeleton = <Skeleton className="size-full rounded-sm" />;

    return (
        <div ref={ref} className="size-full">
            {nearViewport ? (
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
            ) : (
                skeleton
            )}
        </div>
    );
}
