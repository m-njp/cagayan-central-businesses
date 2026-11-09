<?php

namespace App\Data;

/**
 * A town, or the whole province, on the home page map. A null figure means
 * "Not available", never 0.
 */
final readonly class TownProfile
{
    public function __construct(
        public string $code,
        public string $name,
        public string $kind,
        public ?TownFigure $population,
        public ?TownFigure $revenue,
        public ?TownFigure $expenditure,
        // M18 shade, 1 (fewest people) to cagayan.towns.shades. Null without a population.
        public ?int $shade,
        public ?TownFigure $barangayCount = null,
        public ?TownFigure $incomeClass = null,
        public ?TownFigure $povertyIncidence = null,
    ) {}
}
