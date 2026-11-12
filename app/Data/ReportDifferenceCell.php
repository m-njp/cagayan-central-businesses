<?php

namespace App\Data;

/** Counts for one report: its findings by family, and its figures by review status. */
final readonly class ReportDifferenceCell
{
    public function __construct(
        public int $report,
        public int $between,
        public int $reading,
        public int $pending,
        public int $verified,
    ) {}
}
