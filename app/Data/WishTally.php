<?php

namespace App\Data;

/**
 * M17. Wishes for a type, a named business, or both in one town.
 * registeredCount stays null until business records exist (roadmap F4),
 * and the page shows "Not available", never 0.
 */
final readonly class WishTally
{
    public function __construct(
        public string $townName,
        public ?string $typeName,
        public ?string $specificBusiness,
        public int $wishCount,
        public ?int $registeredCount,
    ) {}
}
