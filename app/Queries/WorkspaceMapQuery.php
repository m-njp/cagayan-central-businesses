<?php

namespace App\Queries;

use App\Data\BarangayProfile;
use App\Data\TownFigure;
use App\Data\WorkspaceMap;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

final class WorkspaceMapQuery
{
    public function __construct(private TownAtlasQuery $atlas, private FinanceMapQuery $finances, private BarangayAtlasQuery $populations) {}

    public function get(?string $town, bool $drill): WorkspaceMap
    {
        $census = $town && $drill ? $this->census($town) : null;
        $counts = $this->populations->counts();
        // The map file names the barangays and draws them; the published database count wins over the file's census
        // value, so a verified count shows without a rebuild (D43).
        $barangays = array_map(function (array $f) use ($census, $counts) {
            $count = $counts[$f['properties']['psgc_code']] ?? null;

            return new BarangayProfile(
                $f['properties']['psgc_code'],
                $f['properties']['name'],
                $count === null
                    ? TownFigure::fromNullable($f['properties']['population'], $census['year'], $census['source'])
                    : TownFigure::fromNullable($count['value'], $count['year'], $count['source']),
                $count['shade'] ?? $f['properties']['shade'],
            );
        }, $census['features'] ?? []);

        return new WorkspaceMap($this->atlas->get(), $barangays, $this->finances->get(), $counts);
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
