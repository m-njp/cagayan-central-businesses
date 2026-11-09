<?php

namespace App\Data;

/** One town and report year in the finance review list, with figure counts by status. */
final readonly class FinanceReportRow
{
    public function __construct(
        public string $townCode,
        public string $town,
        public int $year,
        public int $pending,
        public int $verified,
        public int $rejected,
    ) {}
}
