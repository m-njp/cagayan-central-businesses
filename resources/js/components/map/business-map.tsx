import Map, {
    Layer,
    Marker,
    NavigationControl,
    Source,
} from 'react-map-gl/maplibre';
import type { MapLayerMouseEvent, MapRef } from 'react-map-gl/maplibre';
import * as maplibregl from 'maplibre-gl';
import type { ExpressionSpecification, StyleSpecification } from 'maplibre-gl';
import workerUrl from 'maplibre-gl/dist/maplibre-gl-worker.mjs?worker&url';
import type { FeatureCollection, Point as GeoPoint } from 'geojson';
import { useEffect, useMemo, useRef, useState } from 'react';
import 'maplibre-gl/dist/maplibre-gl.css';
import { useAppearance } from '@/hooks/use-appearance';
import { categoryIcon, icons } from '@/lib/icons';
import { mapColor } from '@/lib/map-color';
import { collectionBounds, loadTownBoundaries } from '@/lib/map-data';
import type { AreaCollection } from '@/lib/map-data';

maplibregl.setWorkerUrl(workerUrl);

const BASEMAP = 'https://tiles.openfreemap.org/styles/positron';
const PADDING = 32;

export type Point = { lng: number; lat: number };
export type MapBusiness = { id: string; point: [number, number] | null };

export type BusinessMapProps = {
    townCode: string;
    barangay: string | null;
    label: string;
    // Storefront dots; home-based businesses never carry a point (privacy-rules.md, "Field classes").
    businesses?: MapBusiness[];
    highlight?: string | null;
    // The one business this map is about, drawn as a tag with its category icon.
    pin?: { point: Point | null; category?: string };
    onSelect?: (id: string) => void;
    // With it, a click or a drag moves the pin (the registry form).
    onPick?: (point: Point) => void;
};

// One map for the directory, both profiles and the form: dashed barangay outlines; the chosen barangay framed with a
// solid outline and named, the rest of the town dimmed.
export default function BusinessMap({
    townCode,
    barangay,
    label,
    businesses = [],
    highlight = null,
    pin,
    onSelect,
    onPick,
}: BusinessMapProps) {
    const ref = useRef<MapRef>(null);
    const [areas, setAreas] = useState<AreaCollection | null>(null);
    const { resolvedAppearance } = useAppearance();
    const [plain, setPlain] = useState(false);
    const [fatal, setFatal] = useState(false);
    const PinIcon = pin?.category ? categoryIcon(pin.category) : icons.location;
    const colors = useMemo(
        () => ({
            background: mapColor('--muted', resolvedAppearance),
            dot: mapColor('--primary', resolvedAppearance),
            selected: mapColor('--highlight', resolvedAppearance),
            ring: mapColor('--background', resolvedAppearance),
            line: mapColor('--muted-foreground', resolvedAppearance),
            chosen: mapColor('--primary', resolvedAppearance),
            fill: mapColor('--highlight', resolvedAppearance),
            dim: mapColor('--background', resolvedAppearance),
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
    const points = useMemo<FeatureCollection<GeoPoint, { id: string }>>(
        () => ({
            type: 'FeatureCollection',
            features: businesses.flatMap((business) =>
                business.point
                    ? [
                          {
                              type: 'Feature',
                              geometry: {
                                  type: 'Point',
                                  coordinates: business.point,
                              },
                              properties: { id: business.id },
                          },
                      ]
                    : [],
            ),
        }),
        [businesses],
    );

    useEffect(() => {
        let current = true;
        loadTownBoundaries(townCode)
            .then((collection) => current && setAreas(collection))
            .catch(() => current && setAreas(null));
        return () => {
            current = false;
        };
    }, [townCode]);

    const framed = useMemo<AreaCollection | null>(() => {
        if (!areas) return null;
        const features = barangay
            ? areas.features.filter((f) => f.properties.psgc_code === barangay)
            : areas.features;
        return features.length ? { ...areas, features } : null;
    }, [areas, barangay]);

    useEffect(() => {
        if (framed)
            ref.current?.fitBounds(collectionBounds(framed), {
                padding: PADDING,
                duration: 400,
            });
    }, [framed]);

    if (fatal)
        return (
            <p
                role="status"
                className="flex h-full items-center justify-center border bg-muted p-4 type-body"
            >
                The map renderer is unavailable.
            </p>
        );

    const click = (event: MapLayerMouseEvent) => {
        const id = event.features?.[0]?.properties?.id;
        if (typeof id === 'string') onSelect?.(id);
        else onPick?.({ lng: event.lngLat.lng, lat: event.lngLat.lat });
    };
    const chosen: ExpressionSpecification = [
        '==',
        ['get', 'psgc_code'],
        barangay ?? '',
    ];
    const marked: ExpressionSpecification = [
        '==',
        ['get', 'id'],
        highlight ?? '',
    ];

    return (
        <div
            className="relative h-full overflow-hidden rounded-sm border"
            role="region"
            aria-label={label}
        >
            <Map
                ref={ref}
                mapLib={maplibregl}
                initialViewState={
                    pin?.point
                        ? {
                              longitude: pin.point.lng,
                              latitude: pin.point.lat,
                              zoom: 15,
                          }
                        : { longitude: 121.727, latitude: 17.62, zoom: 12.3 }
                }
                mapStyle={plain ? fallback : BASEMAP}
                interactiveLayerIds={onSelect ? ['business-dots'] : []}
                onClick={click}
                onError={(event) => {
                    if (
                        /webgl|context lost|context creation|gpu|worker failed/i.test(
                            event.error.message,
                        )
                    )
                        setFatal(true);
                    else setPlain(true);
                }}
                cursor={onPick ? 'crosshair' : onSelect ? 'pointer' : undefined}
                dragRotate={false}
                touchPitch={false}
                cooperativeGestures
                attributionControl={{ compact: true }}
                style={{ width: '100%', height: '100%' }}
            >
                {areas && (
                    <Source
                        id="barangays"
                        type="geojson"
                        data={areas}
                        attribution="NAMRIA/PSA via philippines-json-maps (MIT)"
                    >
                        <Layer
                            id="barangay-fill"
                            type="fill"
                            paint={{
                                'fill-color': [
                                    'case',
                                    chosen,
                                    colors.fill,
                                    colors.dim,
                                ],
                                'fill-opacity': barangay
                                    ? ['case', chosen, 0.12, 0.55]
                                    : 0,
                            }}
                        />
                        <Layer
                            id="barangay-lines"
                            type="line"
                            paint={{
                                'line-color': colors.line,
                                'line-width': 0.8,
                                'line-dasharray': [3, 2],
                                'line-opacity': 0.7,
                            }}
                        />
                        <Layer
                            id="barangay-chosen"
                            type="line"
                            filter={chosen}
                            paint={{
                                'line-color': colors.chosen,
                                'line-width': 2.5,
                            }}
                        />
                    </Source>
                )}
                <Source id="businesses" type="geojson" data={points}>
                    <Layer
                        id="business-dots"
                        type="circle"
                        paint={{
                            'circle-radius': ['case', marked, 8, 4.5],
                            'circle-color': [
                                'case',
                                marked,
                                colors.selected,
                                colors.dot,
                            ],
                            'circle-stroke-color': colors.ring,
                            'circle-stroke-width': 1.5,
                        }}
                    />
                </Source>
                {pin?.point && (
                    <Marker
                        longitude={pin.point.lng}
                        latitude={pin.point.lat}
                        anchor="bottom"
                        draggable={!!onPick}
                        onDragEnd={(event) =>
                            onPick?.({
                                lng: event.lngLat.lng,
                                lat: event.lngLat.lat,
                            })
                        }
                    >
                        {/* A bordered tag with a pointer, as design.md "Map panel" describes pins. */}
                        <span className="flex flex-col items-center">
                            <span className="flex size-9 items-center justify-center rounded-sm border-2 border-background bg-primary text-primary-foreground shadow-md">
                                <PinIcon
                                    className="size-5"
                                    aria-hidden="true"
                                />
                            </span>
                            <span className="-mt-1 size-2.5 rotate-45 border-r-2 border-b-2 border-background bg-primary" />
                        </span>
                    </Marker>
                )}
                <NavigationControl position="top-right" showCompass={false} />
            </Map>
            {framed && barangay && (
                <p className="pointer-events-none absolute top-2 left-2 rounded-sm border bg-background px-2 py-1 type-meta font-medium text-foreground">
                    {framed.features[0].properties.name}
                </p>
            )}
            {plain && (
                <p className="absolute bottom-8 left-2 max-w-measure rounded-sm bg-background p-2 type-meta">
                    Basemap unavailable. Local boundaries remain available.
                </p>
            )}
        </div>
    );
}
