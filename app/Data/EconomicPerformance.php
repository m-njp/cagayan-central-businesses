<?php

namespace App\Data;

/** The public Economy page: the public analytics catalog plus curated community wishes. */
final readonly class EconomicPerformance
{
    /**
     * @param  array{town: string, period: string, measure: string, dimension: string, group: string|null}  $filters
     * @param  array<string, CommunityWishSnapshot>  $wishes
     */
    public function __construct(
        public AnalyticsCatalog $catalog,
        public array $filters,
        public array $wishes,
    ) {}
}
