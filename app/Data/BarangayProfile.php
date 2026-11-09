<?php

namespace App\Data;

/**
 * A Tuguegarao City barangay on the About the Data map. A null population
 * means "Not available", never 0.
 */
final readonly class BarangayProfile
{
    public function __construct(
        public string $code,
        public string $name,
        public ?TownFigure $population,
        // M18 shade among the city's barangays. Null without a population.
        public ?int $shade,
    ) {}
}
