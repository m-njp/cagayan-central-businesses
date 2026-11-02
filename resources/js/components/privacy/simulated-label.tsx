import { usePage } from '@inertiajs/react';

// The shared config keeps labels consistent in layouts and the cards containing simulated records.
export function SimulatedLabel() {
    const { simulated } = usePage().props;

    if (!simulated) {
        return null;
    }

    return (
        <span
            className="inline-flex shrink-0 items-center rounded-sm border border-destructive-foreground/30 bg-destructive-foreground/10 px-2 py-0.5 type-meta font-medium text-destructive-foreground"
            data-test="simulated-label"
        >
            Simulated data
        </span>
    );
}
