// Labels and token dots shared by the Capitol and public registry screens (D40, metrics.md M32).
export const BUSINESS_STATUS_DOTS: Record<string, string> = {
    new: 'bg-status-new',
    renewed: 'bg-status-renewed',
    not_renewed: 'bg-status-not-renewed',
    closed: 'bg-status-closed',
};

export const BUSINESS_STATUS_LABELS: Record<string, string> = {
    new: 'New',
    renewed: 'Renewed',
    not_renewed: 'Not Renewed',
    closed: 'Closed',
};

export const PERMIT_STATUS: Record<string, { label: string; dot: string }> = {
    valid: { label: 'Permit Valid', dot: 'bg-status-new' },
    expiring: { label: 'Permit Expiring', dot: 'bg-status-not-renewed' },
    expired: { label: 'Permit Expired', dot: 'bg-status-closed' },
    none: { label: 'No Permit on File', dot: 'bg-muted-foreground' },
};

// The words for an entered figure everywhere it appears: finance lines and census counts.
export const FIGURE_STATUS: Record<string, string> = {
    pending: 'Pending',
    verified: 'Verified',
    rejected: 'Returned',
    superseded: 'Superseded',
};

export const FIGURE_STATUS_DOTS: Record<string, string> = {
    pending: 'bg-muted-foreground',
    verified: 'bg-status-new',
    rejected: 'bg-status-closed',
    superseded: 'bg-muted-foreground',
};

export const RECORD_STATUS: Record<string, string> = {
    draft: 'Draft',
    submitted: 'Pending',
    returned: 'Returned',
    approved: 'Verified',
};
