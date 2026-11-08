import type { ComponentProps } from 'react';
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
    overlayClassName,
    className,
    closeButton = false,
    children,
    ...props
}: ComponentProps<typeof Dialog> & {
    overlayClassName?: string;
    className?: string;
    closeButton?: boolean;
}) {
    return (
        <Dialog {...props}>
            <DialogContent
                showCloseButton={false}
                overlayClassName={cn(
                    'bg-background/60 backdrop-blur-md',
                    FADE,
                    overlayClassName,
                )}
                className={cn(
                    'border-0 bg-transparent p-0 shadow-none data-[state=open]:zoom-in-95 data-[state=closed]:zoom-out-95',
                    FADE,
                    className,
                )}
            >
                {children}
                {closeButton && (
                    <DialogClose asChild>
                        <Button
                            variant="outline"
                            size="icon"
                            aria-label="Close"
                            className="absolute top-2 right-2 z-10 size-10"
                        >
                            <icons.close aria-hidden="true" />
                        </Button>
                    </DialogClose>
                )}
            </DialogContent>
        </Dialog>
    );
}
