<?php

namespace App\Queries;

use App\Data\EconomicMetric;
use App\Data\EconomicObservation;
use App\Data\FinanceMap;
use App\Models\FinanceLine;
use Illuminate\Support\Facades\DB;
use stdClass;

/** Both maps use Analytics' signed-off annual results, without business or census evidence. */
final class FinanceMapQuery
{
    public const array METRICS = ['performance.revenue', 'performance.expenses', 'performance.surplus'];

    public function __construct(private FinanceEvidenceQuery $evidence) {}

    public function get(): FinanceMap
    {
        /** @var list<stdClass> $rows */
        $rows = DB::select(<<<'SQL'
            select s.*, coalesce(m.psgc_code, '0201500000') as code,
                case when s.municipality_id is not null then
                    ntile(?) over (partition by s.fiscal_year, s.metric, s.municipality_id is null order by s.value, m.psgc_code)
                end as shade
            from stats_finance_year s
            left join municipalities m on m.id = s.municipality_id
            where s.metric in (?, ?, ?)
            order by s.fiscal_year desc, s.metric, m.psgc_code
            SQL, [(int) config('cagayan.towns.shades'), ...self::METRICS]);
        $stats = collect($rows);
        $evidence = $this->evidence->get($stats, [
            'performance.surplus' => ['performance.revenue', 'performance.expenses'],
        ]);
        $observations = [];
        $shades = [];
        foreach ($rows as $row) {
            $year = (int) $row->fiscal_year;
            $observations[$row->code][$year][$row->metric] = new EconomicObservation(
                state: 'shown', value: (string) $row->value,
                comparable: (bool) $row->comparable,
                change: $row->change === null ? null : (string) $row->change,
                percentageChange: $row->percent_change === null ? null : (float) $row->percent_change,
                comparisonYear: $row->comparable ? $year - 1 : null,
                coverage: $row->reporting_towns === null ? null : [
                    'reporting' => (int) $row->reporting_towns, 'total' => (int) $row->total_towns, 'matched' => (int) $row->matched_towns,
                ],
                restatedIn: $row->restated_in === null ? null : (int) $row->restated_in,
                sourceRefs: $evidence['references'][($row->municipality_id ?? 0)." {$year} {$row->metric}"] ?? [],
            );
            if ($row->shade !== null) {
                $shades[$row->code][$year][$row->metric] = (int) $row->shade;
            }
        }

        return new FinanceMap(
            years: array_values($stats->pluck('fiscal_year')->map(fn ($year) => (int) $year)->unique()->all()),
            latestYear: $stats->whereNotNull('municipality_id')->where('metric', 'performance.surplus')->max('fiscal_year'),
            metrics: array_values(FinanceLine::query()->whereIn('code', self::METRICS)->orderBy('sort')->get()
                ->map(fn (FinanceLine $line) => new EconomicMetric($line->code, $line->label, $line->unit, $line->source))->all()),
            observations: $observations, shades: $shades, sources: $evidence['sources'],
        );
    }
}
