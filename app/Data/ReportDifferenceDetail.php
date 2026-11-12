<?php

namespace App\Data;

final readonly class ReportDifferenceDetail
{
    /** @param list<ReportDifferenceItem> $items */
    public function __construct(
        public string $townCode,
        public string $town,
        public int $year,
        public ?string $sourceUrl,
        public int $pending,
        public int $verified,
        public int $corroborated,
        public array $items,
    ) {}
}
