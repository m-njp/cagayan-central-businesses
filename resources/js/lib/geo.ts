import type { Geometry } from 'geojson';

type Ring = number[][];

// Even-odd ray casting; enough to suggest a barangay for a pin. The server checks with PostGIS.
function inRing([x, y]: [number, number], ring: Ring): boolean {
    let inside = false;
    for (let i = 0, j = ring.length - 1; i < ring.length; j = i++) {
        const [xi, yi] = ring[i];
        const [xj, yj] = ring[j];
        if (yi > y !== yj > y && x < ((xj - xi) * (y - yi)) / (yj - yi) + xi)
            inside = !inside;
    }
    return inside;
}

export function containsPoint(
    geometry: Geometry,
    point: [number, number],
): boolean {
    const polygons: Ring[][] =
        geometry.type === 'Polygon'
            ? [geometry.coordinates]
            : geometry.type === 'MultiPolygon'
              ? geometry.coordinates
              : [];
    return polygons.some(
        ([outer, ...holes]) =>
            inRing(point, outer) && !holes.some((hole) => inRing(point, hole)),
    );
}
