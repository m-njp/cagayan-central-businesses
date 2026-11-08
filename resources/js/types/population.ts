// Mirrors of app/Data/PopulationPage.php and PopulationRow.php, field for field.
import type { SelectOption } from '@/types/data';

export type PopulationRow = {
    place: string;
    name: string;
    value: number | null;
    status: 'pending' | 'verified' | null;
    source: string | null;
    pendingId: string | null;
    enteredByYou: boolean;
    returnedNote: string | null;
};

export type PopulationPage = {
    towns: SelectOption[];
    town: SelectOption;
    year: number;
    years: number[];
    rows: PopulationRow[];
    canReview: boolean;
    limits: {
        valueMax: number;
        changesMax: number;
        noteMax: number;
        sourceUrlMax: number;
    };
};
