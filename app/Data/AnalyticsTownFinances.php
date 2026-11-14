<?php

namespace App\Data;

/**
 * One town's finance series and the citations they use, loaded the first time a viewer selects the town
 * (frontend.md, "Local Selection Contract"). Public data only, as in AnalyticsCatalog.
 */
final readonly class AnalyticsTownFinances
{
    /**
     * @param  array<string, array<string, FinancialHistory>>  $finances  town code, then measure; empty when the town has no signed-off figure
     * @param  array<string, array<string, mixed>>  $sources
     */
    public function __construct(
        public array $finances,
        public array $sources,
    ) {}
}
