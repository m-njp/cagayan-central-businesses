<?php

namespace App\Data;

final readonly class ReportDifferenceDetail
{
    /**
     * @param  list<array{label: string, url: string}>  $sources  every document of the report that can be opened or downloaded
     * @param  list<ReportDifferenceItem>  $items
     */
    public function __construct(
        public string $townCode,
        public string $town,
        public int $year,
        public ?string $sourceUrl,
        public array $sources,
        public int $pending,
        public int $verified,
        public int $corroborated,
        public array $items,
    ) {}
}
