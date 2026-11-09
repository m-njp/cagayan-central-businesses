<?php

namespace App\Data;

final readonly class FinanceDocumentLink
{
    public function __construct(
        public string $kind,
        public string $label,
        public bool $available,
        public ?string $sourceUrl,
    ) {}
}
