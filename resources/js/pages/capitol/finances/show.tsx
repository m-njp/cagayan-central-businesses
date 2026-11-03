import { WorkspaceFilters } from '@/components/capitol/workspace-filters';
import { Head, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { FinanceFiguresTable } from '@/components/capitol/finance-figures-table';
import { FinanceReportGrid } from '@/components/capitol/finance-report-grid';
import {
    FinanceUploadForm,
    FinanceUploads,
} from '@/components/capitol/finance-upload';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
    SheetDescription,
    SheetTrigger,
} from '@/components/ui/sheet';
import { pageSearchParams } from '@/lib/navigation';
import { show } from '@/routes/capitol/finances';
import type { FinanceReport } from '@/types/finance';

export default function FinanceReportPage({
    report,
}: {
    report: FinanceReport;
}) {
    const { url } = usePage();
    const [entering, setEntering] = useState(false);
    const [uploading, setUploading] = useState(false);
    const years = [
        ...new Set([
            report.year,
            ...report.figures.map((figure) => figure.fiscalYear),
        ]),
    ].sort((a, b) => b - a);
    const requested = Number(pageSearchParams(url).get('fiscalYear'));
    const year = years.includes(requested) ? requested : years[0];
    const sources = report.documents.filter(
        (document) => document.available && document.sourceUrl,
    );
    const chooseYear = (next: string) => {
        if (Number(next) !== year)
            router.push({
                url: show(
                    { town: report.town.value, year: report.year },
                    { query: { fiscalYear: next } },
                ).url,
                preserveScroll: true,
                preserveState: true,
            });
    };
    return (
        <>
            <Head title={`${report.town.label} ${report.year}`} />
            <header className="flex flex-wrap items-center justify-between gap-3">
                <h1 className="type-heading">{report.town.label}</h1>
                <div className="flex flex-wrap items-center gap-2">
                    <Sheet open={entering} onOpenChange={setEntering}>
                        <SheetTrigger asChild>
                            <Button>Enter Report</Button>
                        </SheetTrigger>
                        <SheetContent className="w-full overflow-y-auto sm:max-w-3xl">
                            <SheetHeader>
                                <SheetTitle>Enter Report</SheetTitle>
                                <SheetDescription>
                                    {report.town.label} {report.year}
                                </SheetDescription>
                            </SheetHeader>
                            <FinanceReportGrid
                                report={report}
                                onSaved={() => setEntering(false)}
                            />
                        </SheetContent>
                    </Sheet>
                    <Sheet open={uploading} onOpenChange={setUploading}>
                        <SheetTrigger asChild>
                            <Button variant="outline">Upload Report</Button>
                        </SheetTrigger>
                        <SheetContent className="w-full overflow-y-auto sm:max-w-md">
                            <SheetHeader>
                                <SheetTitle>Upload Report</SheetTitle>
                                <SheetDescription>
                                    {report.town.label} {report.year}
                                </SheetDescription>
                            </SheetHeader>
                            <FinanceUploadForm
                                report={report}
                                onSaved={() => setUploading(false)}
                            />
                        </SheetContent>
                    </Sheet>
                </div>
            </header>
            <div
                className="flex flex-wrap items-baseline gap-x-3 gap-y-1 type-meta"
                aria-label="Sources"
            >
                <span>Source:</span>
                {sources.length ? (
                    sources.map((document) => (
                        <a
                            key={document.kind}
                            href={document.sourceUrl!}
                            target="_blank"
                            rel="noreferrer"
                            className="underline underline-offset-4"
                        >
                            {document.kind === 'executive_summary'
                                ? 'COA Executive Summary'
                                : document.label}
                            <span className="sr-only">
                                {' '}
                                (opens in a new tab)
                            </span>
                        </a>
                    ))
                ) : (
                    <span>Not available</span>
                )}
            </div>
            <div className="grid items-start gap-4 lg:grid-cols-[minmax(0,1fr)_16rem]">
                <section
                    aria-label={`Figures for fiscal year ${year}`}
                    className="flex min-w-0 flex-col gap-3"
                >
                    {report.issues.length > 0 && (
                        <details className="border-b pb-3">
                            <summary className="cursor-pointer type-body">
                                Source Checks
                            </summary>
                            <ul className="mt-3 flex flex-col gap-3">
                                {report.issues.map((issue, index) => (
                                    <li
                                        key={index}
                                        className="flex items-start gap-2 type-body"
                                    >
                                        <Badge
                                            variant={
                                                issue.severity === 'error'
                                                    ? 'destructive'
                                                    : 'outline'
                                            }
                                        >
                                            {issue.severity === 'error'
                                                ? 'Error'
                                                : 'Warning'}
                                        </Badge>
                                        <span>
                                            {issue.message}
                                            {issue.location && (
                                                <span className="ml-2 font-mono type-meta">
                                                    {issue.location}
                                                </span>
                                            )}
                                        </span>
                                    </li>
                                ))}
                            </ul>
                        </details>
                    )}
                    <FinanceFiguresTable
                        key={year}
                        report={report}
                        fiscalYear={year}
                    />
                </section>
                <WorkspaceFilters>
                    <label id="fiscal-year-label" className="type-label">
                        Fiscal Year
                    </label>
                    <Select value={String(year)} onValueChange={chooseYear}>
                        <SelectTrigger
                            aria-labelledby="fiscal-year-label"
                            className="w-full"
                        >
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            {years.map((item) => (
                                <SelectItem key={item} value={String(item)}>
                                    {item}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <FinanceUploads report={report} />
                </WorkspaceFilters>
            </div>
        </>
    );
}
