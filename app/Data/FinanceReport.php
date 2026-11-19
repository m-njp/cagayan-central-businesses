<?php

namespace App\Data;

/** Everything the review screen needs for one town and report year. */
final readonly class FinanceReport
{
    /**
     * @param  list<FinanceFigureRow>  $figures
     * @param  list<FinanceIssue>  $issues
     * @param  list<FinanceDocumentLink>  $documents
     * @param  list<FinanceLineOption>  $lines
     * @param  list<int>  $entryYears
     * @param  list<FinanceEntryCell>  $cells
     * @param  list<FinanceUploadRow>  $uploads
     * @param  list<SelectOption>  $uploadKinds
     * @param  array{lastYear: int, noteMax: int, reviewBatchMax: int, changesMax: int}  $limits
     */
    public function __construct(
        public SelectOption $town,
        public int $year,
        public bool $canReview,
        public array $figures,
        public array $issues,
        public array $documents,
        public array $lines,
        public array $entryYears,
        public array $cells,
        public array $uploads,
        public array $uploadKinds,
        public array $limits,
    ) {}
}
