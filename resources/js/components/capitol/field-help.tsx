import { useState, type ReactNode } from 'react';
import { Button } from '@/components/ui/button';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { icons } from '@/lib/icons';

export function FieldHelp({
    label,
    children,
}: {
    label: string;
    children: ReactNode;
}) {
    const [open, setOpen] = useState(false);
    const Help = icons.help;
    return (
        <TooltipProvider>
            <Tooltip open={open} onOpenChange={setOpen}>
                <TooltipTrigger asChild>
                    <Button
                        type="button"
                        variant="ghost"
                        size="icon"
                        className="size-8 shrink-0"
                        aria-label={`Help for ${label}`}
                        onClick={(event) => {
                            event.preventDefault();
                            setOpen(true);
                        }}
                    >
                        <Help className="size-4" aria-hidden="true" />
                    </Button>
                </TooltipTrigger>
                <TooltipContent className="max-w-72 type-body">
                    {children}
                </TooltipContent>
            </Tooltip>
        </TooltipProvider>
    );
}
