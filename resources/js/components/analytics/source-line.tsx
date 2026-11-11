import { FileSpreadsheet, FileText } from 'lucide-react';
import { SourceLink } from '@/components/source-link';
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
            <FinanceReportList citations={citations} />
        </Disclosure>
    );
}

export function FinanceReportList({
    citations,
    showIcons = false,
}: {
    citations: ReturnType<typeof citationReports>;
    showIcons?: boolean;
}) {
    return (
        <ul className="grid gap-2 px-2 pt-1 pb-2 type-body">
            {citations.map(({ year, reports }) => (
                <li key={year} className="grid gap-x-3 sm:grid-cols-[auto_1fr]">
                    <span className="font-mono type-meta tabular-nums">
                        {year}
                    </span>
                    <ul className="grid gap-1">
                        {reports.map((report) => (
                            <li
                                key={report.key}
                                className="flex flex-wrap items-baseline gap-x-3 gap-y-1"
                            >
                                <span className="inline-flex items-center gap-2">
                                    {showIcons &&
                                        (report.links.some(
                                            (link) =>
                                                link.text ===
                                                'Download Workbook',
                                        ) ? (
                                            <FileSpreadsheet
                                                className="size-4 shrink-0"
                                                aria-hidden="true"
                                            />
                                        ) : (
                                            <FileText
                                                className="size-4 shrink-0"
                                                aria-hidden="true"
                                            />
                                        ))}
                                    {report.label}
                                </span>
                                {report.links.map((link) => (
                                    <SourceLink
                                        key={link.text}
                                        href={link.href}
                                        label={`${report.label} ${year} ${link.text}`}
                                    >
                                        {link.text}
                                    </SourceLink>
                                ))}
                            </li>
                        ))}
                    </ul>
                </li>
            ))}
        </ul>
    );
}
