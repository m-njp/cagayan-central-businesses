import { Figure } from '@/components/charts/finance-panel';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { financeMapObservation } from '@/lib/finance-map';
import { NOT_AVAILABLE } from '@/lib/format';
import type { FinanceMap } from '@/types/finance-map';

const YEARS_SHOWN = 5;
const number = 'text-right font-mono tabular-nums';

// Income, Expenses and Net Income of one place, years as columns, oldest to newest. The Capitol dashboard and the
// Home focus view both read it from the finance map they already loaded.
export function FinanceYearsTable({
    finances,
    code,
    name,
    limit = YEARS_SHOWN,
    plain = false,
}: {
    finances: FinanceMap;
    code: string;
    name: string;
    limit?: number;
    plain?: boolean;
}) {
    const years = [...finances.years].sort((a, b) => a - b).slice(-limit);
    const reported = years.some((year) =>
        finances.metrics.some(
            (metric) =>
                financeMapObservation(finances, code, year, metric.id)
                    ?.state === 'shown',
        ),
    );

    if (!reported) {
        return (
            <p role="status" className="p-panel type-body">
                {NOT_AVAILABLE}
            </p>
        );
    }

    return (
        <Table
            data-striped={plain ? undefined : true}
            className={`type-data sm:type-body ${plain ? '[&_.text-trend-down]:text-foreground [&_.text-trend-up]:text-foreground' : ''}`}
        >
            <TableHeader>
                <TableRow
                    className={plain ? 'hover:bg-transparent' : undefined}
                >
                    <TableHead scope="col">{name}</TableHead>
                    {years.map((year) => (
                        <TableHead key={year} scope="col" className={number}>
                            {year}
                        </TableHead>
                    ))}
                </TableRow>
            </TableHeader>
            <TableBody>
                {finances.metrics.map((metric) => (
                    <TableRow
                        key={metric.id}
                        className={plain ? 'hover:bg-transparent' : undefined}
                    >
                        <TableHead
                            scope="row"
                            className="h-auto py-1.5 font-normal whitespace-normal"
                        >
                            {metric.label}
                        </TableHead>
                        {years.map((year) => (
                            <TableCell key={year} className={number}>
                                <Figure
                                    point={financeMapObservation(
                                        finances,
                                        code,
                                        year,
                                        metric.id,
                                    )}
                                    metric={metric}
                                    alwaysShort
                                    withChange
                                />
                            </TableCell>
                        ))}
                    </TableRow>
                ))}
            </TableBody>
        </Table>
    );
}
