import type { ReactNode } from 'react';
import { ProfileSection } from '@/components/profile-section';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { formatDate } from '@/lib/format';
import { icons } from '@/lib/icons';
import { cn } from '@/lib/utils';

function Mark({ label, children }: { label: string; children: ReactNode }) {
    return (
        <Tooltip>
            <TooltipTrigger asChild>
                <span className="inline-flex items-center">
                    {children}
                    <span className="sr-only">{label}</span>
                </span>
            </TooltipTrigger>
            <TooltipContent>{label}</TooltipContent>
        </Tooltip>
    );
}

// Directory row marks, named in a tooltip and for screen readers.
export function BirMark({ className }: { className?: string }) {
    const Bir = icons.birRegistered;

    return (
        <Mark label="BIR registered">
            <Bir
                className={cn('size-4 text-primary', className)}
                aria-hidden="true"
            />
        </Mark>
    );
}

export function MilestoneMark({ years }: { years: number }) {
    return (
        <Mark label={`In business ${years} years`}>
            <span
                aria-hidden="true"
                className="grid size-5 place-items-center rounded-full border border-highlight font-mono type-data text-foreground tabular-nums"
            >
                {years}
            </span>
        </Mark>
    );
}

function Medal({ tone, children }: { tone: string; children: ReactNode }) {
    return (
        <span
            aria-hidden="true"
            className={cn('relative h-20 w-18 shrink-0', tone)}
        >
            <svg viewBox="0 0 64 72" className="size-full">
                <path
                    d="M21 44 15 70l9-5 7 6 1-23zM43 44l6 26-9-5-7 6-1-23z"
                    fill="currentColor"
                    fillOpacity="0.45"
                />
                <path
                    d="M32 2 54.5 15v26L32 54 9.5 41V15z"
                    fill="currentColor"
                    fillOpacity="0.12"
                    stroke="currentColor"
                    strokeWidth="2"
                    strokeLinejoin="round"
                />
                <path
                    d="M32 8.5 48.9 18.25v19.5L32 47.5 15.1 37.75v-19.5z"
                    fill="none"
                    stroke="currentColor"
                    strokeOpacity="0.35"
                />
            </svg>
            <span className="absolute inset-x-0 top-0 grid h-16 place-items-center">
                {children}
            </span>
        </span>
    );
}

/** The original profile medals; only earned recognition is shown. */
export function Achievements({
    milestone,
    since,
    streak,
    bir,
}: {
    milestone: number | null;
    since: number;
    streak: number;
    bir: string | null;
}) {
    const Streak = icons.streak;
    const Bir = icons.birRegistered;
    const earned: {
        key: string;
        tone: string;
        face: ReactNode;
        title: string;
        detail: string;
    }[] = [];
    if (milestone)
        earned.push({
            key: 'milestone',
            tone: 'text-highlight',
            face: (
                <span className="flex flex-col items-center gap-0.5 text-foreground">
                    <span className="font-mono type-item leading-none font-semibold tabular-nums">
                        {milestone}
                    </span>
                    <span className="type-data leading-none">Years</span>
                </span>
            ),
            title: 'Years in Business',
            detail: `Since ${since}`,
        });
    if (streak > 1)
        earned.push({
            key: 'streak',
            tone: 'text-chart-1',
            face: (
                <span className="flex flex-col items-center gap-0.5">
                    <Streak className="size-5 fill-current" />
                    <span className="font-mono type-data leading-none font-semibold text-foreground tabular-nums">
                        {streak}
                    </span>
                </span>
            ),
            title: 'Renewal Streak',
            detail: `${streak} years in a row`,
        });
    if (bir)
        earned.push({
            key: 'bir',
            tone: 'text-primary',
            face: <Bir className="size-6" />,
            title: 'BIR Registered',
            detail: `Checked ${formatDate(bir)}`,
        });
    if (earned.length === 0) return null;

    return (
        <ProfileSection title="Achievements">
            <ul className="flex flex-wrap gap-x-4 gap-y-3">
                {earned.map((item) => (
                    <li
                        key={item.key}
                        className="flex w-32 flex-col items-center gap-1 text-center"
                    >
                        <Medal tone={item.tone}>{item.face}</Medal>
                        <span className="type-meta font-medium text-foreground">
                            {item.title}
                        </span>
                        <span className="type-data text-muted-foreground">
                            {item.detail}
                        </span>
                    </li>
                ))}
            </ul>
        </ProfileSection>
    );
}
