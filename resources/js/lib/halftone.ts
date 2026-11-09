// The halftone dot grid behind every map (design.md, "Halftone").

export type Point = [number, number];

// Distance between dot centers, in map units (the map is 400 units wide).
export const DOT_STEP = 6;

// Every dot is the same size. Color carries the value (DOT_FILL).
export const DOT_RADIUS = 2.2;

// Sea dots are smaller and quieter than land dots, so the land always reads first.
export const WATER_RADIUS = 1.2;

// Even-odd rule, so holes in a shape stay empty.
function inside([x, y]: Point, rings: Point[][]): boolean {
    let hit = false;

    for (const ring of rings) {
        for (let i = 0, j = ring.length - 1; i < ring.length; j = i++) {
            const [xi, yi] = ring[i];
            const [xj, yj] = ring[j];

            if (
                yi > y !== yj > y &&
                x < ((xj - xi) * (y - yi)) / (yj - yi) + xi
            ) {
                hit = !hit;
            }
        }
    }

    return hit;
}

// One shared grid for all shapes, so the dots line up across borders like a printed halftone.
// A shape too small to hold a grid point gets one dot at its center, so every place stays visible.
export function dotsFor(shapes: Point[][][], width: number, height: number) {
    const boxes = shapes.map((rings) => {
        const points = rings.flat();
        const xs = points.map(([x]) => x);
        const ys = points.map(([, y]) => y);

        return [
            Math.min(...xs),
            Math.min(...ys),
            Math.max(...xs),
            Math.max(...ys),
        ];
    });
    const dots: Point[][] = shapes.map(() => []);

    for (let y = DOT_STEP / 2; y < height; y += DOT_STEP) {
        for (let x = DOT_STEP / 2; x < width; x += DOT_STEP) {
            const index = shapes.findIndex((rings, i) => {
                const [left, top, right, bottom] = boxes[i];

                return (
                    x >= left &&
                    x <= right &&
                    y >= top &&
                    y <= bottom &&
                    inside([x, y], rings)
                );
            });

            if (index !== -1) {
                dots[index].push([x, y]);
            }
        }
    }

    return dots.map((list, i) => {
        if (list.length > 0) {
            return list;
        }

        const [left, top, right, bottom] = boxes[i];

        return [[(left + right) / 2, (top + bottom) / 2] as Point];
    });
}

// Dot color by shade 1 to 5, darker for more. Index 0 is "no value". Full class names so Tailwind finds them.
export const DOT_FILL = [
    'fill-map-none',
    'fill-map-1',
    'fill-map-2',
    'fill-map-3',
    'fill-map-4',
    'fill-map-5',
] as const;

// How far the sea reaches from any coast, in grid steps. A band, not a box, so the sea hugs the land.
const SEA_REACH = 4;

// Sea dots as one path, so a few thousand dots cost one element. A grid point is sea when it is not
// land, lies within SEA_REACH of land, and is either north of the mainland coast in its column or
// east of the mainland in its row above eastLimitY. Anything else outside the shapes is a
// neighboring province and stays empty. Dots above islandLimitY are islands, not mainland.
export function seaPath(
    dots: Point[][],
    width: number,
    height: number,
    islandLimitY: number,
    eastLimitY: number,
): string {
    const all = dots.flat();
    const key = (x: number, y: number) =>
        `${Math.round(x / DOT_STEP)} ${Math.round(y / DOT_STEP)}`;
    const land = new Set(all.map(([x, y]) => key(x, y)));
    const coastTop = new Map<number, number>();
    const coastEast = new Map<number, number>();

    for (const [x, y] of all) {
        if (y > islandLimitY) {
            coastTop.set(x, Math.min(coastTop.get(x) ?? Infinity, y));
            coastEast.set(y, Math.max(coastEast.get(y) ?? -Infinity, x));
        }
    }

    const nearLand = (x: number, y: number) => {
        for (let dy = -SEA_REACH; dy <= SEA_REACH; dy++) {
            for (let dx = -SEA_REACH; dx <= SEA_REACH; dx++) {
                if (
                    dx * dx + dy * dy <= SEA_REACH * SEA_REACH &&
                    land.has(key(x + dx * DOT_STEP, y + dy * DOT_STEP))
                ) {
                    return true;
                }
            }
        }

        return false;
    };

    const r = WATER_RADIUS;
    let path = '';

    for (let y = DOT_STEP / 2; y < height; y += DOT_STEP) {
        for (let x = DOT_STEP / 2; x < width; x += DOT_STEP) {
            const north = y < (coastTop.get(x) ?? eastLimitY);
            const east = y < eastLimitY && x > (coastEast.get(y) ?? Infinity);

            if (!land.has(key(x, y)) && (north || east) && nearLand(x, y)) {
                path += `M${(x - r).toFixed(1)} ${y.toFixed(1)}a${r} ${r} 0 1 0 ${2 * r} 0a${r} ${r} 0 1 0 ${-2 * r} 0`;
            }
        }
    }

    return path;
}
