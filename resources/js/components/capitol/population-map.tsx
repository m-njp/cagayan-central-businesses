import { LazyProvinceMap } from '@/components/lazy-province-map';
import { Skeleton } from '@/components/ui/skeleton';
import { Surface } from '@/components/ui/surface';
import { useTownShape } from '@/hooks/use-town-shape';
import { formatCount, NOT_AVAILABLE } from '@/lib/format';
import type { PopulationRow } from '@/types/population';

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

    return (
        <Surface
            title={name}
            className="order-first lg:sticky lg:top-4 lg:order-last"
        >
            <div className="h-64 text-primary lg:h-96">
                {shape ? (
                    <LazyProvinceMap
                        key={code}
                        source={shape.raw}
                        label={`Population by barangay in ${name}, ${year}`}
                        mapStyle="solid"
                        zoomable
                        shades={Object.fromEntries(
                            shape.features.map(({ properties }) => [
                                properties.psgc_code,
                                properties.shade ?? null,
                            ]),
                        )}
                        highlight={focused ? [focused] : []}
                        describe={(place, barangay) => {
                            const value = rows.find(
                                (row) => row.place === place,
                            )?.value;
                            return (
                                <span>
                                    {barangay}:{' '}
                                    {value == null
                                        ? NOT_AVAILABLE
                                        : `${formatCount(value)} people`}
                                </span>
                            );
                        }}
                        onSelect={onFocus}
                        className="size-full"
                    />
                ) : failed ? (
                    <p role="status" className="p-panel type-body">
                        {NOT_AVAILABLE}
                    </p>
                ) : (
                    <Skeleton
                        role="status"
                        aria-label="Loading the map"
                        className="size-full"
                    />
                )}
            </div>
        </Surface>
    );
}
