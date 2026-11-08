<?php

namespace App\Data;

final readonly class HomeOverview
{
    /**
     * @param  list<string>  $connectedTownCodes
     * @param  list<BarangayProfile>  $barangays
     */
    public function __construct(
        public int $townCount,
        public int $connectedTownCount,
        public array $connectedTownCodes,
        public int $businessTypeCount,
        public int $wishCount,
        public int $firstYear,
        public int $latestYear,
        public int $yearCount,
        public TownAtlas $atlas,
        public array $barangays,
        public FinanceMap $finances,
        public ?CommunityWishSnapshot $wishes,
    ) {}
}
