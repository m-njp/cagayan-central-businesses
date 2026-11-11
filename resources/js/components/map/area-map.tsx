import Map, {
    Layer,
    NavigationControl,
    Popup,
    Source,
} from 'react-map-gl/maplibre';
import type { MapRef, MapLayerMouseEvent } from 'react-map-gl/maplibre';
import type { StyleSpecification } from 'maplibre-gl';
import { memo, useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { useAppearance } from '@/hooks/use-appearance';
import { collectionBounds } from '@/lib/map-data';
import type { AreaCollection } from '@/lib/map-data';
import { mapColor } from '@/lib/map-color';

import maplibregl from './maplibre';

// Leave room for mandatory attribution when fitting the full province and its islands.
const MAP_PADDING = { top: 24, right: 24, bottom: 76, left: 24 };
const BASEMAP = 'https://tiles.openfreemap.org/styles/positron';

export default memo(function AreaMap({
    source,
    selected,
    onSelect,
    label,
    describe,
}: {
    source: AreaCollection;
    selected: string | null;
    onSelect: (code: string) => void;
    label: string;
    describe?: (code: string) => string | undefined;
}) {
    const ref = useRef<MapRef>(null);
    const previous = useRef<string | null>(null);
    const { resolvedAppearance } = useAppearance();
    const [plain, setPlain] = useState(false);
    const [ready, setReady] = useState(false);
    const [fatal, setFatal] = useState(false);
    const [hovered, setHovered] = useState<{
        code: string;
        longitude: number;
        latitude: number;
    } | null>(null);
    const colors = useMemo(
        () => ({
            background: mapColor('--muted', resolvedAppearance),
            border: mapColor('--foreground', resolvedAppearance),
            selection: mapColor('--highlight', resolvedAppearance),
            none: mapColor('--map-none', resolvedAppearance),
            shades: [1, 2, 3, 4, 5].map((n) =>
                mapColor('--map-' + n, resolvedAppearance),
            ),
        }),
        [resolvedAppearance],
    );
    const fallback = useMemo<StyleSpecification>(
        () => ({
            version: 8,
            sources: {},
            layers: [
                {
                    id: 'background',
                    type: 'background',
                    paint: { 'background-color': colors.background },
                },
            ],
        }),
        [colors],
    );
    const [[west, south], [east, north]] = useMemo(
        () => collectionBounds(source),
        [source],
    );
    const bounds = useMemo<[[number, number], [number, number]]>(
        () => [
            [west, south],
            [east, north],
        ],
        [west, south, east, north],
    );
    const selectFeature = useCallback(() => {
        const map = ref.current?.getMap();
        if (!map?.getSource('areas')) return;
        if (previous.current)
            map.setFeatureState(
                { source: 'areas', id: previous.current },
                { selected: false },
            );
        if (selected)
            map.setFeatureState(
                { source: 'areas', id: selected },
                { selected: true },
            );
        previous.current = selected;
    }, [selected]);
    useEffect(() => {
        if (ready) selectFeature();
    }, [ready, selectFeature]);
    useEffect(() => {
        if (ready)
            ref.current?.fitBounds(bounds, {
                padding: MAP_PADDING,
                duration: 0,
            });
    }, [bounds, ready]);
    useEffect(() => {
        if (!ready) return;
        const map = ref.current?.getMap();
        const lost = () => setFatal(true);
        map?.on('webglcontextlost', lost);
        return () => {
            map?.off('webglcontextlost', lost);
        };
    }, [ready]);
    const click = (event: MapLayerMouseEvent) => {
        const code = event.features?.[0]?.properties?.psgc_code;
        if (typeof code === 'string') onSelect(code);
    };
    const hover = (event: MapLayerMouseEvent) => {
        const code = event.features?.[0]?.properties?.psgc_code;
        setHovered(
            typeof code === 'string'
                ? {
                      code,
                      longitude: event.lngLat.lng,
                      latitude: event.lngLat.lat,
                  }
                : null,
        );
    };
    const description = hovered && describe?.(hovered.code);

    if (fatal)
        return (
            <p
                role="status"
                className="flex h-full items-center justify-center border bg-muted p-4 type-body"
            >
                The map renderer is unavailable. Use the area selectors and data
                panel.
            </p>
        );

    return (
        <div
            className="relative h-full overflow-hidden rounded-sm border bg-card"
            role="region"
            aria-label={label}
        >
            <Map
                ref={ref}
                mapLib={maplibregl}
                initialViewState={{
                    bounds,
                    fitBoundsOptions: { padding: MAP_PADDING },
                }}
                mapStyle={plain ? fallback : BASEMAP}
                interactiveLayerIds={['area-fill']}
                onLoad={() => {
                    setReady(true);
                    selectFeature();
                }}
                onSourceData={selectFeature}
                onResize={() =>
                    ref.current?.fitBounds(bounds, {
                        padding: MAP_PADDING,
                        duration: 0,
                    })
                }
                onClick={click}
                onMouseMove={describe ? hover : undefined}
                onMouseOut={describe ? () => setHovered(null) : undefined}
                cursor={hovered ? 'pointer' : undefined}
                onError={(event) => {
                    if (
                        /webgl|context lost|context creation|gpu|worker failed/i.test(
                            event.error.message,
                        )
                    )
                        setFatal(true);
                    else setPlain(true);
                }}
                dragRotate={false}
                touchPitch={false}
                cooperativeGestures
                attributionControl={{ compact: true }}
                style={{ width: '100%', height: '100%' }}
            >
                <Source
                    id="areas"
                    type="geojson"
                    data={source}
                    promoteId="psgc_code"
                    attribution="NAMRIA/PSA via philippines-json-maps (MIT)"
                >
                    <Layer
                        id="area-fill"
                        type="fill"
                        paint={{
                            'fill-color': [
                                'case',
                                [
                                    'boolean',
                                    ['feature-state', 'selected'],
                                    false,
                                ],
                                colors.selection,
                                [
                                    'match',
                                    ['get', 'shade'],
                                    1,
                                    colors.shades[0],
                                    2,
                                    colors.shades[1],
                                    3,
                                    colors.shades[2],
                                    4,
                                    colors.shades[3],
                                    5,
                                    colors.shades[4],
                                    colors.none,
                                ],
                            ],
                            'fill-opacity': 0.75,
                        }}
                    />
                    <Layer
                        id="area-line"
                        type="line"
                        paint={{
                            'line-color': colors.border,
                            'line-width': [
                                'case',
                                [
                                    'boolean',
                                    ['feature-state', 'selected'],
                                    false,
                                ],
                                2,
                                0.6,
                            ],
                            'line-opacity': 0.65,
                        }}
                    />
                </Source>
                {hovered && description && (
                    <Popup
                        longitude={hovered.longitude}
                        latitude={hovered.latitude}
                        closeButton={false}
                        closeOnClick={false}
                        focusAfterOpen={false}
                        offset={8}
                        className="pointer-events-none [&_.maplibregl-popup-content]:bg-popover [&_.maplibregl-popup-content]:text-popover-foreground"
                    >
                        {description}
                    </Popup>
                )}
                <NavigationControl position="top-right" showCompass={false} />
            </Map>
            {plain && (
                <p className="absolute bottom-8 left-2 max-w-measure rounded-sm bg-background p-2 type-meta">
                    Basemap unavailable. Local boundaries remain available.
                </p>
            )}
        </div>
    );
});
