<?php

namespace App\Data;

/** One approved business on the public directory, kept to what a list row renders (P8, P15). */
final readonly class PublicBusinessRow
{
    /**
     * @param  int|null  $milestone  M34 Operating Milestone in years
     * @param  array{0: float, 1: float}|null  $point  Longitude and latitude, storefronts only
     */
    public function __construct(
        public string $id,
        public string $name,
        public string $category,
        public string $barangay,
        public bool $bir,
        public ?int $milestone,
        public ?array $point,
    ) {}
}
