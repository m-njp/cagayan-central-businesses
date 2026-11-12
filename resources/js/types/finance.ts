// Mirrors of the finance review Data objects in app/Data, field for field.
import type { SelectOption } from '@/types/data';
import type { Pagination } from '@/types/workspace';

export type FinanceReportRow = {
    townCode: string;
    town: string;
    year: number;
    pending: number;
    verified: number;
    rejected: number;
};

export type FinanceReviewList = {
    rows: FinanceReportRow[];
    pagination: Pagination;
    towns: SelectOption[];
    years: SelectOption[];
    filters: {
        town: string | null;
        year: string | null;
        status: string | null;
    };
};

export type FinanceFigureStatus =
    | 'pending'
    | 'verified'
    | 'rejected'
    | 'superseded';

export type FinanceFigureRow = {
    id: string;
    label: string;
    fiscalYear: number;
    basis: 'actual' | 'budget_final';
    value: string;
    source: string;
    location: string | null;
    status: FinanceFigureStatus;
    enteredByYou: boolean;
    reviewedBy: string | null;
    note: string | null;
    group: string;
    groupLabel: string;
    sourceUrl: string | null;
};

export type FinanceIssue = {
    severity: 'error' | 'warning';
    kind: string;
    message: string;
    location: string | null;
};

export type FinanceDocumentLink = {
    kind: string;
    label: string;
    available: boolean;
    sourceUrl: string | null;
};

export type FinanceUploadRow = {
    id: string;
    name: string;
    status: 'queued' | 'running' | 'done' | 'failed';
    statusLabel: string;
    figures: number;
    uploadedAt: string;
};

export type FinanceUploadTask = {
    id: string;
    name: string;
    status: FinanceUploadRow['status'];
    saved: number;
    message: string | null;
    pollMs: number;
    // Only what the parsing overlay reads; the server snapshot carries more.
    progress: {
        attempt: string;
        current: number | null;
        total: number | null;
        found: number | null;
        confidence?: number | null;
        startedAt: string | null;
        updatedAt: string;
        finishedAt: string | null;
        rows: {
            line: string;
            label: string;
            value: string;
            location: string;
        }[];
        issues: { severity: string }[];
        activity: {
            sequence: number;
            kind: 'stage' | 'scan' | 'figure' | 'issue';
            line?: string;
            message: string;
            location: string | null;
            at: string;
        }[];
    };
};

export type FinanceReport = {
    town: SelectOption;
    year: number;
    canReview: boolean;
    figures: FinanceFigureRow[];
    issues: FinanceIssue[];
    documents: FinanceDocumentLink[];
    lines: (SelectOption & {
        bases: ('actual' | 'budget_final')[];
        group: string;
        groupLabel: string;
    })[];
    entryYears: number[];
    cells: {
        line: string;
        fiscalYear: number;
        basis: 'actual' | 'budget_final';
        value: string;
        status: FinanceFigureStatus;
    }[];
    uploads: FinanceUploadRow[];
    uploadKinds: SelectOption[];
    limits: {
        lastYear: number;
        noteMax: number;
        reviewBatchMax: number;
        changesMax: number;
    };
};

export type ReportDifferenceFamily = 'report' | 'between_reports' | 'reading';

export type ReportDifferenceCell = {
    report: number;
    between: number;
    reading: number;
    pending: number;
    verified: number;
};

export type ReportDifferences = {
    years: number[];
    rows: {
        townCode: string;
        town: string;
        cells: Record<string, ReportDifferenceCell | null>;
    }[];
};

export type ReportDifferenceDetail = {
    townCode: string;
    town: string;
    year: number;
    sourceUrl: string | null;
    pending: number;
    verified: number;
    corroborated: number;
    items: {
        family: ReportDifferenceFamily;
        severity: 'error' | 'warning' | 'info';
        kind: string;
        line: string | null;
        fiscalYear: number | null;
        message: string;
        sourceUrl: string | null;
    }[];
};
