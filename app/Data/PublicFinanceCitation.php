<?php

namespace App\Data;

final readonly class PublicFinanceCitation
{
    public function __construct(
        public ?string $documentKey,
        public ?string $kind,
        public string $label,
        public string $town,
        public int $fiscalYear,
        public ?int $reportYear,
        public ?int $page,
        public ?string $location,
        public ?string $url,
    ) {}
}
