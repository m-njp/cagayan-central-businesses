<?php

namespace App\Data;

/**
 * The finance series of the Sum of Reporting Towns and the selected town, plus every business trend. Other towns in
 * financeTowns load once on demand (frontend.md, "Local Selection Contract"). The public variant carries sales bands only.
 */
final readonly class AnalyticsCatalog
{
    /**
     * @param  list<SelectOption>  $towns  the first option is the Sum of Reporting Towns
     * @param  list<AnalyticsMeasure>  $measures
     * @param  array{all: int, recent: int}  $periods
     * @param  list<string>  $financeTowns  town codes with at least one signed-off figure; any other town is not available
     * @param  array<string, array<string, FinancialHistory>>  $finances  town code, then measure; a town in financeTowns but absent here loads on demand
     * @param  array<string, mixed>  $guide
     * @param  array<string, array<string, mixed>>  $sources
     */
    public function __construct(
        public array $towns,
        public array $measures,
        public array $periods,
        public int $lastYear,
        public array $financeTowns,
        public array $finances,
        public ?BusinessTrends $businesses,
        public array $guide = [],
        public array $sources = [],
    ) {}
}
