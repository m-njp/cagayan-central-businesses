<?php

namespace App\Queries;

use App\Data\HomeOverview;
use App\Models\BusinessType;
use App\Models\Municipality;
use App\Models\Wish;

final class HomeOverviewQuery
{
    public function __construct(
        private TownAtlasQuery $atlas,
        private BarangayAtlasQuery $barangays,
        private FinanceMapQuery $finances,
        private SampleWishesQuery $wishes,
    ) {}

    public function get(): HomeOverview
    {
        /** @var object{total: int|string|null, connected: int|string|null}|null $towns */
        $towns = Municipality::query()
            ->toBase()
            ->selectRaw('count(*) as total, count(*) filter (where is_connected) as connected')
            ->first();

        /** @var list<string> $connectedTownCodes */
        $connectedTownCodes = Municipality::query()
            ->where('is_connected', true)
            ->orderBy('psgc_code')
            ->pluck('psgc_code')
            ->all();

        return new HomeOverview(
            townCount: (int) ($towns->total ?? 0),
            connectedTownCount: (int) ($towns->connected ?? 0),
            connectedTownCodes: $connectedTownCodes,
            businessTypeCount: BusinessType::query()->count(),
            wishCount: Wish::query()->count(),
            firstYear: (int) config('cagayan.data.first_year'),
            latestYear: (int) config('cagayan.data.latest_year'),
            yearCount: (int) config('cagayan.data.latest_year') - (int) config('cagayan.data.first_year') + 1,
            atlas: $this->atlas->get(),
            barangays: $this->barangays->get(),
            finances: $this->finances->get(),
            wishes: $this->wishes->get(),
        );
    }
}
