<?php

namespace App\Data;

/**
 * Every finance series and business trend a viewer may select, loaded once so selections stay local
 * (frontend.md, "Local Selection Contract"). The public variant carries sales bands only.
 */
final readonly class AnalyticsCatalog
{
    /**
     * @param  list<SelectOption>  $towns  the first option is the Sum of Reporting Towns
     * @param  list<AnalyticsMeasure>  $measures
     * @param  array{all: int, recent: int}  $periods
     * @param  array<string, array<string, FinancialHistory>>  $finances  town code, then measure; absent means not available
     * @param  array<string, mixed>  $guide
     * @param  array<string, array<string, mixed>>  $sources
     */
    public function __construct(
        public array $towns,
        public array $measures,
        public array $periods,
        public int $lastYear,
        public array $finances,
        public ?BusinessTrends $businesses,
        public array $guide = [],
        public array $sources = [],
    ) {}
}
