import { useWordcloud } from '@visx/wordcloud';
import { useEffect, useMemo, useRef, useState } from 'react';
import WishRankingChart from '@/components/charts/wish-ranking-chart';
import { WishTooltip } from '@/components/charts/wish-tooltip';
import { Skeleton } from '@/components/ui/skeleton';
import { formatCount } from '@/lib/format';
import type {
    CommunityWishSnapshot,
    WishAggregate,
} from '@/types/economic-performance';

const MIN_SIZE = 12;
// A fixed generator keeps the same layout on every visit.
const fixedRandom = () => 0.5;

type Word = { text: string; value: number; id: string };

function Cloud({
    items,
    townName,
    width,
}: {
    items: WishAggregate[];
    townName: string;
    width: number;
}) {
    const height = width < 480 ? 340 : 300;
    // Lowered after a layout that could not place every word, so no wish is ever dropped.
    const [shrink, setShrink] = useState(1);
    const words = useMemo<Word[]>(
        () =>
            items.map((item) => ({
                text: item.label,
                value: item.count,
                id: item.id,
            })),
        [items],
    );
    // Log scale, as visx scaleLog: one large count cannot flatten the rest.
    const level = useMemo(() => {
        const logs = items.map((item) => Math.log(Math.max(item.count, 1)));
        const low = Math.min(...logs);
        const high = Math.max(...logs);
        return (value: number) =>
            high === low
                ? 0.5
                : (Math.log(Math.max(value, 1)) - low) / (high - low);
    }, [items]);
    const fontSize = useMemo(() => {
        const max = Math.min(56, Math.max(24, width / 12)) * shrink;
        return (word: Word) =>
            MIN_SIZE + level(word.value) * Math.max(0, max - MIN_SIZE);
    }, [level, width, shrink]);
    const placed = useWordcloud<Word>({
        words,
        width,
        height,
        font: 'Geist',
        fontSize,
        fontWeight: 600,
        padding: 3,
        rotate: 0,
        spiral: 'archimedean',
        random: fixedRandom,
    });
    const initialLayout = useRef(placed);
    const complete = placed.length === words.length;
    const cannotFit = shrink < 0.4;
    useEffect(() => {
        if (placed !== initialLayout.current && !complete && !cannotFit)
            setShrink((value) => value * 0.85);
    }, [placed, complete, cannotFit]);
    const byId = useMemo(
        () => new Map(items.map((item) => [item.id, item])),
        [items],
    );

    if (!complete && cannotFit)
        return <WishRankingChart snapshot={{ items, townName }} />;

    if (!complete)
        return (
            <Skeleton
                className="motion-reduce:animate-none"
                style={{ height }}
                aria-label="Arranging business wishes"
            />
        );

    return (
        <svg
            viewBox={`0 0 ${width} ${height}`}
            className="w-full select-none"
            role="group"
            aria-label={`Business wishes word cloud in ${townName}`}
        >
            <g transform={`translate(${width / 2}, ${height / 2})`}>
                {placed.map((word) => {
                    const item = byId.get((word as typeof word & Word).id);
                    if (!item) return null;
                    const tier = level(item.count);
                    return (
                        <WishTooltip
                            key={item.id}
                            item={item}
                            townName={townName}
                        >
                            <text
                                transform={`translate(${word.x}, ${word.y})`}
                                textAnchor="middle"
                                fontFamily={word.font}
                                fontSize={word.size}
                                fontWeight={600}
                                fill={
                                    tier >= 0.66
                                        ? 'var(--primary)'
                                        : tier >= 0.33
                                          ? 'var(--chart-2)'
                                          : 'var(--muted-foreground)'
                                }
                                tabIndex={0}
                                role="img"
                                aria-label={`${item.label}: ${formatCount(item.count)} wishes in ${townName}`}
                                className="cursor-pointer outline-none hover:opacity-75 focus:underline"
                            >
                                {item.label}
                            </text>
                        </WishTooltip>
                    );
                })}
            </g>
        </svg>
    );
}

/** Every curated wish in a visx word cloud, sized by count; hover, focus or tap shows the count for the town. */
export default function WishCloud({
    snapshot,
}: {
    snapshot: Pick<CommunityWishSnapshot, 'items' | 'townName'>;
}) {
    const container = useRef<HTMLDivElement>(null);
    const [width, setWidth] = useState(0);
    const [fontsReady, setFontsReady] = useState(false);
    useEffect(() => {
        let active = true;
        void document.fonts.ready.then(() => active && setFontsReady(true));
        const element = container.current;
        if (!element) return;
        // Small width changes keep the current layout; a real resize lays it out again.
        const observer = new ResizeObserver(([entry]) => {
            const next = Math.floor(entry.contentRect.width);
            setWidth((current) =>
                current === 0 || Math.abs(next - current) >= 24
                    ? next
                    : current,
            );
        });
        observer.observe(element);
        return () => {
            active = false;
            observer.disconnect();
        };
    }, []);

    if (snapshot.items.length === 0)
        return (
            <p role="status" className="py-4 type-body">
                No wishes are available for this town.
            </p>
        );

    return (
        <div ref={container} className="min-w-0">
            {fontsReady && width > 0 ? (
                <Cloud
                    key={width}
                    items={snapshot.items}
                    townName={snapshot.townName}
                    width={width}
                />
            ) : (
                <Skeleton
                    className="h-72 motion-reduce:animate-none"
                    aria-label="Loading word cloud"
                />
            )}
        </div>
    );
}
