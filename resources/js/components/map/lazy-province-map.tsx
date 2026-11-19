import { lazy, Suspense } from 'react';
import { Skeleton } from '@/components/ui/skeleton';
import { useInBrowser } from '@/hooks/use-in-browser';
import type { ProvinceMapProps } from './province-map';

const ProvinceMap = lazy(() => import('./province-map'));

export function LazyProvinceMap(props: ProvinceMapProps) {
    const inBrowser = useInBrowser();
    const skeleton = (
        <Skeleton
            role="presentation"
            className="size-full rounded-2xl bg-current/10"
        />
    );

    if (!inBrowser) {
        return skeleton;
    }

    return (
        <Suspense fallback={skeleton}>
            <ProvinceMap {...props} />
        </Suspense>
    );
}
