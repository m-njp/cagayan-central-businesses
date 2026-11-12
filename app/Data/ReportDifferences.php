<?php

namespace App\Data;

/** Reporting differences in every municipality's Executive Summaries, by report year. */
final readonly class ReportDifferences
{
    /**
     * @param  list<int>  $years
     * @param  list<ReportDifferenceRow>  $rows
     */
    public function __construct(
        public array $years,
        public array $rows,
    ) {}
}
