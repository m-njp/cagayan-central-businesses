<?php

namespace App\Data;

final readonly class CommunityWishSnapshot
{
    /** @param list<array{id: string, label: string, count: int}> $items */
    public function __construct(
        public string $townCode,
        public string $townName,
        public string $asOf,
        public string $period,
        public string $source,
        public array $items,
    ) {}
}
