import { useState } from 'react';
import type { ReactElement } from 'react';
import { MapPin } from 'lucide-react';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { formatCount } from '@/lib/format';
import type { WishAggregate } from '@/types/economic-performance';

export function WishTooltip({
    item,
    townName,
    children,
}: {
    item: WishAggregate;
    townName: string;
    children: ReactElement;
}) {
    const [open, setOpen] = useState(false);
    return (
        <Tooltip open={open} onOpenChange={setOpen}>
            <TooltipTrigger
                asChild
                onClick={() => setOpen(!open)}
                onKeyDown={(event) => {
                    if (event.key === 'Enter' || event.key === ' ') {
                        event.preventDefault();
                        setOpen(!open);
                    }
                }}
            >
                {children}
            </TooltipTrigger>
            <TooltipContent className="grid max-w-72 gap-1 motion-reduce:animate-none">
                <p className="type-body break-words">{item.label}</p>
                <p className="flex items-center gap-1">
                    <MapPin className="size-3 shrink-0" aria-hidden="true" />
                    {townName}
                </p>
                <p className="font-mono tabular-nums">
                    {formatCount(item.count)} wishes
                </p>
            </TooltipContent>
        </Tooltip>
    );
}
