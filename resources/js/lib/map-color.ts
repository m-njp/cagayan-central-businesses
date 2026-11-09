import type { ResolvedAppearance } from '@/hooks/use-appearance';

const colors = new Map<string, string>();

export function mapColor(
    token: string,
    appearance: ResolvedAppearance,
): string {
    const key = `${appearance}:${token}`;
    const cached = colors.get(key);
    if (cached) return cached;
    const canvas = document.createElement('canvas');
    canvas.width = canvas.height = 1;
    const context = canvas.getContext('2d', { willReadFrequently: true });
    if (!context) throw new Error('Color conversion unavailable');
    context.fillStyle = getComputedStyle(document.documentElement)
        .getPropertyValue(token)
        .trim();
    context.fillRect(0, 0, 1, 1);
    const [r, g, b, a] = context.getImageData(0, 0, 1, 1).data;
    const color = 'rgba(' + r + ',' + g + ',' + b + ',' + a / 255 + ')';
    colors.set(key, color);
    return color;
}
