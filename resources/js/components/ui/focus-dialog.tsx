import type { ComponentProps } from 'react';
import { useRef } from 'react';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
} from '@/components/ui/dialog';
import { icons } from '@/lib/icons';
import { cn } from '@/lib/utils';

// Both fade over the page: the blur appears and leaves with the overlay, slower in than out. Reduced motion skips it.
const FADE =
    'data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=open]:fade-in-0 data-[state=closed]:fade-out-0 data-[state=open]:duration-(--duration-slow) data-[state=closed]:duration-(--duration-base) motion-reduce:animate-none';

// The workspace's one full-page focus view: the page stays visible under a Gaussian blur and the content sits bare in
// the middle, with no card. The parsing progress and the Home map both use it. Children bring their own title.
export function FocusDialog({
    className,
    closeButton = false,
    closeClassName,
    onCloseAutoFocus,
    children,
    ...props
}: ComponentProps<typeof Dialog> & {
    className?: string;
    closeButton?: boolean;
    closeClassName?: string;
    onCloseAutoFocus?: ComponentProps<typeof DialogContent>['onCloseAutoFocus'];
}) {
    const close = useRef<HTMLButtonElement>(null);

    return (
        <Dialog {...props}>
            <DialogContent
                showCloseButton={false}
                onOpenAutoFocus={(event) => {
                    if (!close.current) return;
                    event.preventDefault();
                    close.current.focus();
                }}
                onCloseAutoFocus={onCloseAutoFocus}
                overlayClassName={cn(
                    'bg-background/45 backdrop-blur-sm dark:bg-background/75 dark:backdrop-blur-lg',
                    FADE,
                )}
                className={cn(
                    'border-0 bg-transparent p-0 shadow-none',
                    FADE,
                    className,
                )}
            >
                {children}
                {closeButton && (
                    <DialogClose asChild>
                        <Button
                            ref={close}
                            variant="subtle"
                            size="touch"
                            aria-label="Close"
                            className={cn(
                                'absolute top-[max(--spacing(2),env(safe-area-inset-top))] right-[max(--spacing(2),env(safe-area-inset-right))] z-10',
                                closeClassName,
                            )}
                        >
                            <icons.close aria-hidden="true" />
                        </Button>
                    </DialogClose>
                )}
            </DialogContent>
        </Dialog>
    );
}

export function FocusNavigation({
    previousLabel,
    nextLabel,
    onStep,
    canPrevious = true,
    canNext = true,
}: {
    previousLabel: string;
    nextLabel: string;
    onStep: (direction: -1 | 1) => void;
    canPrevious?: boolean;
    canNext?: boolean;
}) {
    const style = 'pointer-events-auto cursor-pointer hover:bg-brand hover:text-brand-foreground focus-visible:bg-brand focus-visible:text-brand-foreground disabled:invisible';

    return (
        <nav aria-label="Focus view navigation" className="pointer-events-none absolute inset-x-0 top-[max(--spacing(2),env(safe-area-inset-top))] z-10 mx-auto flex max-w-6xl justify-between pr-[max(--spacing(2),env(safe-area-inset-right))] pl-[max(--spacing(2),env(safe-area-inset-left))] md:top-1/2 md:-translate-y-1/2">
            <Button variant="ghost" size="touch" aria-label={previousLabel} className={style} disabled={!canPrevious} onClick={() => onStep(-1)}>
                <icons.previous aria-hidden="true" className="size-8" />
            </Button>
            <Button variant="ghost" size="touch" aria-label={nextLabel} className={style} disabled={!canNext} onClick={() => onStep(1)}>
                <icons.next aria-hidden="true" className="size-8" />
            </Button>
        </nav>
    );
}
