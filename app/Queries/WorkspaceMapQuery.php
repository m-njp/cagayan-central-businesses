<?php

namespace App\Queries;

use App\Data\WorkspaceMap;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

final class WorkspaceMapQuery
{
    public function __construct(private TownAtlasQuery $atlas, private FinanceMapQuery $finances, private BarangayAtlasQuery $populations) {}

    public function get(?string $town, bool $drill): WorkspaceMap
    {
        // Boundaries come from the map file; counts and shades come from the database.
        $barangays = $town && $drill && $this->census($town) !== null ? $this->populations->get($town) : [];

        return new WorkspaceMap($this->atlas->get(), $barangays, $this->finances->get(), $this->populations->counts());
    }

    /** @return array{year: int, source: string, features: list<array{properties: array{psgc_code: string, name: string, population: int|null, shade: int|null}}>} | null */
    public function census(string $town): ?array
    {
        $path = base_path('data/barangays/maps/'.$town.'.json');

        /** @var array{year: int, source: string, features: list<array{properties: array{psgc_code: string, name: string, population: int|null, shade: int|null}}>} | null */
        return is_file($path)
            ? Cache::rememberForever('workspace.census.'.$town.'.'.filemtime($path), fn () => json_decode(File::get($path), true, flags: JSON_THROW_ON_ERROR))
            : null;
    }
}
