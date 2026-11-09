<?php

namespace App\Data;

/** A parser check result for one report, from data/towns/finances/issues. */
final readonly class FinanceIssue
{
    public function __construct(
        public string $severity,
        public string $kind,
        public ?string $line,
        public string $message,
        public ?string $location,
    ) {}
}
