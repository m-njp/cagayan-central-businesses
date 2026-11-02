import type { SelectOption, TownAtlas, BarangayProfile } from '@/types/data';
import type { FinanceMap } from '@/types/finance-map';

export type Workspace = {
    displayName: string;
    role: string;
    canViewWishes: boolean;
    canViewLogs: boolean;
    canManageCategories: boolean;
    isBirthday: boolean;
    idleExpiresAt: number;
    absoluteExpiresAt: number;
    warningSeconds: number;
    serverNow: number;
    activityIntervalSeconds: number;
};
export type WorkspaceMap = {
    atlas: TownAtlas;
    barangays: BarangayProfile[];
    finances: FinanceMap;
};
export type WorkspaceTable = {
    rows: Record<string, string | number | null>[];
    pagination: Pagination;
    towns: SelectOption[];
    categories: SelectOption[];
    filters: Record<string, string | null>;
};
export type Pagination = {
    previous: string | null;
    next: string | null;
    page: number;
    lastPage: number;
    total: number;
    from: number | null;
    to: number | null;
    links: { page: number; url: string }[];
};
export type AccountProfile = {
    displayName: string;
    email: string;
    role: string;
    birthdayMonth: number | null;
    birthdayDay: number | null;
    displayNameMax: number;
};

export type BusinessTypeRow = {
    code: string;
    name: string;
    category: string;
    industry: string;
    industryCode: string;
    registered: number | null;
};

export type BusinessCategoryRow = {
    code: string;
    name: string;
    color: number;
    types: number;
    registered: number | null;
};

export type BusinessCategories = {
    types: BusinessTypeRow[];
    categories: BusinessCategoryRow[];
    industries: SelectOption[];
    year: number | null;
    typeNameMax: number;
    categoryNameMax: number;
    colors: number;
    pageSize: number;
};

export type BusinessDirectoryRow = {
    publicId: string;
    name: string;
    type: string;
    category: string;
    barangay: string;
    yearEstablished: number;
    status: string | null;
    recordStatus: string;
    permitStatus: string;
};

export type BusinessDirectory = {
    rows: BusinessDirectoryRow[];
    pagination: Pagination;
    categories: { code: string; name: string; color: number }[];
    types: SelectOption[];
    industries: SelectOption[];
    statuses: SelectOption[];
    barangays: SelectOption[];
    recordCounts: Partial<Record<string, number>>;
    filters: {
        q: string | null;
        record: string | null;
        barangay: string | null;
        category: string | null;
        type: string | null;
        industry: string | null;
        status: string | null;
        sort: string;
        dir: string;
    };
    canAdd: boolean;
};

export type BusinessProfile = {
    publicId: string;
    name: string;
    type: string;
    typeCode: string;
    category: { code: string; name: string; color: number };
    barangay: string;
    barangayCode: string;
    town: string;
    townCode: string;
    description: string;
    yearEstablished: number;
    isStorefront: boolean;
    businessPhone: string | null;
    businessEmail: string | null;
    birVerifiedOn: string | null;
    permitStatus: string;
    permitValidUntil: string | null;
    streak: number;
    milestone: number | null;
    location: { lng: number; lat: number } | null;
    recordStatus: string;
    submittedBy: string | null;
    submittedAt: string | null;
    reviewedBy: string | null;
    reviewedAt: string | null;
    admin: {
        ownerName: string;
        ownerContact: string;
        streetAddress: string | null;
        ownership: string;
        size: string;
        employees: number;
        registrationBody: string | null;
        registrationNumber: string | null;
        registrationValidUntil: string | null;
        tin: string | null;
        rdoCode: string | null;
        reviewNote: string | null;
    } | null;
    permits: {
        year: number;
        number: string | null;
        validFrom: string;
        validUntil: string;
    }[];
    documents: {
        publicId: string;
        kind: string;
        label: string;
        name: string;
        issuedOn: string | null;
        expiresOn: string | null;
    }[];
    history: { year: number; status: string }[];
    can: { edit: boolean; review: boolean };
    reviewNoteMax: number;
};

export type BusinessForm = {
    publicId: string | null;
    values: Record<string, string | number | boolean | null> | null;
    reviewNote: string | null;
    categories: { code: string; name: string; types: SelectOption[] }[];
    towns: SelectOption[];
    barangays: { value: string; label: string; town: string }[];
    choices: {
        ownership: SelectOption[];
        size: SelectOption[];
        registration: SelectOption[];
        documents: SelectOption[];
    };
    documents: { label: string; name: string }[];
    limits: {
        businessNameMax: number;
        descriptionMax: number;
        addressMax: number;
        ownerNameMax: number;
        emailMax: number;
        registrationNumberMax: number;
        permitNumberMax: number;
        documentsMax: number;
        documentMaxKb: number;
    };
};
