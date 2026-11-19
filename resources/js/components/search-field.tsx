import { useRef, useState } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { icons } from '@/lib/icons';
import { cn } from '@/lib/utils';

export function SearchField({
    value,
    onChange,
    label = 'Search',
    compact = false,
    disabled = false,
    expandedClassName,
}: {
    value: string;
    onChange: (value: string) => void;
    label?: string;
    compact?: boolean;
    disabled?: boolean;
    expandedClassName?: string;
}) {
    const [open, setOpen] = useState(false);
    const input = useRef<HTMLInputElement>(null);
    const trigger = useRef<HTMLButtonElement>(null);

    if (disabled || (!open && !value)) {
        return (
            <Button
                ref={trigger}
                type="button"
                variant="subtle"
                size="touch"
                className={cn(compact && 'md:size-control-compact')}
                aria-label={label}
                title={label}
                disabled={disabled}
                onClick={() => {
                    setOpen(true);
                    requestAnimationFrame(() => input.current?.focus());
                }}
            >
                <icons.search aria-hidden="true" />
            </Button>
        );
    }

    return (
        <div
            className={cn(
                'relative min-w-0',
                compact && 'w-44 max-w-full sm:w-56',
                expandedClassName,
            )}
            onBlur={(event) => {
                if (!event.currentTarget.contains(event.relatedTarget)) {
                    setOpen(false);
                }
            }}
        >
            <Input
                ref={input}
                type="search"
                value={value}
                maxLength={60}
                aria-label={label}
                placeholder={label}
                className={cn(
                    'h-touch [&::-webkit-search-cancel-button]:hidden',
                    compact && 'md:h-control-compact',
                    value && 'pr-touch',
                )}
                onChange={(event) => onChange(event.target.value)}
                onKeyDown={(event) => {
                    if (event.key !== 'Escape') return;
                    event.preventDefault();
                    if (value) return;
                    setOpen(false);
                    requestAnimationFrame(() => trigger.current?.focus());
                }}
            />
            {value && (
                <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    aria-label="Clear Search"
                    className={cn(
                        'absolute inset-y-0 right-0 size-touch',
                        compact && 'md:size-control-compact',
                    )}
                    onClick={() => {
                        onChange('');
                        setOpen(true);
                        input.current?.focus();
                    }}
                >
                    <icons.close aria-hidden="true" />
                </Button>
            )}
        </div>
    );
}
