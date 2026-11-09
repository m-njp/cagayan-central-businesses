<?php

namespace App\Queries;

use App\Data\BarangayProfile;
use App\Data\TownFigure;
use Illuminate\Support\Facades\DB;

/**
 * A town's barangays (Tuguegarao City unless another is named) with their published census population and M18
 * shade, computed in SQL (metrics.md, "Town figures"). Maps read this, not the boundary files, so a verified
 * count shows without a rebuild (Data Entry, Phase 4).
 */
final class BarangayAtlasQuery
{
    /**
     * Every barangay's published count and its shade within its own town, keyed by PSGC code, so a map drawn from
     * the boundary files on the client shows the database counts without a server visit.
     *
     * @return array<string, array{value: int, year: int, source: string|null, shade: int}>
     */
    public function counts(): array
    {
        /** @var list<object{psgc_code: string, population: int, population_year: int, population_source: string|null, shade: int}> $rows */
        $rows = DB::select(<<<'SQL'
            select psgc_code, population, population_year, population_source,
                ntile(?) over (partition by municipality_id order by population) as shade
            from barangays
            where population is not null
            SQL, [(int) config('cagayan.towns.shades')]);

        $counts = [];
        foreach ($rows as $row) {
            $counts[$row->psgc_code] = ['value' => (int) $row->population, 'year' => (int) $row->population_year,
                'source' => $row->population_source, 'shade' => (int) $row->shade];
        }

        return $counts;
    }

    /**
     * @return list<BarangayProfile>
     */
    public function get(?string $town = null): array
    {
        /** @var list<object{psgc_code: string, name: string, population: int|null, population_year: int|null, population_source: string|null, shade: int|null}> $rows */
        $rows = DB::select(<<<'SQL'
            select b.psgc_code, b.name, b.population, b.population_year, b.population_source,
                case when b.population is not null
                    then ntile(?) over (partition by b.population is null order by b.population) end as shade
            from barangays b
            join municipalities m on m.id = b.municipality_id
            where m.psgc_code = ?
            order by b.name
            SQL, [(int) config('cagayan.towns.shades'), $town ?? (string) config('cagayan.performance.demo_town')]);

        return array_map(fn (object $row) => new BarangayProfile(
            code: $row->psgc_code,
            name: $row->name,
            population: TownFigure::fromNullable($row->population, $row->population_year, $row->population_source),
            shade: $row->shade === null ? null : (int) $row->shade,
        ), $rows);
    }
}
