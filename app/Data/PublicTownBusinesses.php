<?php

namespace App\Data;

/** Every approved business in one town, with that town's barangays. */
final readonly class PublicTownBusinesses
{
    /**
     * @param  list<PublicBusinessRow>  $rows
     * @param  list<SelectOption>  $barangays
     */
    public function __construct(
        public string $town,
        public string $townName,
        public array $rows,
        public array $barangays,
    ) {}
}
