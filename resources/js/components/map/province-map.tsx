import { Minus, Plus, RotateCcw } from 'lucide-react';
import { icons } from '@/lib/icons';
import { useEffect, useMemo, useRef, useState } from 'react';
import type { ReactNode } from 'react';
import townsSource from '../../../../data/boundaries/cagayan-municipalities.geojson?raw';
import { Button } from '@/components/ui/button';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { DOT_FILL, DOT_RADIUS, dotsFor, seaPath } from '@/lib/halftone';
import type { Point } from '@/lib/halftone';
import { cn } from '@/lib/utils';

type Feature = {
    properties: { psgc_code: string; name: string };
    geometry:
        | { type: 'Polygon'; coordinates: Point[][] }
        | { type: 'MultiPolygon'; coordinates: Point[][][] };
};

const WIDTH = 400;

// Zoom limits: the whole map at 1, about a barangay cluster at 6.
const MAX_ZOOM = 6;

const clamp = (value: number, min: number, max: number) =>
    Math.min(Math.max(value, min), max);

type Sea = {
    // Land north of this latitude is islands (Babuyan, Fuga), so the coastline is read from the mainland only.
    islandsNorthOfLat: number;
    // The east coast faces the sea only north of this latitude. Further south the neighbor is land (Isabela).
    eastCoastUntilLat: number;
};

// Longitude is squeezed by the cosine of the middle latitude so the shapes keep their proportions.
function draw(source: string, sea?: Sea) {
    const features = (JSON.parse(source) as { features: Feature[] }).features;
    const rings = ({ geometry }: Feature): Point[][] =>
        geometry.type === 'Polygon'
            ? geometry.coordinates
            : geometry.coordinates.flat();

    const points = features.flatMap((f) => rings(f).flat());
    const lngs = points.map(([lng]) => lng);
    const lats = points.map(([, lat]) => lat);
    const [west, north] = [Math.min(...lngs), Math.max(...lats)];
    const south = Math.min(...lats);
    const squeeze = Math.cos((((south + north) / 2) * Math.PI) / 180);
    const scale = WIDTH / ((Math.max(...lngs) - west) * squeeze);
    const project = ([lng, lat]: Point): Point => [
        (lng - west) * squeeze * scale,
        (north - lat) * scale,
    ];

    const projected = features.map((f) =>
        rings(f).map((ring) => ring.map(project)),
    );
    const height = Math.round((north - south) * scale);
    const dots = dotsFor(projected, WIDTH, height);

    return {
        height,
        sea: sea
            ? seaPath(
                  dots,
                  WIDTH,
                  height,
                  (north - sea.islandsNorthOfLat) * scale,
                  (north - sea.eastCoastUntilLat) * scale,
              )
            : null,
        shapes: features.map((f, i) => ({
            code: f.properties.psgc_code,
            name: f.properties.name,
            path: projected[i]
                .map(
                    (ring) =>
                        `M${ring.map(([x, y]) => `${x.toFixed(1)} ${y.toFixed(1)}`).join('L')}Z`,
                )
                .join(''),
            dots: dots[i],
        })),
    };
}

// Cagayan's coast faces the sea to the north and, down to about Gonzaga, to the east.
const province = draw(townsSource, {
    islandsNorthOfLat: 18.7,
    eastCoastUntilLat: 18.2,
});

// A visited town keeps its projection and dot grid when the visitor switches back.
const preparedMaps = new Map<string, ReturnType<typeof draw>>();

function prepare(source: string) {
    const cached = preparedMaps.get(source);
    if (cached) return cached;
    const map = draw(source);
    preparedMaps.set(source, map);
    return map;
}

export type MapStyle = 'dotted' | 'solid';

export type ProvinceMapProps = {
    source?: string;
    mapStyle?: MapStyle;
    label: string;
    // Brass dots: the connected towns, or the town a visitor chose.
    highlight?: string[];
    // Shade 1 to 5 per code (metrics.md, M18): darker dots for more. Every dot is the same size.
    // Without it every place gets dots in the parent's text color.
    shades?: Record<string, number | null>;
    // Tooltip content per place. Defaults to the name.
    describe?: (code: string, name: string) => ReactNode;
    onSelect?: (code: string) => void;
    // Sea dots where the coast meets the sea, on the same grid (design.md, "Halftone").
    water?: boolean;
    // Zoom buttons, Ctrl or pinch with the wheel, and drag to pan. Plain scrolling still scrolls the page.
    zoomable?: boolean;
    // Adds a full screen button to the zoom controls (zoomable maps only).
    onExpand?: (trigger: HTMLButtonElement) => void;
    className?: string;
};

// Drawn from data/boundaries as a halftone (design.md, "Halftone"). Pages use LazyProvinceMap
// (frontend.md, "The map"). Never the only way to pick a place.
export default function ProvinceMap({
    source,
    mapStyle = 'dotted',
    label,
    highlight = [],
    shades,
    describe,
    onSelect,
    water = false,
    zoomable = false,
    onExpand,
    className,
}: ProvinceMapProps) {
    const animate = useRef(!source || !preparedMaps.has(source)).current;
    const {
        height,
        sea,
        shapes: list,
    } = useMemo(() => (source ? prepare(source) : province), [source]);
    const [hovered, setHovered] = useState<string | null>(null);
    const focus = hovered ?? (highlight.length === 1 ? highlight[0] : null);
    const svg = useRef<SVGSVGElement>(null);
    const drag = useRef<{
        x: number;
        y: number;
        cx: number;
        cy: number;
        moved: boolean;
    } | null>(null);
    // Set when a drag ends, so the click that follows does not pick a town.
    const dragged = useRef(false);
    const full = { w: WIDTH + 8, h: height + 8 };
    const [view, setView] = useState({ k: 1, cx: WIDTH / 2, cy: height / 2 });
    const box = {
        w: full.w / view.k,
        h: full.h / view.k,
    };
    const x = clamp(view.cx - box.w / 2, -4, -4 + full.w - box.w);
    const y = clamp(view.cy - box.h / 2, -4, -4 + full.h - box.h);

    // Keeps the point under the pointer (or the middle) still while the zoom changes.
    const zoom = (factor: number, at?: { x: number; y: number }) =>
        setView((current) => {
            const k = clamp(current.k * factor, 1, MAX_ZOOM);
            const middle = { x: x + box.w / 2, y: y + box.h / 2 };
            const anchor = at ?? middle;

            return {
                k,
                cx: anchor.x - (anchor.x - middle.x) * (current.k / k),
                cy: anchor.y - (anchor.y - middle.y) * (current.k / k),
            };
        });

    const toMap = (clientX: number, clientY: number) => {
        const matrix = svg.current?.getScreenCTM()?.inverse();

        if (!svg.current || !matrix) {
            return undefined;
        }

        const point = svg.current.createSVGPoint();
        point.x = clientX;
        point.y = clientY;
        const mapped = point.matrixTransform(matrix);

        return { x: mapped.x, y: mapped.y };
    };

    // React wheel listeners are passive, so preventDefault needs a native listener.
    useEffect(() => {
        const element = svg.current;

        if (!zoomable || !element) {
            return;
        }

        const onWheel = (event: WheelEvent) => {
            if (!event.ctrlKey && !event.metaKey) {
                return;
            }

            event.preventDefault();
            zoom(
                event.deltaY < 0 ? 1.25 : 0.8,
                toMap(event.clientX, event.clientY),
            );
        };

        element.addEventListener('wheel', onWheel, { passive: false });

        return () => element.removeEventListener('wheel', onWheel);
    });

    const map = (
        <svg
            ref={svg}
            viewBox={`${x} ${y} ${box.w} ${box.h}`}
            role={onSelect ? 'group' : 'img'}
            aria-label={label}
            className={cn(
                zoomable ? 'size-full touch-pan-y' : 'overflow-visible',
                zoomable && view.k > 1 && 'cursor-grab touch-none',
                !zoomable && className,
            )}
            onPointerLeave={() => {
                setHovered(null);
                drag.current = null;
            }}
            onPointerDown={(event) => {
                if (zoomable && view.k > 1) {
                    drag.current = {
                        x: event.clientX,
                        y: event.clientY,
                        cx: x + box.w / 2,
                        cy: y + box.h / 2,
                        moved: false,
                    };
                }
            }}
            onPointerMove={(event) => {
                const start = drag.current;
                const scale = svg.current?.getScreenCTM()?.a ?? 1;

                if (!start) {
                    return;
                }

                const dx = event.clientX - start.x;
                const dy = event.clientY - start.y;
                start.moved ||= Math.hypot(dx, dy) > 4;

                if (start.moved) {
                    setView((current) => ({
                        ...current,
                        cx: start.cx - dx / scale,
                        cy: start.cy - dy / scale,
                    }));
                }
            }}
            onPointerUp={() => {
                dragged.current = drag.current?.moved ?? false;
                drag.current = null;
            }}
        >
            {mapStyle === 'dotted' && water && sea && (
                <path d={sea} aria-hidden="true" className="fill-water/55" />
            )}
            {list.map((shape, index) => {
                const chosen = highlight.includes(shape.code);
                const shade = shades ? (shades[shape.code] ?? 0) : null;

                return (
                    <Tooltip key={shape.code}>
                        <g
                            style={{
                                animationDelay: `${(index * 700) / list.length}ms`,
                            }}
                            className={cn(
                                'origin-center transition-[opacity,scale] duration-(--duration-base) ease-out-soft [transform-box:fill-box] motion-reduce:transition-none',
                                animate && 'motion-safe:animate-fade',
                                focus && focus !== shape.code && 'opacity-45',
                                hovered === shape.code && 'scale-[1.04]',
                            )}
                        >
                            {mapStyle === 'dotted' &&
                                shape.dots.map(([x, y]) => (
                                    <circle
                                        key={`${x} ${y}`}
                                        cx={x}
                                        cy={y}
                                        r={DOT_RADIUS}
                                        className={
                                            chosen
                                                ? 'fill-highlight'
                                                : shade === null
                                                  ? 'fill-current/70'
                                                  : DOT_FILL[shade]
                                        }
                                    />
                                ))}
                            <TooltipTrigger asChild>
                                <path
                                    d={shape.path}
                                    fillRule="evenodd"
                                    role={onSelect ? 'button' : undefined}
                                    tabIndex={onSelect ? 0 : undefined}
                                    aria-label={
                                        onSelect ? shape.name : undefined
                                    }
                                    aria-pressed={onSelect ? chosen : undefined}
                                    onPointerEnter={() =>
                                        setHovered(shape.code)
                                    }
                                    onFocus={() => setHovered(shape.code)}
                                    onBlur={() => setHovered(null)}
                                    onClick={() => {
                                        if (dragged.current) {
                                            dragged.current = false;

                                            return;
                                        }

                                        onSelect?.(shape.code);
                                    }}
                                    onKeyDown={(event) => {
                                        if (
                                            onSelect &&
                                            (event.key === 'Enter' ||
                                                event.key === ' ')
                                        ) {
                                            event.preventDefault();
                                            onSelect(shape.code);
                                        }
                                    }}
                                    className={cn(
                                        // Borders always show, so every town or barangay reads as its own place.
                                        'stroke-current/45 stroke-1 transition-colors duration-(--duration-quick) outline-none [vector-effect:non-scaling-stroke]',
                                        mapStyle === 'dotted'
                                            ? 'fill-transparent'
                                            : chosen
                                              ? 'fill-highlight'
                                              : shade === null
                                                ? 'fill-current/70'
                                                : DOT_FILL[shade],
                                        mapStyle === 'solid' &&
                                            'stroke-background/80',
                                        (hovered === shape.code || chosen) &&
                                            'stroke-current stroke-2',
                                        'focus-visible:stroke-ring focus-visible:stroke-2',
                                        onSelect && 'cursor-pointer',
                                    )}
                                />
                            </TooltipTrigger>
                        </g>
                        <TooltipContent className="font-mono">
                            {describe
                                ? describe(shape.code, shape.name)
                                : shape.name}
                        </TooltipContent>
                    </Tooltip>
                );
            })}
        </svg>
    );

    if (!zoomable) {
        return map;
    }

    return (
        <div className={cn('relative', className)}>
            {map}
            <div className="absolute top-2 right-2 flex flex-col">
                {onExpand && (
                    <Button
                        variant="subtle"
                        size="touch"
                        aria-label="Full screen"
                        onClick={(event) => onExpand(event.currentTarget)}
                    >
                        <icons.fullscreen aria-hidden="true" />
                    </Button>
                )}
                <Button
                    variant="subtle"
                    size="touch"
                    aria-label="Zoom in"
                    disabled={view.k >= MAX_ZOOM}
                    onClick={() => zoom(1.5)}
                >
                    <Plus aria-hidden="true" />
                </Button>
                <Button
                    variant="subtle"
                    size="touch"
                    aria-label="Zoom out"
                    disabled={view.k <= 1}
                    onClick={() => zoom(1 / 1.5)}
                >
                    <Minus aria-hidden="true" />
                </Button>
                <Button
                    variant="subtle"
                    size="touch"
                    aria-label="Show the whole map"
                    disabled={view.k === 1}
                    onClick={() =>
                        setView({ k: 1, cx: WIDTH / 2, cy: height / 2 })
                    }
                >
                    <RotateCcw aria-hidden="true" />
                </Button>
            </div>
        </div>
    );
}
