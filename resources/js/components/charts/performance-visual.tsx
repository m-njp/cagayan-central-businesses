import { Suspense } from 'react';
import type { ReactNode } from 'react';
import { Button } from '@/components/ui/button';
import { Skeleton } from '@/components/ui/skeleton';
import { VisualErrorBoundary } from '@/components/visual-error-boundary';
import { useNearViewport } from '@/hooks/use-near-viewport';

export default function PerformanceVisual({
    children,
}: {
    children: ReactNode;
}) {
    const { ref, nearViewport } = useNearViewport();
    const loading = (
        <Skeleton
            className="h-72 w-full motion-reduce:animate-none"
            aria-label="Loading visualization"
        />
    );

    return (
        <div ref={ref} className="min-w-0">
            {nearViewport ? (
                <VisualErrorBoundary
                    fallback={
                        <div
                            role="alert"
                            className="flex min-h-64 flex-col items-center justify-center gap-4 p-6"
                        >
                            <p className="type-body">
                                Unable to display the visualization.
                            </p>
                            <Button
                                variant="outline"
                                onClick={() => window.location.reload()}
                            >
                                Retry
                            </Button>
                        </div>
                    }
                >
                    <Suspense fallback={loading}>{children}</Suspense>
                </VisualErrorBoundary>
            ) : (
                loading
            )}
        </div>
    );
}
