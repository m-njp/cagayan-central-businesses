<?php

namespace App\Data;

/**
 * Simulated business counts by industry or Business Category for the connected town (M28).
 */
final readonly class BusinessTrends
{
    /**
     * @param  array{industry: list<SelectOption>, category: list<SelectOption>}  $groups
     * @param  array<string, FinancialHistory>  $datasets  keyed "industry:A", "category:fishery"
     */
    public function __construct(
        public string $townCode,
        public string $townName,
        public array $groups,
        public array $datasets,
    ) {}
}
