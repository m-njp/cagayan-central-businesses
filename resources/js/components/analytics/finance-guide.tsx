import { CircleHelp } from 'lucide-react';
import { createContext, useContext, useRef, useState } from 'react';
import type { ReactNode } from 'react';
import { GuideDialog, GuideSection } from '@/components/guide-dialog';
import type { GuideTab } from '@/components/guide-dialog';
import { Button } from '@/components/ui/button';
import type { AnalyticsGuide } from '@/types/economic-performance';

/** The entry a row label or a missing figure opens; null opens the guide at the top. */
type OpenGuide = (entry: string | null) => void;

const GuideContext = createContext<OpenGuide | null>(null);

export const MISSING_ENTRY = 'missing';

const STEPS_ENTRY = 'steps';

export function guideAnchor(entry: string): string {
    return `guide-${entry.replaceAll('.', '-')}`;
}

/** Opens the Government Finances Guide; null outside a guide provider, so callers render plain text there. */
export function useFinanceGuide(): OpenGuide | null {
    return useContext(GuideContext);
}

/**
 * The Government Finances Guide, shared by public Economy and Capitol Analytics, in the shared tabbed guide overlay.
 * Row labels, missing figures and the Help button open it at the matching section (design.md, "Government Finances
 * Guide").
 */
export function FinanceGuideProvider({
    guide,
    showBusiness,
    children,
}: {
    guide: AnalyticsGuide;
    showBusiness: boolean;
    children: ReactNode;
}) {
    const [open, setOpen] = useState(false);
    const [entry, setEntry] = useState<string | null>(null);
    const [tab, setTab] = useState<string | null>(null);
    // Focus returns to whatever opened the guide: the Guide button, a row label or an N/A cell.
    const opener = useRef<HTMLElement | null>(null);
    const topics = guide.topics.filter(
        (topic) => showBusiness || topic.id !== 'business',
    );
    const limits = guide.notes.find((note) => note.id === 'limits');

    const topicTab = (topic: AnalyticsGuide['topics'][number]): GuideTab => ({
        id: topic.id,
        title: topic.title,
        content: (
            <>
                <GuideSection title={topic.title}>
                    <p>{topic.summary}</p>
                    <dl className="flex flex-col gap-4">
                        {topic.metrics.map((metric) => (
                            <div
                                key={metric.id}
                                id={guideAnchor(metric.id)}
                                className={`flex scroll-mt-2 flex-col gap-1 border-l-2 pl-3 ${metric.id === entry ? 'border-primary' : ''}`}
                            >
                                <dt className="font-medium">{metric.label}</dt>
                                <dd>{metric.meaning}</dd>
                                <dd className="text-muted-foreground">
                                    {metric.why}
                                </dd>
                                {metric.formula !== 'Reported amount.' && (
                                    <dd className="type-data text-muted-foreground">
                                        {metric.formula}
                                    </dd>
                                )}
                            </div>
                        ))}
                    </dl>
                </GuideSection>
                {topic.id === 'budget' && (
                    <GuideSection
                        id={guideAnchor(STEPS_ENTRY)}
                        title="How a Budget Is Spent"
                    >
                        <ol className="flex flex-col gap-3">
                            {guide.budgetSteps.map((step, index) => (
                                <li
                                    key={step.id}
                                    className="grid grid-cols-[auto_1fr] gap-x-3"
                                >
                                    <span className="font-mono text-muted-foreground tabular-nums">
                                        {index + 1}
                                    </span>
                                    <span>
                                        <span className="font-medium">
                                            {step.term}.
                                        </span>{' '}
                                        {step.text}
                                    </span>
                                </li>
                            ))}
                        </ol>
                    </GuideSection>
                )}
            </>
        ),
    });
    const noteTab = (note: AnalyticsGuide['notes'][number]): GuideTab => ({
        id: note.id,
        title: note.title,
        content: (
            <GuideSection title={note.title}>
                <p>{note.text}</p>
            </GuideSection>
        ),
    });
    const tabs: GuideTab[] = [
        ...guide.notes.filter((note) => note.id !== 'limits').map(noteTab),
        ...topics.map(topicTab),
        {
            id: MISSING_ENTRY,
            title: 'Reading N/A',
            content: (
                <GuideSection title="Reading N/A">
                    <p>
                        N/A means the figure is not available. It is never zero.
                        Hover over, focus on or tap N/A to see the reason, which
                        is one of these:
                    </p>
                    <ul className="flex list-disc flex-col gap-1 pl-5">
                        {guide.reasons.map((reason) => (
                            <li key={reason.id}>{reason.text}</li>
                        ))}
                    </ul>
                </GuideSection>
            ),
        },
        ...(limits ? [noteTab(limits)] : []),
    ];
    // An entry is a section, the budget steps, or one line inside a section.
    const tabFor = (target: string | null): string =>
        tabs.find((item) => item.id === target)?.id ??
        (target === STEPS_ENTRY ? 'budget' : undefined) ??
        topics.find((topic) =>
            topic.metrics.some((metric) => metric.id === target),
        )?.id ??
        tabs[0].id;
    const current = tab ?? tabs[0].id;

    const openGuide: OpenGuide = (next) => {
        opener.current =
            document.activeElement instanceof HTMLElement
                ? document.activeElement
                : null;
        setEntry(next);
        setTab(tabFor(next));
        setOpen(true);
    };

    return (
        <GuideContext.Provider value={openGuide}>
            {children}
            <GuideDialog
                open={open}
                onOpenChange={setOpen}
                onCloseAutoFocus={(event) => {
                    if (!opener.current?.isConnected) return;
                    event.preventDefault();
                    opener.current.focus();
                }}
                title="Government Finances Guide"
                description={guide.introduction}
                tabs={tabs}
                tab={current}
                onTabChange={(next) => {
                    setEntry(null);
                    setTab(next);
                }}
                anchor={entry && entry !== current ? guideAnchor(entry) : null}
            />
        </GuideContext.Provider>
    );
}

/** The icon Help button in the Government Finances header. */
export function FinanceGuideButton() {
    const openGuide = useFinanceGuide();
    if (!openGuide) return null;
    return (
        <Button
            variant="outline"
            size="icon"
            className="size-9"
            aria-label="Guide"
            title="Guide"
            onClick={() => openGuide(null)}
        >
            <CircleHelp aria-hidden="true" />
        </Button>
    );
}

/** A row label that opens the guide at its definition; plain text where no guide is provided. */
export function GuideTerm({ entry, label }: { entry: string; label: string }) {
    const openGuide = useFinanceGuide();
    if (!openGuide) return <>{label}</>;
    return (
        <button
            type="button"
            className="text-left decoration-dotted underline-offset-4 hover:underline focus-visible:underline"
            aria-label={`${label}, open its definition`}
            onClick={() => openGuide(entry)}
        >
            {label}
        </button>
    );
}
