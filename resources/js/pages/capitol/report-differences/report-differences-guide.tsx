import { useEffect } from 'react';
import type { ReactNode } from 'react';
import { DialogTitle } from '@/components/ui/dialog';
import { FocusDialog } from '@/components/ui/focus-dialog';
import { Surface } from '@/components/ui/surface';
import type { ReportDifferenceFamily } from '@/types/finance';
import { FAMILIES, FINDINGS } from './finding-guide';

const FAMILY_ORDER: ReportDifferenceFamily[] = [
    'report',
    'between_reports',
    'reading',
];

function Section({ title, children }: { title: string; children: ReactNode }) {
    return (
        <section className="flex flex-col gap-2">
            <h3 className="type-item">{title}</h3>
            {children}
        </section>
    );
}

// The full guide to Report Differences, in the shared blur overlay. Opening it on a finding scrolls to that entry.
export function ReportDifferencesGuide({
    open,
    focus,
    onOpenChange,
}: {
    open: boolean;
    focus: string | null;
    onOpenChange: (open: boolean) => void;
}) {
    useEffect(() => {
        if (!open || !focus) return;
        const timer = window.setTimeout(
            () =>
                document
                    .getElementById(`finding-${focus}`)
                    ?.scrollIntoView({ block: 'start' }),
            50,
        );
        return () => window.clearTimeout(timer);
    }, [open, focus]);

    return (
        <FocusDialog
            open={open}
            onOpenChange={onOpenChange}
            closeButton
            className="max-h-dvh w-full max-w-3xl overflow-y-auto px-4 py-14 sm:max-w-3xl"
        >
            <article className="flex flex-col gap-6 text-sm leading-relaxed">
                <DialogTitle className="type-heading">
                    Report Differences Guide
                </DialogTitle>

                <Section title="What This Page Shows">
                    <p>
                        Each municipality's finances come from the Executive
                        Summary of its Annual Audit Report, published by the
                        Commission on Audit. Every Executive Summary shows the
                        report year and, for comparison, the year before. The
                        system reads each report, checks its amounts and lists
                        anything a reviewer should know before the figures are
                        relied on.
                    </p>
                    <p>
                        A finding concerns how an amount is printed or read. It
                        is not an audit finding and does not indicate how public
                        funds were used.
                    </p>
                </Section>

                <Section title="Reading the Table">
                    <ul className="flex list-disc flex-col gap-1 pl-5">
                        <li>
                            Each row is a municipality and each column is the
                            year of an Annual Audit Report. A 2017 column holds
                            the 2017 report, which also shows 2016 for
                            comparison.
                        </li>
                        <li>
                            The number is the count of findings in that report
                            for the selected category.
                        </li>
                        <li>
                            A filled green dot means every figure from the
                            report is signed off. A hollow dot means some
                            figures are still pending.
                        </li>
                        <li>
                            Not available means the report has not been
                            collected or was not published.
                        </li>
                        <li>
                            Select a count to see each finding with a link to
                            the page of the report it concerns.
                        </li>
                    </ul>
                </Section>

                <Section title="Categories">
                    {FAMILY_ORDER.map((family) => (
                        <div key={family} className="flex flex-col gap-1">
                            <h4 className="font-medium">
                                {FAMILIES[family].label}
                            </h4>
                            <p>{FAMILIES[family].meaning}</p>
                            <p className="text-muted-foreground">
                                {FAMILIES[family].responsibility}
                            </p>
                        </div>
                    ))}
                </Section>

                <Section title="How Figures Are Signed Off">
                    <p>
                        A figure reaches the maps and Analytics only once it is
                        signed off. It is signed off automatically when two
                        Annual Audit Reports show the same amount to the
                        centavo, the year's own report and the next year's
                        comparison column, and neither report has a finding that
                        blocks that line. Every other figure waits for a
                        reviewer, who must be someone other than the person who
                        entered it.
                    </p>
                    <p>
                        When a later report restates a year, the later report
                        prevails and the earlier amount is kept as history. Two
                        reports agreeing does not prove an amount correct if the
                        same mistake was printed in both.
                    </p>
                </Section>

                <Section title="Findings">
                    {FAMILY_ORDER.map((family) => (
                        <div key={family} className="flex flex-col gap-3">
                            <h4 className="font-medium">
                                {FAMILIES[family].label}
                            </h4>
                            {FINDINGS.filter(
                                (entry) => entry.family === family,
                            ).map((entry) => (
                                <Surface
                                    key={entry.kind}
                                    id={`finding-${entry.kind}`}
                                    padded
                                    className="scroll-mt-14"
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
                            ))}
                        </div>
                    ))}
                </Section>
            </article>
        </FocusDialog>
    );
}
