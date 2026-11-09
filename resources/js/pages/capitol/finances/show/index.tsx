import { Head, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { FinanceFiguresTable } from '@/components/capitol/finance-figures-table';
import { PageHeader } from '@/components/capitol/page-header';
import { FinanceReportGrid } from '@/components/capitol/finance-report-grid';
import { FinanceUploads } from '@/components/capitol/finance-upload';
import { FinanceParsingTask } from '@/components/capitol/finance-parsing-task';
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
import { Surface } from '@/components/ui/surface';
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
    const [uploadId, setUploadId] = useState<string | null>(null);
    const openUpload = (id: string | null) => {
        setUploadId(id);
        setUploading(true);
    };
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
            <PageHeader title={report.town.label}>
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
                <Button variant="outline" onClick={() => openUpload(null)}>
                    Upload Report
                </Button>
            </PageHeader>
            <FinanceParsingTask
                report={report}
                id={uploadId}
                open={uploading}
                onOpenChange={setUploading}
                onUploaded={openUpload}
            />
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
                <aside className="flex min-w-0 flex-col gap-2 lg:col-start-2 lg:row-start-1">
                    <Surface padded aria-label="Filters">
                        <div className="flex items-center justify-between gap-2">
                            <span id="fiscal-year-label" className="type-label">
                                Fiscal Year
                            </span>
                            <Select
                                value={String(year)}
                                onValueChange={chooseYear}
                            >
                                <SelectTrigger
                                    variant="quiet"
                                    size="sm"
                                    aria-labelledby="fiscal-year-label"
                                    className="font-mono tabular-nums"
                                >
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {years.map((item) => (
                                        <SelectItem
                                            key={item}
                                            value={String(item)}
                                        >
                                            {item}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                    </Surface>
                    <FinanceUploads
                        report={report}
                        onOpen={openUpload}
                        watching={uploading && uploadId !== null}
                    />
                </aside>
                <section
                    aria-label={`Figures for fiscal year ${year}`}
                    className="flex min-w-0 flex-col gap-3 lg:col-start-1 lg:row-start-1"
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
            </div>
        </>
    );
}
