<?php

namespace App\Data;

final readonly class WorkspaceMap
{
    /**
     * @param  list<BarangayProfile>  $barangays
     * @param  array<string, array{value: int, year: int, source: string|null, shade: int}>  $counts  Published barangay counts by PSGC code
     */
    public function __construct(public TownAtlas $atlas, public array $barangays, public FinanceMap $finances, public array $counts) {}
}
