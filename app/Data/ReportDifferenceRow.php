<?php

namespace App\Data;

final readonly class ReportDifferenceRow
{
    /** @param array<int, ReportDifferenceCell|null> $cells Keyed by report year; null when no report was collected */
    public function __construct(
        public string $townCode,
        public string $town,
        public array $cells,
    ) {}
}
