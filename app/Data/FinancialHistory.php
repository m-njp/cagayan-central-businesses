<?php

namespace App\Data;

final readonly class FinancialHistory
{
    /**
     * @param  list<EconomicMetric>  $metrics
     * @param  list<array{year: int, observations: array<string, EconomicObservation>}>  $rows
     * @param  string|null  $note  the fixed scope phrase of a business series (privacy-rules.md, "Words on screen"); finance series carry none
     */
    public function __construct(
        public array $metrics,
        public array $rows,
        public string $axisMaximum,
        public ?string $note = null,
    ) {}
}
