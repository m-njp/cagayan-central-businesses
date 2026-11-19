import { useState } from 'react';
import type { ReactNode } from 'react';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';

/**
 * A short or rounded figure that shows the exact amount on hover, focus or tap, with an optional second line such as
 * its change, so a figure and its arrow share one tooltip. The text is also the accessible name. With onSelect, a
 * click while the tooltip shows (after hover, focus or a first tap) runs it, such as opening the guide.
 */
export function ExactAmount({
    exact,
    detail,
    label,
    onSelect,
    children,
}: {
    exact: string;
    detail?: string | null;
    label?: string;
    onSelect?: () => void;
    children: ReactNode;
}) {
    const [open, setOpen] = useState(false);
    return (
        <TooltipProvider>
            <Tooltip open={open} onOpenChange={setOpen}>
                <TooltipTrigger asChild>
                    <button
                        type="button"
                        aria-label={
                            label ?? [exact, detail].filter(Boolean).join('. ')
                        }
                        className="min-h-6 cursor-help rounded-sm underline decoration-dotted underline-offset-4"
                        onClick={(event) => {
                            event.preventDefault();
                            if (onSelect && open) {
                                setOpen(false);
                                onSelect();
                                return;
                            }
                            setOpen((current) => !current);
                        }}
                    >
                        {children}
                    </button>
                </TooltipTrigger>
                <TooltipContent className="grid gap-0.5 font-mono type-body tabular-nums">
                    <span>{exact}</span>
                    {detail && <span className="font-sans">{detail}</span>}
                </TooltipContent>
            </Tooltip>
        </TooltipProvider>
    );
}
