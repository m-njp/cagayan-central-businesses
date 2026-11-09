<?php

namespace App\Data;

final readonly class FinanceReviewList
{
    /**
     * @param  list<FinanceReportRow>  $rows
     * @param  list<SelectOption>  $towns
     * @param  list<SelectOption>  $years
     * @param  array{town: string|null, year: string|null, status: string|null}  $filters
     */
    public function __construct(
        public array $rows,
        public Pagination $pagination,
        public array $towns,
        public array $years,
        public array $filters,
    ) {}
}
