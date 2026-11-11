import { lazy, Suspense, useState } from 'react';
import type { ComponentProps } from 'react';
import { Button } from '@/components/ui/button';
import { DialogDescription, DialogTitle } from '@/components/ui/dialog';
import { FocusDialog, FocusNavigation } from '@/components/ui/focus-dialog';
import { VisualErrorBoundary } from '@/components/visual-error-boundary';
import type { Workflow } from './workflow-definitions';

const WorkflowCanvas = lazy(() => import('./workflow-canvas'));

function Chapter({ workflow }: { workflow: Workflow }) {
    const [selection, setSelection] = useState(0);
    const step = workflow.steps[selection];

    return (
        <div className="grid min-w-0 gap-5 motion-safe:animate-in motion-safe:duration-(--duration-base) motion-safe:fade-in lg:grid-cols-[minmax(0,1.5fr)_minmax(0,1fr)]">
            <VisualErrorBoundary
                fallback={
                    <p role="status" className="self-center type-body">
                        The diagram is unavailable. Use the step list to
                        continue.
                    </p>
                }
            >
                <Suspense
                    fallback={
                        <p
                            role="status"
                            className="grid min-h-44 place-items-center type-body"
                        >
                            Loading diagram…
                        </p>
                    }
                >
                    <WorkflowCanvas
                        workflow={workflow}
                        selected={step.id}
                        onSelect={setSelection}
                    />
                </Suspense>
            </VisualErrorBoundary>
            <div className="min-w-0 self-center border-t border-foreground/15 pt-4 lg:border-t-0 lg:border-l lg:pt-0 lg:pl-6">
                <div className="min-h-20" aria-live="polite" aria-atomic="true">
                    <h3 className="type-item">{step.label}</h3>
                    <p className="mt-1 max-w-prose type-body">{step.detail}</p>
                </div>
                <ol aria-label="Workflow steps" className="mt-3 grid gap-1">
                    {workflow.steps.map(({ id, label }, index) => (
                        <li key={id}>
                            <Button
                                variant={
                                    selection === index ? 'secondary' : 'ghost'
                                }
                                className="h-auto min-h-9 w-full justify-start text-left text-sm whitespace-normal max-sm:min-h-touch"
                                aria-current={
                                    selection === index ? 'step' : undefined
                                }
                                onClick={() => setSelection(index)}
                            >
                                <span
                                    aria-hidden="true"
                                    className="font-mono tabular-nums"
                                >
                                    {index + 1}.
                                </span>
                                {label}
                            </Button>
                        </li>
                    ))}
                </ol>
            </div>
        </div>
    );
}

export function DataEntryWorkflow({
    open,
    onOpenChange,
    onCloseAutoFocus,
    value,
    options,
    workflow,
    onChange,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    onCloseAutoFocus: ComponentProps<typeof FocusDialog>['onCloseAutoFocus'];
    value: string;
    options: { id: string; label: string }[];
    workflow: Workflow;
    onChange: (value: string) => void;
}) {
    const index = options.findIndex(({ id }) => id === value);
    const previous = options[index - 1];
    const next = options[index + 1];

    return (
        <FocusDialog
            open={open}
            onOpenChange={onOpenChange}
            onCloseAutoFocus={onCloseAutoFocus}
            closeButton
            closeClassName="max-md:right-1/2 max-md:translate-x-1/2"
            className="inset-0 block h-dvh max-w-none translate-x-0 translate-y-0 overflow-hidden text-foreground sm:max-w-none"
        >
            <FocusNavigation
                previousLabel="Previous chapter"
                nextLabel="Next chapter"
                canPrevious={Boolean(previous)}
                canNext={Boolean(next)}
                onStep={(direction) => {
                    const target = options[index + direction];
                    if (target) onChange(target.id);
                }}
            />
            <div className="mx-auto flex h-full max-w-6xl flex-col gap-3 px-gutter pt-[calc(var(--spacing-touch)+max(1rem,env(safe-area-inset-top)))] pb-[max(1rem,env(safe-area-inset-bottom))] md:px-[calc(var(--spacing-touch)+1rem)] md:pt-8">
                <header>
                    <DialogTitle className="text-lg font-semibold tracking-tight">
                        How Data Entry Works
                    </DialogTitle>
                    <DialogDescription className="sr-only">
                        Choose a chapter, then select a step in the diagram or
                        the step list.
                    </DialogDescription>
                </header>
                <nav
                    aria-label="Workflow chapters"
                    className="flex flex-wrap gap-1.5 border-b border-foreground/15 pb-3"
                >
                    {options.map(({ id, label }) => (
                        <Button
                            key={id}
                            size="sm"
                            variant={value === id ? 'default' : 'outline'}
                            className="max-sm:min-h-touch"
                            aria-current={value === id ? 'page' : undefined}
                            onClick={() => onChange(id)}
                        >
                            {label}
                        </Button>
                    ))}
                </nav>
                <div
                    key={value}
                    className="min-h-0 flex-1 overflow-y-auto overscroll-contain pb-2"
                >
                    <h2 className="mb-2 type-item">{workflow.title}</h2>
                    {open && <Chapter workflow={workflow} />}
                </div>
            </div>
        </FocusDialog>
    );
}
