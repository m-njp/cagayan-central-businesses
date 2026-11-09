<?php

namespace App\Data;

final readonly class BusinessCategoryRow
{
    /** @param int $color Slot in the --category-N palette */
    public function __construct(
        public string $code,
        public string $name,
        public int $color,
        public int $types,
        public ?int $registered,
    ) {}
}
