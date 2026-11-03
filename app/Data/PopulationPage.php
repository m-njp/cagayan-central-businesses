<?php

namespace App\Data;

/** One town's census counts for one year, as the population grid and its review read them. */
final readonly class PopulationPage
{
    /**
     * @param  list<SelectOption>  $towns
     * @param  list<int>  $years
     * @param  list<PopulationRow>  $rows
     * @param  array{valueMax: int, changesMax: int}  $limits
     */
    public function __construct(
        public array $towns,
        public SelectOption $town,
        public int $year,
        public array $years,
        public array $rows,
        public bool $canReview,
        public array $limits,
    ) {}
}
