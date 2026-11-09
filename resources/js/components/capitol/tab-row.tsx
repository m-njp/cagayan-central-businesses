import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';

export type Tab = {
    value: string | null;
    label: string;
    count?: number;
    mono?: boolean;
};

// The one tab row of the workspace: a filled pill marks the active tab, never an underline. Callers wrap it in a
// padded Surface.
export function TabRow({
    label,
    value,
    tabs,
    onChange,
}: {
    label: string;
    value: string | null;
    tabs: Tab[];
    onChange: (value: string | null) => void;
}) {
    return (
        <div role="tablist" aria-label={label} className="flex flex-wrap gap-2">
            {tabs.map((tab) => (
                <Button
                    key={tab.value ?? 'all'}
                    role="tab"
                    aria-selected={value === tab.value}
                    variant={value === tab.value ? 'secondary' : 'ghost'}
                    size="sm"
                    className={cn(tab.mono && 'font-mono tabular-nums')}
                    onClick={() => onChange(tab.value)}
                >
                    {tab.label}
                    {tab.count ? (
                        <span className="font-mono text-muted-foreground tabular-nums">
                            {tab.count}
                        </span>
                    ) : null}
                </Button>
            ))}
        </div>
    );
}
