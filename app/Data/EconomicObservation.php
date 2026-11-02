<?php

namespace App\Data;

/**
 * One value in a yearly series. A missing value carries a reason; a public sales value carries a band instead (P7).
 */
final readonly class EconomicObservation
{
    /**
     * @param  array{reporting: int, total: int, matched: int}|null  $coverage  Sum of Reporting Towns only (M27)
     * @param  list<string>  $sourceRefs
     */
    public function __construct(
        public string $state,
        public ?string $value,
        public bool $comparable,
        public ?string $change,
        public ?float $percentageChange,
        public ?int $comparisonYear,
        public ?string $reason = null,
        public ?string $band = null,
        public ?array $coverage = null,
        public ?int $restatedIn = null,
        public array $sourceRefs = [],
    ) {}
}
