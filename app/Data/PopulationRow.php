<?php

namespace App\Data;

/** One place in the population grid: the town itself ("town") or a barangay by PSGC code. */
final readonly class PopulationRow
{
    public function __construct(
        public string $place,
        public string $name,
        public ?int $value,
        public ?string $status,
        public ?string $source,
        public ?string $pendingId,
        public bool $enteredByYou,
    ) {}
}
