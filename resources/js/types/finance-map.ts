import type {
    EconomicMetric,
    EconomicObservation,
    FinanceSource,
} from '@/types/economic-performance';

export type FinanceMap = {
    years: number[];
    latestYear: number | null;
    metrics: EconomicMetric[];
    observations: Record<
        string,
        Record<number, Record<string, EconomicObservation>>
    >;
    shades: Record<string, Record<number, Record<string, number>>>;
    sources: Record<string, FinanceSource>;
};
