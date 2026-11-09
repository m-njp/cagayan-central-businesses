import { formatCount } from '@/lib/format';
import type { CommunityWishSnapshot } from '@/types/economic-performance';

// One compact row per wish: label, bar scaled to the most wished, count. Every wish is listed, longest first.
export default function WishRankingChart({
    snapshot,
}: {
    snapshot: CommunityWishSnapshot;
}) {
    const { items, townName } = snapshot;
    if (items.length === 0)
        return (
            <p role="status" className="py-4 type-body">
                No wishes are available for this town.
            </p>
        );
    const maximum = Math.max(1, ...items.map((item) => item.count));
    return (
        <ol
            className="grid max-h-72 gap-1 overflow-y-auto overscroll-contain pr-1"
            aria-label={`Business Wishes in ${townName}`}
        >
            {items.map((item) => (
                <li
                    key={item.id}
                    className="grid grid-cols-[minmax(0,1fr)_auto] items-center gap-x-2 gap-y-0.5"
                >
                    <span
                        className="truncate type-meta text-foreground"
                        title={item.label}
                    >
                        {item.label}
                    </span>
                    <span className="font-mono type-data tabular-nums">
                        {formatCount(item.count)}
                    </span>
                    <span
                        className="col-span-2 h-1.5 rounded-sm bg-muted"
                        aria-hidden="true"
                    >
                        <span
                            className="block h-full rounded-sm bg-chart-1"
                            style={{
                                width: `${(item.count / maximum) * 100}%`,
                            }}
                        />
                    </span>
                </li>
            ))}
        </ol>
    );
}
