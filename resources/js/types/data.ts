// Mirrors of the PHP Data objects in app/Data, field for field.
import type { CommunityWishSnapshot } from '@/types/economic-performance';
import type { FinanceMap } from '@/types/finance-map';

export type SelectOption = {
    value: string;
    label: string;
};

export type HomeOverview = {
    townCount: number;
    connectedTownCount: number;
    connectedTownCodes: string[];
    businessTypeCount: number;
    wishCount: number;
    firstYear: number;
    latestYear: number;
    yearCount: number;
    atlas: TownAtlas;
    barangays: BarangayProfile[];
    finances: FinanceMap;
    wishes: CommunityWishSnapshot | null;
};

export type WishTally = {
    townName: string;
    typeName: string | null;
    specificBusiness: string | null;
    wishCount: number;
    // Null means "Not available", never zero.
    registeredCount: number | null;
};

export type MyWish = {
    id: number;
    townCode: string;
    townName: string;
    typeCode: string | null;
    typeName: string | null;
    specificBusiness: string | null;
    reason: string | null;
    editsLeft: number;
    nextEditAt: string | null;
};

export type WishPage = {
    towns: SelectOption[];
    types: SelectOption[];
    defaultTown: string | null;
    tally: WishTally | null;
    myWish: MyWish | null;
    nextWishAt: string | null;
    cooldownDays: number;
    editsPerWindow: number;
    editWindowDays: number;
    reasonMax: number;
    specificBusinessMax: number;
    formToken: string;
};

export type TownFigure = {
    value: string;
    year: number;
    // Null for province totals, which are sums.
    sourceUrl: string | null;
};

// A town or the whole province. Null figures read "Not available", never 0.
export type TownProfile = {
    code: string;
    name: string;
    kind: 'city' | 'municipality' | 'province';
    barangayCount: TownFigure | null;
    incomeClass: TownFigure | null;
    povertyIncidence: TownFigure | null;
    population: TownFigure | null;
    revenue: TownFigure | null;
    expenditure: TownFigure | null;
    // Map shade by population, 1 to 5 (metrics.md, M18).
    shade: number | null;
};

// A Tuguegarao City barangay. Null population reads "Not available", never 0.
export type BarangayProfile = {
    code: string;
    name: string;
    population: TownFigure | null;
    shade: number | null;
};

export type TownAtlas = {
    towns: TownProfile[];
    province: TownProfile;
};
