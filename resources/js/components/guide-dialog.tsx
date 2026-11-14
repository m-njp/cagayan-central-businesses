import { useEffect, useRef } from 'react';
import type { ComponentProps, KeyboardEvent, ReactNode } from 'react';
import { DialogDescription, DialogTitle } from '@/components/ui/dialog';
import { FocusDialog } from '@/components/ui/focus-dialog';

export type GuideTab = { id: string; title: string; content: ReactNode };

/** The class a guide entry takes while it is the one a link opened, so the reader finds it at once. */
export const GUIDE_TARGET = 'bg-accent ring-2 ring-primary';

/**
 * A guide in the shared blur overlay: sections are tabs listed at the left (a scrolling row on phones) and one shows
 * at a time. `anchor` is the id of an element inside the current tab to bring into view, such as the entry a link
 * opened (design.md, "Guides").
 */
export function GuideDialog({
    open,
    onOpenChange,
    onCloseAutoFocus,
    title,
    description,
    tabs,
    tab,
    onTabChange,
    anchor,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    onCloseAutoFocus?: ComponentProps<typeof FocusDialog>['onCloseAutoFocus'];
    title: string;
    description?: ReactNode;
    tabs: GuideTab[];
    tab: string;
    onTabChange: (tab: string) => void;
    anchor?: string | null;
}) {
    const tabList = useRef<HTMLDivElement>(null);
    const panel = useRef<HTMLDivElement>(null);
    const current = tabs.find((item) => item.id === tab) ?? tabs[0];
    const tabId = (id: string) => `guide-tab-${id}`;

    useEffect(() => {
        if (!open) return;
        const timer = window.setTimeout(() => {
            const target = anchor ? document.getElementById(anchor) : null;
            if (target) target.scrollIntoView({ block: 'start' });
            else panel.current?.scrollTo({ top: 0 });
        }, 50);
        return () => window.clearTimeout(timer);
    }, [open, anchor, current.id]);

    // Arrow keys move between tabs, as the tabs pattern expects; Home and End jump to the ends.
    const onTabKey = (event: KeyboardEvent<HTMLDivElement>) => {
        const index = tabs.indexOf(current);
        const next =
            event.key === 'ArrowDown' || event.key === 'ArrowRight'
                ? (index + 1) % tabs.length
                : event.key === 'ArrowUp' || event.key === 'ArrowLeft'
                  ? (index - 1 + tabs.length) % tabs.length
                  : event.key === 'Home'
                    ? 0
                    : event.key === 'End'
                      ? tabs.length - 1
                      : null;
        if (next === null) return;
        event.preventDefault();
        onTabChange(tabs[next].id);
        tabList.current
            ?.querySelector<HTMLElement>(`#${tabId(tabs[next].id)}`)
            ?.focus();
    };

    return (
        <FocusDialog
            open={open}
            onOpenChange={onOpenChange}
            onCloseAutoFocus={onCloseAutoFocus}
            closeButton
            className="flex h-dvh max-h-176 w-full max-w-4xl flex-col gap-4 px-4 pt-14 pb-4 text-sm leading-relaxed sm:max-w-4xl"
        >
            <header className="flex flex-col gap-2">
                <DialogTitle className="type-heading">{title}</DialogTitle>
                {description && (
                    <DialogDescription className="type-body text-foreground">
                        {description}
                    </DialogDescription>
                )}
            </header>
            <div className="grid min-h-0 flex-1 grid-rows-[auto_minmax(0,1fr)] gap-4 md:grid-cols-[13rem_minmax(0,1fr)] md:grid-rows-1 md:gap-6">
                <div
                    ref={tabList}
                    role="tablist"
                    aria-label={`${title} sections`}
                    aria-orientation="vertical"
                    onKeyDown={onTabKey}
                    className="flex scrollbar-quiet gap-1 overflow-x-auto md:flex-col md:overflow-x-visible md:overflow-y-auto"
                >
                    {tabs.map((item) => (
                        <button
                            key={item.id}
                            id={tabId(item.id)}
                            type="button"
                            role="tab"
                            aria-selected={item === current}
                            aria-controls="guide-panel"
                            tabIndex={item === current ? 0 : -1}
                            onClick={() => onTabChange(item.id)}
                            className="min-h-9 shrink-0 rounded-sm px-3 py-1.5 text-left whitespace-nowrap text-muted-foreground outline-none hover:bg-accent hover:text-accent-foreground focus-visible:ring-2 focus-visible:ring-ring aria-selected:bg-accent aria-selected:font-medium aria-selected:text-accent-foreground md:whitespace-normal"
                        >
                            {item.title}
                        </button>
                    ))}
                </div>
                <div
                    ref={panel}
                    id="guide-panel"
                    role="tabpanel"
                    aria-labelledby={tabId(current.id)}
                    tabIndex={0}
                    className="flex min-h-0 scrollbar-quiet flex-col gap-6 overflow-y-auto py-0.5 pr-3 pl-0.5 outline-none focus-visible:ring-2 focus-visible:ring-ring"
                >
                    {current.content}
                </div>
            </div>
        </FocusDialog>
    );
}

/** One titled part of a guide tab. */
export function GuideSection({
    id,
    title,
    children,
}: {
    id?: string;
    title: string;
    children: ReactNode;
}) {
    return (
        <section id={id} className="flex scroll-mt-2 flex-col gap-3">
            <h3 className="type-item">{title}</h3>
            {children}
        </section>
    );
}
