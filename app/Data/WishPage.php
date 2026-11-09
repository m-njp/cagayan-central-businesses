<?php

namespace App\Data;

final readonly class WishPage
{
    /**
     * @param  list<SelectOption>  $towns
     * @param  list<SelectOption>  $types
     */
    public function __construct(
        public array $towns,
        public array $types,
        public ?string $defaultTown,
        public ?WishTally $tally,
        public ?MyWish $myWish,
        public ?string $nextWishAt,
        public int $cooldownDays,
        public int $editsPerWindow,
        public int $editWindowDays,
        public int $reasonMax,
        public int $specificBusinessMax,
        public string $formToken,
    ) {}
}
