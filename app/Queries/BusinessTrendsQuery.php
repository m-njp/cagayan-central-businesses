<?php

namespace App\Queries;

use App\Data\BusinessTrends;
use App\Data\EconomicMetric;
use App\Data\EconomicObservation;
use App\Data\FinancialHistory;
use App\Data\SelectOption;
use App\Models\Municipality;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use stdClass;

/**
 * M28 active, new and closed business counts for the connected town by industry or Business Category, summed from
 * stats_area_year in SQL. No sales figure is sent: declared sales were removed from Business Trends (26-10-29).
 */
final class BusinessTrendsQuery
{
    private const array COUNTS = [
        'active' => 'Active Businesses',
        'new' => 'New Registrations',
        'closed' => 'Closed Businesses',
    ];

    public function get(): ?BusinessTrends
    {
        // Business records are simulated until a real registry is loaded, so nothing shows unless the label can show too (P13).
        if (! config('cagayan.simulated')) {
            return null;
        }

        $town = Municipality::query()->where('psgc_code', config('cagayan.performance.demo_town'))->first(['id', 'psgc_code', 'name']);
        $cells = $town === null ? collect() : $this->cells($town->id);
        if ($town === null || $cells->isEmpty()) {
            return null;
        }

        $industries = [];
        $categories = [];
        $datasets = [];
        $years = range((int) $cells->min('year'), (int) $cells->max('year'));
        foreach ($cells->groupBy(fn (stdClass $cell) => "{$cell->dimension}:{$cell->code}") as $key => $series) {
            $first = $series->first();
            $option = new SelectOption((string) $key, (string) $first->label);
            if ($first->dimension === 'category') {
                $categories[] = $option;
            } else {
                $industries[] = $option;
            }
            $datasets[$key] = $this->withGaps($this->counts($series), $years);
        }

        return new BusinessTrends($town->psgc_code, $town->name, ['industry' => $industries, 'category' => $categories], $datasets);
    }

    /** @return Collection<int, stdClass> */
    private function cells(int $townId): Collection
    {
        return collect(DB::select(<<<'SQL'
            with cells as (
                select 'industry' as dimension, t.psic_section as code, t.psic_section_name as label, s.year,
                    sum(s.active_count) as active, sum(s.new_count) as new, sum(s.closed_count) as closed
                from stats_area_year s
                join barangays g on g.id = s.barangay_id
                join business_types t on t.id = s.business_type_id
                where g.municipality_id = ?
                group by t.psic_section, t.psic_section_name, s.year
                union all
                select 'category', c.code, c.name, s.year,
                    sum(s.active_count), sum(s.new_count), sum(s.closed_count)
                from stats_area_year s
                join barangays g on g.id = s.barangay_id
                join business_types t on t.id = s.business_type_id
                join business_categories c on c.id = t.business_category_id
                where g.municipality_id = ?
                group by c.code, c.name, s.year
            )
            select *,
                lag(year) over w as prior_year,
                active - lag(active) over w as active_change,
                round((active - lag(active) over w) * 100.0 / nullif(lag(active) over w, 0), 1) as active_percent,
                new - lag(new) over w as new_change,
                round((new - lag(new) over w) * 100.0 / nullif(lag(new) over w, 0), 1) as new_percent,
                closed - lag(closed) over w as closed_change,
                round((closed - lag(closed) over w) * 100.0 / nullif(lag(closed) over w, 0), 1) as closed_percent
            from cells
            window w as (partition by dimension, code order by year)
            order by dimension, label, year
            SQL, [$townId, $townId]));
    }

    /** @param  Collection<int, stdClass>  $series */
    private function counts(Collection $series): FinancialHistory
    {
        $rows = array_values($series->map(fn (stdClass $cell) => ['year' => (int) $cell->year, 'observations' => array_map(function (string $metric) use ($cell) {
            $comparable = $cell->prior_year !== null && (int) $cell->prior_year === (int) $cell->year - 1;
            $percent = $cell->{"{$metric}_percent"};

            return new EconomicObservation('shown', (string) $cell->{$metric}, $comparable,
                $comparable ? (string) $cell->{"{$metric}_change"} : null,
                $comparable && $percent !== null ? (float) $percent : null,
                $comparable ? (int) $cell->year - 1 : null);
        }, array_combine(array_keys(self::COUNTS), array_keys(self::COUNTS)))])->all());

        return new FinancialHistory(
            metrics: array_map(fn (string $id, string $label) => new EconomicMetric($id, $label, 'count', 'Registered businesses only'), array_keys(self::COUNTS), self::COUNTS),
            rows: $rows,
            axisMaximum: (string) $series->max('active'),
            note: 'Registered businesses only',
        );
    }

    /**
     * A year with no records for the group is a gap: Not available, never zero and never bridged (M26).
     *
     * @param  list<int>  $years
     */
    private function withGaps(FinancialHistory $history, array $years): FinancialHistory
    {
        $byYear = array_column($history->rows, null, 'year');
        $missing = array_fill_keys(array_map(fn (EconomicMetric $metric) => $metric->id, $history->metrics), new EconomicObservation('unavailable', null, false, null, null, null));

        return new FinancialHistory(
            metrics: $history->metrics,
            rows: array_map(fn (int $year) => $byYear[$year] ?? ['year' => $year, 'observations' => $missing], $years),
            axisMaximum: $history->axisMaximum,
            note: $history->note,
        );
    }
}
