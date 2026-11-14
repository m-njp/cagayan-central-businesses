import { EditButton } from '@/components/capitol/edit-button';
import { Input } from '@/components/ui/input';

// One number entry in a grid. A verified figure is shown as text with a pen: correcting it is a deliberate act on
// that one figure. Everything else stays an input, so bulk entry is unchanged.
export function EntryCell({
    locked,
    display,
    label,
    onUnlock,
    value,
    onChange,
    inputMode,
    changed,
    error,
}: {
    locked: boolean;
    display: string;
    label: string;
    onUnlock: () => void;
    value: string;
    onChange: (value: string) => void;
    inputMode: 'numeric' | 'decimal';
    changed: boolean;
    error?: string;
}) {
    if (locked) {
        return (
            <div className="flex h-7 items-center justify-end gap-1 pl-2 font-mono text-sm tabular-nums">
                {display}
                <EditButton label={`Correct ${label}`} onClick={onUnlock} />
            </div>
        );
    }

    return (
        <Input
            aria-label={label}
            aria-invalid={error ? true : undefined}
            title={error}
            inputMode={inputMode}
            placeholder={
                inputMode === 'decimal' ? 'Enter amount' : 'Enter count'
            }
            className={`h-7 min-w-32 text-right font-mono text-sm tabular-nums ${changed ? 'border-primary bg-accent' : ''}`}
            value={value}
            onChange={(event) => onChange(event.target.value)}
        />
    );
}
