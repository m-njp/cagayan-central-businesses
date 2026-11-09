// Mirrors of app/Data/DataEntry*.php, field for field.
import type { Pagination } from '@/types/workspace';

export type DataEntryDataset = {
    id: string;
    label: string;
    shownOn: string[];
    href: string;
    pending: number | null;
    lastChange: string | null;
    action: string | null;
    actionHref: string | null;
};

export type DataEntryReviewRow = {
    dataset: string;
    item: string;
    count: number;
    since: string;
    href: string;
};

export type DataEntry = {
    datasets: DataEntryDataset[];
    review: DataEntryReviewRow[] | null;
    pagination: Pagination | null;
};
