<?php

namespace App\Data;

/** The Data Entry hub. The review list is null for people who cannot verify. */
final readonly class DataEntry
{
    /**
     * @param  list<DataEntryDataset>  $datasets
     * @param  list<DataEntryReviewRow>|null  $review
     */
    public function __construct(
        public array $datasets,
        public ?array $review,
        public ?Pagination $pagination,
    ) {}
}
