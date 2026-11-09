<?php

namespace App\Data;

/** Something waiting for an Admin: a town's finance report or one submitted business. */
final readonly class DataEntryReviewRow
{
    public function __construct(
        public string $dataset,
        public string $item,
        public int $count,
        public string $since,
        public string $href,
    ) {}
}
