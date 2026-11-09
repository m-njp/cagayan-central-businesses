import { Disclosure } from '@/components/ui/disclosure';
import { ALL_TOWNS } from '@/lib/analytics';
import type { citationReports } from '@/lib/performance-format';

export function SourceLine({
    citations,
    town,
}: {
    citations: ReturnType<typeof citationReports>;
    town: string;
}) {
    if (town === ALL_TOWNS)
        return (
            <p className="flex flex-wrap gap-x-2 type-meta">
                <span>Source</span>COA Annual Audit Reports
            </p>
        );
    if (citations.length === 0) return null;

    return (
        <Disclosure title="COA Sources" className="border-t">
            <ul className="grid gap-2 px-2 pt-1 pb-2 type-body">
                {citations.map(({ year, reports }) => (
                    <li
                        key={year}
                        className="grid gap-x-3 sm:grid-cols-[auto_1fr]"
                    >
                        <span className="font-mono type-meta tabular-nums">
                            {year}
                        </span>
                        <ul className="grid gap-1">
                            {reports.map((report) => (
                                <li
                                    key={report.key}
                                    className="flex flex-wrap items-baseline gap-x-3 gap-y-1"
                                >
                                    <span>{report.label}</span>
                                    {report.links.map((link) => (
                                        <a
                                            key={link.text}
                                            href={link.href}
                                            target="_blank"
                                            rel="noreferrer"
                                            aria-label={`${report.label} ${year} ${link.text} (opens in a new tab)`}
                                            className="underline underline-offset-2"
                                        >
                                            {link.text}
                                        </a>
                                    ))}
                                </li>
                            ))}
                        </ul>
                    </li>
                ))}
            </ul>
        </Disclosure>
    );
}
