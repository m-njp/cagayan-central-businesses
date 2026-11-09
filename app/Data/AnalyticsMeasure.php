<?php

namespace App\Data;

final readonly class AnalyticsMeasure
{
    /**
     * @param  list<string>|null  $towns  town codes with a figure for this measure; null when every town has one
     */
    public function __construct(
        public string $value,
        public string $label,
        public ?array $towns,
    ) {}
}
