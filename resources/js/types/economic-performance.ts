import type { SelectOption } from '@/types/data';

export type AnalyticsFilters = {
    town: string;
    period: 'all' | 'recent' | '2y' | '3y' | '5y';
    presentation: 'statement' | 'summary' | 'trends';
    year: number | null;
    measure: string;
    metric: string | null;
    dimension: 'industry' | 'category';
    group: string | null;
};

export type PerformanceFilters = AnalyticsFilters & {};

export type EconomicObservation = {
    state: 'shown' | 'unavailable' | 'hidden';
    value: string | null;
    comparable: boolean;
    change: string | null;
    percentageChange: number | null;
    comparisonYear: number | null;
    reason?:
        | 'not_published'
        | 'not_reported'
        | 'not_collected'
        | 'not_loaded'
        | 'pending_review'
        | 'small_group'
        | 'dominance'
        | 'complementary'
        | null;
    band?: string | null;
    coverage?: { reporting: number; total: number; matched: number } | null;
    restatedIn?: number | null;
    sourceRefs?: string[];
};

export type MetricUnit = 'php' | 'percent' | 'count';

export type EconomicMetric = {
    id: string;
    label: string;
    unit: MetricUnit;
    basis: string;
};

export type FinancialHistory = {
    metrics: EconomicMetric[];
    rows: { year: number; observations: Record<string, EconomicObservation> }[];
    axisMaximum: string;
    note: string | null;
};

export type WishAggregate = { id: string; label: string; count: number };

export type CommunityWishSnapshot = {
    townCode: string;
    townName: string;
    asOf: string;
    period: string;
    source: string;
    items: WishAggregate[];
};

export type BusinessTrends = {
    townCode: string;
    townName: string;
    groups: Record<AnalyticsFilters['dimension'], SelectOption[]>;
    datasets: Record<string, FinancialHistory>;
};

export type AnalyticsCatalog = {
    towns: SelectOption[];
    measures: AnalyticsMeasure[];
    periods: { all: number; recent: number };
    lastYear: number;
    finances: Record<string, Record<string, FinancialHistory>>;
    businesses: BusinessTrends | null;
    guide: AnalyticsGuide;
    sources: Record<string, FinanceSource>;
};

export type EconomicPerformance = {
    catalog: AnalyticsCatalog;
    filters: PerformanceFilters;
    wishes: Record<string, CommunityWishSnapshot>;
};

/** town codes with a figure for the measure; null when every town has one */
export type AnalyticsMeasure = SelectOption & { towns: string[] | null };

export type MeasureDefinition = {
    id: string;
    label: string;
    meaning: string;
    formula: string;
    inputs: string[];
};

export type AnalyticsGuide = {
    introduction: string;
    topics: {
        id: string;
        title: string;
        summary: string;
        metrics: MeasureDefinition[];
    }[];
    notes: { id: string; title: string; text: string }[];
};

/** The one evidence entry behind a published figure; observations point to it by key. */
export type FinanceSource = {
    kind: 'executive_summary' | 'fs_workbook' | 'annex_workbook' | null;
    label: string;
    town: string;
    fiscalYear: number;
    reportYear: number | null;
    page: number | null;
    location: string | null;
    inputs: { location: string; value: string }[];
    url: string | null;
};
