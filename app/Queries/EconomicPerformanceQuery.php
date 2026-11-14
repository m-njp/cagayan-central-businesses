<?php

namespace App\Queries;

use App\Data\EconomicPerformance;

final class EconomicPerformanceQuery
{
    public function __construct(private AnalyticsCatalogQuery $catalog, private SampleWishesQuery $wishes) {}

    /** @param array{town: string, period: string, measure: string, dimension: string, group: string|null} $filters */
    public function get(array $filters): EconomicPerformance
    {
        $wishes = $this->wishes->get();

        return new EconomicPerformance(
            catalog: $this->catalog->get($filters['town']),
            filters: $filters,
            wishes: $wishes !== null ? [$wishes->townCode => $wishes] : [],
        );
    }
}
