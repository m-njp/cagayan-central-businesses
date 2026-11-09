<?php

namespace App\Data;

final readonly class TownAtlas
{
    /**
     * @param  list<TownProfile>  $towns
     */
    public function __construct(
        public array $towns,
        public TownProfile $province,
    ) {}
}
