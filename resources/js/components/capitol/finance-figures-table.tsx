import { Fragment, useRef, useState } from 'react';
import { FinanceReviewDialog } from '@/components/capitol/finance-review-dialog';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Surface } from '@/components/ui/surface';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import {
    formatExactPesos,
    formatFinanceSource,
} from '@/lib/performance-format';
import { FIGURE_STATUS } from '@/lib/registry';
import type { FinanceReport } from '@/types/finance';

export function FinanceFiguresTable({
    report,
    fiscalYear,
}: {
    report: FinanceReport;
    fiscalYear: number;
}) {
    const [reviewing, setReviewing] = useState(false);
    const [selected, setSelected] = useState<string[]>([]);
    const [decision, setDecision] = useState<'verified' | 'rejected' | null>(
        null,
    );
    const reviewTrigger = useRef<HTMLButtonElement>(null);
    const figures = report.figures.filter(
        (figure) => figure.fiscalYear === fiscalYear,
    );
    const groups = [...new Set(figures.map((figure) => figure.group))];
    const reviewable = figures.filter(
        (figure) => figure.status === 'pending' && !figure.enteredByYou,
    );
    const toggle = (id: string, checked: boolean) =>
        setSelected((ids) =>
            checked ? [...ids, id] : ids.filter((value) => value !== id),
        );
    const openReview = (
        value: 'verified' | 'rejected',
        trigger: HTMLButtonElement,
    ) => {
        reviewTrigger.current = trigger;
        setDecision(value);
    };
    if (!figures.length)
        return (
            <p className="rounded-sm border bg-card p-4 type-body text-muted-foreground">
                No figures for this fiscal year.
            </p>
        );
    return (
        <div className="flex min-w-0 flex-col gap-2">
            {report.canReview && reviewable.length > 0 && (
                <div className="flex flex-wrap items-center justify-end gap-2">
                    {selected.length > 0 && (
                        <>
                            <span className="type-meta">
                                <span className="font-mono tabular-nums">
                                    {selected.length}
                                </span>{' '}
                                selected
                            </span>
                            <Button
                                size="sm"
                                onClick={(event) =>
                                    openReview('verified', event.currentTarget)
                                }
                            >
                                Verify
                            </Button>
                            <Button
                                size="sm"
                                variant="outline"
                                onClick={(event) =>
                                    openReview('rejected', event.currentTarget)
                                }
                            >
                                Return
                            </Button>
                        </>
                    )}
                    <Button
                        size="sm"
                        variant="outline"
                        onClick={() => {
                            setReviewing(!reviewing);
                            setSelected([]);
                        }}
                    >
                        {reviewing ? 'Cancel Review' : 'Review Figures'}
                    </Button>
                </div>
            )}
            <Surface>
                <Table className="w-full min-w-[40rem] table-fixed">
                    <colgroup>
                        {reviewing && <col className="w-10" />}
                        <col />
                        <col className="w-1/4" />
                        <col className="w-[30%]" />
                        <col className="w-32" />
                    </colgroup>
                    <TableHeader>
                        <TableRow>
                            {reviewing && (
                                <TableHead>
                                    <input
                                        type="checkbox"
                                        className="size-4 accent-primary"
                                        aria-label="Select all pending figures"
                                        checked={
                                            selected.length ===
                                                reviewable.length &&
                                            selected.length > 0
                                        }
                                        disabled={
                                            reviewable.length >
                                            report.limits.reviewBatchMax
                                        }
                                        onChange={(event) =>
                                            setSelected(
                                                event.target.checked
                                                    ? reviewable.map(
                                                          (figure) => figure.id,
                                                      )
                                                    : [],
                                            )
                                        }
                                    />
                                </TableHead>
                            )}
                            <TableHead>Item</TableHead>
                            <TableHead className="text-right">
                                Amount in PHP (₱)
                            </TableHead>
                            <TableHead>Source</TableHead>
                            <TableHead>Status</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {groups.map((group) => {
                            const rows = figures.filter(
                                (figure) => figure.group === group,
                            );
                            return (
                                <Fragment key={group}>
                                    <TableRow className="bg-muted/40 hover:bg-muted/40">
                                        <TableCell
                                            colSpan={reviewing ? 5 : 4}
                                            className="py-2 type-body font-medium"
                                        >
                                            {rows[0].groupLabel}
                                            {group === 'business_taxes' && (
                                                <span className="ml-3 type-meta">
                                                    General Fund
                                                </span>
                                            )}
                                        </TableCell>
                                    </TableRow>
                                    {rows.map((figure) => (
                                        <TableRow key={figure.id}>
                                            {reviewing && (
                                                <TableCell>
                                                    {reviewable.includes(
                                                        figure,
                                                    ) && (
                                                        <input
                                                            type="checkbox"
                                                            className="size-4 accent-primary"
                                                            aria-label={`Select ${figure.label} ${figure.fiscalYear} ${figure.basis === 'budget_final' ? 'Final Budget' : 'Actual'}`}
                                                            checked={selected.includes(
                                                                figure.id,
                                                            )}
                                                            disabled={
                                                                !selected.includes(
                                                                    figure.id,
                                                                ) &&
                                                                selected.length >=
                                                                    report
                                                                        .limits
                                                                        .reviewBatchMax
                                                            }
                                                            onChange={(event) =>
                                                                toggle(
                                                                    figure.id,
                                                                    event.target
                                                                        .checked,
                                                                )
                                                            }
                                                        />
                                                    )}
                                                </TableCell>
                                            )}
                                            <TableCell className="whitespace-normal">
                                                <span className="type-body">
                                                    {figure.label}
                                                </span>
                                                {figure.basis ===
                                                    'budget_final' && (
                                                    <span className="block type-meta">
                                                        Final Budget
                                                    </span>
                                                )}
                                            </TableCell>
                                            <TableCell className="text-right font-mono tabular-nums">
                                                {formatExactPesos(figure.value)}
                                            </TableCell>
                                            <TableCell className="type-meta whitespace-normal">
                                                {figure.sourceUrl ? (
                                                    <a
                                                        href={figure.sourceUrl}
                                                        target="_blank"
                                                        rel="noreferrer"
                                                        className="underline underline-offset-4"
                                                        title={[
                                                            figure.source,
                                                            figure.location,
                                                        ]
                                                            .filter(Boolean)
                                                            .join(' ')}
                                                        aria-label={`${formatFinanceSource(figure.source, figure.location)} (opens in a new tab)`}
                                                    >
                                                        {formatFinanceSource(
                                                            figure.source,
                                                            figure.location,
                                                        )}
                                                    </a>
                                                ) : (
                                                    formatFinanceSource(
                                                        figure.source,
                                                        figure.location,
                                                    )
                                                )}
                                            </TableCell>
                                            <TableCell className="whitespace-normal">
                                                <Badge
                                                    variant={
                                                        figure.status ===
                                                        'rejected'
                                                            ? 'destructive'
                                                            : figure.status ===
                                                                'pending'
                                                              ? 'outline'
                                                              : 'secondary'
                                                    }
                                                >
                                                    {
                                                        FIGURE_STATUS[
                                                            figure.status
                                                        ]
                                                    }
                                                </Badge>
                                                {figure.enteredByYou &&
                                                    figure.status ===
                                                        'pending' && (
                                                        <span className="block type-meta">
                                                            Entered by you
                                                        </span>
                                                    )}
                                                {figure.reviewedBy &&
                                                    (figure.status ===
                                                        'verified' ||
                                                        figure.status ===
                                                            'rejected') && (
                                                        <span className="block type-meta">
                                                            {figure.status ===
                                                            'verified'
                                                                ? 'Verified by '
                                                                : 'Returned by '}
                                                            {figure.reviewedBy}
                                                        </span>
                                                    )}
                                                {figure.status === 'rejected' &&
                                                    figure.note && (
                                                        <p className="mt-1 type-meta break-words">
                                                            {figure.note}
                                                        </p>
                                                    )}
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </Fragment>
                            );
                        })}
                    </TableBody>
                </Table>
            </Surface>
            {decision && (
                <FinanceReviewDialog
                    key={decision}
                    report={report}
                    fiscalYear={fiscalYear}
                    decision={decision}
                    ids={selected}
                    onClose={() => setDecision(null)}
                    onReturnFocus={() => reviewTrigger.current?.focus()}
                    onComplete={() => {
                        setSelected([]);
                        setDecision(null);
                        setReviewing(false);
                    }}
                />
            )}
        </div>
    );
}
