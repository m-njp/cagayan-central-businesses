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
    line: string;
    label: string;
    fiscalYear: number;
    basis: 'actual' | 'budget_final';
    value: string;
    variant: string | null;
    source: string;
    location: string | null;
    status: FinanceFigureStatus;
    manual: boolean;
    enteredByYou: boolean;
    reviewedOn: string | null;
    reviewedBy: string | null;
    note: string | null;
    group: string;
    groupLabel: string;
    sourceUrl: string | null;
};

export type FinanceIssue = {
    severity: 'error' | 'warning';
    kind: string;
    line: string | null;
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
    kind: string;
    status: 'queued' | 'running' | 'done' | 'failed';
    statusLabel: string;
    figures: number;
    message: string | null;
    uploadedAt: string;
};

export type FinanceReport = {
    town: SelectOption;
    year: number;
    canReview: boolean;
    figures: FinanceFigureRow[];
    issues: FinanceIssue[];
    documents: FinanceDocumentLink[];
    lines: (SelectOption & { bases: ('actual' | 'budget_final')[] })[];
    uploads: FinanceUploadRow[];
    uploadKinds: SelectOption[];
    limits: {
        firstYear: number;
        lastYear: number;
        noteMax: number;
        valueMax: string;
        sourceUrlMax: number;
        reviewBatchMax: number;
        uploadMaxKb: number;
    };
};
