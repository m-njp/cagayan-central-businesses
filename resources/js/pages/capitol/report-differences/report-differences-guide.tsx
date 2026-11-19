import { useState } from 'react';
import {
    GUIDE_TARGET,
    GuideDialog,
    GuideSection,
} from '@/components/guide-dialog';
import type { GuideTab } from '@/components/guide-dialog';
import { Surface } from '@/components/ui/surface';
import { cn } from '@/lib/utils';
import type { ReportDifferenceFamily } from '@/types/finance';
import { FAMILIES, FINDINGS, findingEntry } from './finding-guide';

const FAMILY_ORDER: ReportDifferenceFamily[] = [
    'report',
    'between_reports',
    'reading',
];

const OVERVIEW: GuideTab[] = [
    {
        id: 'page',
        title: 'What This Page Shows',
        content: (
            <GuideSection title="What This Page Shows">
                <p>
                    Each municipality's finances come from its Annual Audit
                    Report, published by the Commission on Audit. The system
                    reads its Executive Summary (PDF), which shows the report
                    year and, for comparison, the year before. It checks the
                    amounts and lists anything a reviewer should know before the
                    figures are relied on.
                </p>
                <p>
                    A finding concerns how an amount is printed or read. It is
                    not an audit finding and does not indicate how public funds
                    were used.
                </p>
            </GuideSection>
        ),
    },
    {
        id: 'table',
        title: 'Reading the Table',
        content: (
            <GuideSection title="Reading the Table">
                <ul className="flex list-disc flex-col gap-1 pl-5">
                    <li>
                        Choose a report year in Filters. A 2017 report also
                        shows 2016 for comparison.
                    </li>
                    <li>
                        Each row is a municipality, with its findings in each
                        category and their total.
                    </li>
                    <li>
                        Review reads Verified when every figure from the report
                        is signed off, and Pending when some still wait for a
                        reviewer.
                    </li>
                    <li>
                        Not available means the report has not been collected or
                        was not published.
                    </li>
                    <li>
                        Select a row to see each finding with a link to the page
                        of the report it concerns. Select a finding's name to
                        open its explanation here.
                    </li>
                </ul>
            </GuideSection>
        ),
    },
    {
        id: 'sign-off',
        title: 'How Figures Are Signed Off',
        content: (
            <GuideSection title="How Figures Are Signed Off">
                <p>
                    A figure reaches the maps and Analytics only once it is
                    signed off. It is signed off automatically when two Annual
                    Audit Reports of the same kind, the year&rsquo;s own report
                    and the next year&rsquo;s comparison column, show the same
                    amount or amounts no more than ₱1.00 apart, and no finding
                    holds that line. The newest year has no later report yet, so
                    a figure from a government&rsquo;s latest report is shown
                    when no finding holds it. Every other figure waits for a
                    reviewer, who must be someone other than the person who
                    entered it.
                </p>
                <p>
                    Every difference is listed, even one centavo. A difference
                    of ₱1.00 or less does not hold sign-off; a larger one, the
                    same amount under two governments, or a change of more than
                    three times always waits for a reviewer. When a later report
                    restates a year, the later report prevails and the earlier
                    amount is kept as history. Two reports agreeing does not
                    prove an amount correct if the same mistake was printed in
                    both.
                </p>
            </GuideSection>
        ),
    },
];

function findingAnchor(kind: string): string {
    return `finding-${kind}`;
}

/**
 * The guide to Report Differences in the shared tabbed guide overlay: three overview tabs, then one tab per category
 * with its findings. Opening it on a finding selects that category, scrolls to the entry and marks it.
 */
export function ReportDifferencesGuide({
    open,
    focus,
    onOpenChange,
}: {
    open: boolean;
    focus: string | null;
    onOpenChange: (open: boolean) => void;
}) {
    const [tab, setTab] = useState(OVERVIEW[0].id);
    // Each opening starts at the finding that opened it, or at the first tab.
    const opening = open ? (focus ?? '') : null;
    const [seen, setSeen] = useState<string | null>(null);
    if (opening !== seen) {
        setSeen(opening);
        if (opening !== null)
            setTab(findingEntry(opening)?.family ?? OVERVIEW[0].id);
    }

    const tabs: GuideTab[] = [
        ...OVERVIEW,
        ...FAMILY_ORDER.map((family) => ({
            id: family,
            title: FAMILIES[family].label,
            content: (
                <GuideSection title={FAMILIES[family].label}>
                    <p>{FAMILIES[family].meaning}</p>
                    <p className="text-muted-foreground">
                        {FAMILIES[family].responsibility}
                    </p>
                    {FINDINGS.filter((entry) => entry.family === family).map(
                        (entry) => (
                            <Surface
                                key={entry.kind}
                                id={findingAnchor(entry.kind)}
                                padded
                                className={cn(
                                    'scroll-mt-2',
                                    entry.kind === focus && GUIDE_TARGET,
                                )}
                            >
                                <p className="font-medium">{entry.title}</p>
                                <dl className="mt-1 grid gap-x-3 gap-y-1 sm:grid-cols-[auto_1fr]">
                                    <dt className="text-muted-foreground">
                                        What Is Checked
                                    </dt>
                                    <dd>{entry.checks}</dd>
                                    <dt className="text-muted-foreground">
                                        Example
                                    </dt>
                                    <dd>{entry.example}</dd>
                                    <dt className="text-muted-foreground">
                                        Effect
                                    </dt>
                                    <dd>{entry.effect}</dd>
                                </dl>
                            </Surface>
                        ),
                    )}
                </GuideSection>
            ),
        })),
    ];

    return (
        <GuideDialog
            open={open}
            onOpenChange={onOpenChange}
            title="Report Differences Guide"
            tabs={tabs}
            tab={tab}
            onTabChange={setTab}
            anchor={
                focus && findingEntry(focus)?.family === tab
                    ? findingAnchor(focus)
                    : null
            }
        />
    );
}
