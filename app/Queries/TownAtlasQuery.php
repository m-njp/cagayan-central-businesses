<?php

namespace App\Queries;

use App\Data\TownAtlas;
use App\Data\TownFigure;
use App\Data\TownProfile;
use Illuminate\Support\Facades\DB;

/**
 * Town figures for the home page map: the newest year of population, revenue,
 * and expenditure, plus the M18 shade, all computed in SQL
 * (metrics.md, "Town figures"). Revenue and expenditure are the newest signed-off
 * Government Revenue and Government Expenses in the finance ledger (D35).
 */
final class TownAtlasQuery
{
    private const array MEASURES = ['population', 'revenue', 'expenditure', 'barangay_count', 'income_class', 'poverty_incidence'];

    private const array FACT_MEASURES = ['population', 'barangay_count', 'income_class', 'poverty_incidence'];

    public function get(): TownAtlas
    {
        $measures = "'".implode("', '", self::FACT_MEASURES)."'";
        $pivot = collect(self::MEASURES)->map(fn (string $measure) => <<<SQL
            max(l.value) filter (where l.measure = '{$measure}') as {$measure},
            max(l.year) filter (where l.measure = '{$measure}') as {$measure}_year,
            max(l.source_url) filter (where l.measure = '{$measure}') as {$measure}_source
            SQL)->implode(",\n");

        // Population/count totals require complete coverage of the same reference year.
        $totals = collect(['population', 'barangay_count'])->map(fn (string $measure) => <<<SQL
            case when count({$measure}) over () = count(*) over () and min({$measure}_year) over () = max({$measure}_year) over () then sum({$measure}) over () end as total_{$measure},
            max({$measure}_year) over () as total_{$measure}_year,
            case when count({$measure}_source) over () = count(*) over () and min({$measure}_source) over () = max({$measure}_source) over () then max({$measure}_source) over () end as total_{$measure}_source
            SQL)->implode(",\n");

        /** @var list<object> $rows */
        $rows = DB::select(<<<SQL
            with latest as (
                select distinct on (municipality_id, measure) municipality_id, measure::text as measure, year, value, source_url
                from town_facts
                where checked_on is not null and source_url is not null
                    and measure in ({$measures})
                order by municipality_id, measure, year desc
            ),
            finances as (
                select distinct on (f.municipality_id, f.line_code) f.municipality_id,
                    case f.line_code when 'performance.revenue' then 'revenue' else 'expenditure' end as measure,
                    f.fiscal_year as year, f.value, coalesce(d.source_url, f.source_url) as source_url
                from finance_figures f
                left join finance_documents d on d.id = f.finance_document_id
                where f.status = 'verified' and f.basis = 'actual'
                    and f.line_code in ('performance.revenue', 'performance.expenses')
                order by f.municipality_id, f.line_code, f.fiscal_year desc
            ),
            facts as (
                select m.psgc_code, m.name, m.kind, {$pivot}
                from municipalities m
                left join (select * from latest union all select * from finances) l on l.municipality_id = m.id
                group by m.id
            )
            select *,
                case when population is not null
                    then ntile(?) over (partition by population is null order by population) end as shade,
                {$totals}
            from facts
            order by name
            SQL, [(int) config('cagayan.towns.shades')]);

        return new TownAtlas(
            towns: array_map(fn (object $row) => $this->profile((array) $row), $rows),
            province: $this->province(isset($rows[0]) ? (array) $rows[0] : []),
        );
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function profile(array $row): TownProfile
    {
        return new TownProfile(
            code: (string) $row['psgc_code'],
            name: (string) $row['name'],
            kind: (string) $row['kind'],
            population: TownFigure::fromNullable($row['population'], $row['population_year'], $row['population_source']),
            revenue: TownFigure::fromNullable($row['revenue'], $row['revenue_year'], $row['revenue_source']),
            expenditure: TownFigure::fromNullable($row['expenditure'], $row['expenditure_year'], $row['expenditure_source']),
            shade: $row['shade'] === null ? null : (int) $row['shade'],
            barangayCount: TownFigure::fromNullable($row['barangay_count'], $row['barangay_count_year'], $row['barangay_count_source']),
            incomeClass: TownFigure::fromNullable($row['income_class'], $row['income_class_year'], $row['income_class_source']),
            povertyIncidence: TownFigure::fromNullable($row['poverty_incidence'], $row['poverty_incidence_year'], $row['poverty_incidence_source']),
        );
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function province(array $row): TownProfile
    {
        $total = fn (string $m) => TownFigure::fromNullable($row["total_{$m}"] ?? null, $row["total_{$m}_year"] ?? null, $row["total_{$m}_source"] ?? null);

        return new TownProfile(
            code: '0201500000',
            name: 'Cagayan',
            kind: 'province',
            population: $total('population'),
            revenue: null,
            expenditure: null,
            shade: null,
            barangayCount: $total('barangay_count'),
        );
    }
}
