<?php

namespace App\Data;

final readonly class EconomicMetric
{
    public function __construct(
        public string $id,
        public string $label,
        public string $unit,
        public string $basis,
    ) {}
}
