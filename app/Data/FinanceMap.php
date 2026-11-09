<?php

namespace App\Data;

final readonly class FinanceMap
{
    /**
     * @param  list<int>  $years
     * @param  list<EconomicMetric>  $metrics
     * @param  array<string, array<int, array<string, EconomicObservation>>>  $observations
     * @param  array<string, array<int, array<string, int>>>  $shades
     * @param  array<string, array<string, mixed>>  $sources
     */
    public function __construct(
        public array $years,
        public ?int $latestYear,
        public array $metrics,
        public array $observations,
        public array $shades,
        public array $sources,
    ) {}
}
