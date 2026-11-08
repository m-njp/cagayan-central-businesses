import { useRef, useState } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { icons } from '@/lib/icons';

// A magnifier that opens into a search box; it stays open while it holds text.
export function SearchField({
    value,
    onChange,
    label = 'Search',
}: {
    value: string;
    onChange: (value: string) => void;
    label?: string;
}) {
    const [open, setOpen] = useState(false);
    const input = useRef<HTMLInputElement>(null);

    if (!open && !value)
        return (
            <Button
                type="button"
                variant="outline"
                size="icon"
                className="size-8"
                aria-label={label}
                title={label}
                onClick={() => {
                    setOpen(true);
                    requestAnimationFrame(() => input.current?.focus());
                }}
            >
                <icons.search className="size-4" aria-hidden="true" />
            </Button>
        );

    return (
        <div className="relative">
            <icons.search
                className="pointer-events-none absolute top-1/2 left-2.5 size-4 -translate-y-1/2 text-muted-foreground"
                aria-hidden="true"
            />
            <Input
                ref={input}
                type="search"
                value={value}
                maxLength={60}
                aria-label={label}
                className="h-8 w-44 pl-8 sm:w-56"
                autoFocus
                onChange={(event) => onChange(event.target.value)}
                onBlur={() => !value && setOpen(false)}
            />
        </div>
    );
}
