import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { formatDate } from '@/lib/format';
import {
    FIGURE_STATUS,
    FIGURE_STATUS_DOTS,
    PERMIT_STATUS,
    RECORD_STATUS,
} from '@/lib/registry';

const BADGE =
    'inline-flex items-center gap-1.5 rounded-sm border bg-card px-2 py-0.5 type-meta text-foreground';

// The label is always printed, so the dot color is never the only signal.
export function StatusBadge({ dot, label }: { dot: string; label: string }) {
    return (
        <span className={BADGE}>
            <span
                className={`size-2 shrink-0 rounded-full ${dot}`}
                aria-hidden="true"
            />
            {label}
        </span>
    );
}

// The status of an entered figure in a table cell: a dot and the word, plus who entered it or why it came back.
export function FigureStatus({
    status,
    enteredByYou,
    note,
}: {
    status: string;
    enteredByYou?: boolean;
    note?: string | null;
}) {
    return (
        <span className="flex flex-col type-meta">
            <span className="inline-flex items-center gap-1.5">
                <span
                    className={`size-2 shrink-0 rounded-full ${FIGURE_STATUS_DOTS[status] ?? ''}`}
                    aria-hidden="true"
                />
                {FIGURE_STATUS[status] ?? status}
                {enteredByYou && status === 'pending' && (
                    <span className="text-muted-foreground">by you</span>
                )}
            </span>
            {note && <span className="text-muted-foreground">{note}</span>}
        </span>
    );
}

export function PermitBadge({
    status,
    validUntil,
}: {
    status: string;
    validUntil?: string | null;
}) {
    // A valid permit is the expected state, so only a problem gets a badge.
    if (status === 'valid') return null;
    const permit = PERMIT_STATUS[status] ?? PERMIT_STATUS.none;
    if (!validUntil) return <StatusBadge {...permit} />;

    return (
        <Tooltip>
            <TooltipTrigger asChild>
                <button type="button" className="rounded-sm">
                    <StatusBadge {...permit} />
                </button>
            </TooltipTrigger>
            <TooltipContent>
                Valid until {formatDate(validUntil)}
            </TooltipContent>
        </Tooltip>
    );
}

export function RecordBadge({ status }: { status: string }) {
    if (status === 'approved') return null;

    return (
        <span className={`${BADGE} border-dashed`}>
            {RECORD_STATUS[status] ?? status}
        </span>
    );
}
