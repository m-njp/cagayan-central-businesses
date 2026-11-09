<?php

namespace App\Data;

/** What the report grid shows for one line and year before anything is typed. */
final readonly class FinanceEntryCell
{
    public function __construct(
        public string $line,
        public int $fiscalYear,
        public string $basis,
        public string $value,
        public string $status,
    ) {}
}
