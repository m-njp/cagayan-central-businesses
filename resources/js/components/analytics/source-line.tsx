import { ALL_TOWNS } from '@/lib/analytics';

/**
 * The one Source line of a panel: one underlined link per distinct report page, opening the stored COA report
 * (design.md, "Government Finances Ledger Table"). A Sum of Reporting Towns has one report per town, so it gets plain text.
 */
export function SourceLine({
    links,
    town,
}: {
    links: { href: string; text: string }[];
    town: string;
}) {
    const summed = town === ALL_TOWNS;
    if (!summed && links.length === 0) return null;
    return (
        <div className="flex flex-wrap items-baseline gap-x-3 gap-y-1 type-meta">
            <span>Source</span>
            {summed ? (
                <span>COA Annual Audit Reports</span>
            ) : (
                links.map((link) => (
                    <a
                        key={link.href}
                        href={link.href}
                        target="_blank"
                        rel="noreferrer"
                        className="underline underline-offset-2"
                    >
                        {link.text}
                        <span className="sr-only"> (opens in a new tab)</span>
                    </a>
                ))
            )}
        </div>
    );
}
