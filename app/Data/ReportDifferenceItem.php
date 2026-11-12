<?php

namespace App\Data;

/** One finding: "report" (the document disagrees with itself), "between_reports" or "reading" (the parser's limit). */
final readonly class ReportDifferenceItem
{
    public function __construct(
        public string $family,
        public string $severity,
        public string $kind,
        public ?string $line,
        public ?int $fiscalYear,
        public string $message,
        public ?string $sourceUrl,
    ) {}
}
