<?php

namespace App\Queries;

use App\Data\AnalyticsCatalog;
use App\Data\AnalyticsMeasure;
use App\Data\EconomicMetric;
use App\Data\EconomicObservation;
use App\Data\FinancialHistory;
use App\Data\SelectOption;
use App\Enums\FinanceAvailability;
use App\Enums\FinanceFigureStatus;
use App\Models\FinanceDocument;
use App\Models\FinanceFigure;
use App\Models\FinanceLine;
use App\Models\Municipality;
use App\Services\FinanceYears;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use stdClass;

/**
 * Signed-off finance series (stats_finance_year, M19 to M27) and business trends for the analytics pages.
 * Only towns with at least one signed-off figure appear; every other town is not available.
 */
final class AnalyticsCatalogQuery
{
    /** The Sum of Reporting Towns uses the province code, but it is never the provincial government's accounts (M27). */
    public const string ALL_TOWNS = '0201500000';

    /** Which COA document a missing line would have come from, to say why it is missing. */
    private const array SOURCE_KINDS = [
        'executive_summary' => 'executive_summary',
        'fs_workbook' => 'fs_workbook',
        'fs_scbaa' => 'fs_workbook',
        'annex_c1' => 'annex_workbook',
    ];

    /** The document behind each derived line's inputs. */
    private const array DERIVED_KINDS = [
        'revenue.remaining' => 'fs_workbook',
        'revenue.local_share' => 'fs_workbook',
        'sector.development_fund.utilization' => 'fs_workbook',
        'sector.economic_services.utilization' => 'fs_workbook',
    ];

    public function __construct(private BusinessTrendsQuery $businesses, private FinanceEvidenceQuery $evidence) {}

    public function get(): AnalyticsCatalog
    {
        $years = range((int) config('cagayan.performance.first_year'), app(FinanceYears::class)->publishedLastYear());
        $groups = config('cagayan.finances.groups');
        $lines = FinanceLine::query()->where('displayed', true)->whereIn('group', array_keys($groups))
            ->orderBy('sort')->get(['code', 'label', 'group', 'unit', 'source'])->groupBy('group');
        $towns = Municipality::query()->orderBy('name')->get(['id', 'psgc_code', 'name']);
        $stats = DB::table('stats_finance_year')->whereBetween('fiscal_year', [$years[0], end($years)])->get()
            ->groupBy(fn (stdClass $row): int => (int) ($row->municipality_id ?? 0));
        /** @var array<string, mixed> $guide */
        $guide = json_decode(File::get(resource_path('content/analytics-guide.json')), true, flags: JSON_THROW_ON_ERROR);
        $dependencies = [];
        foreach ($guide['topics'] as $topic) {
            foreach ($topic['metrics'] as $metric) {
                if ($metric['inputs'] !== []) {
                    $dependencies[$metric['id']] = $metric['inputs'];
                }
            }
        }
        $evidence = $this->evidence->get($stats->flatten(1), $dependencies);
        // Withheld until signed off (D35); only the fact that a figure awaits review is shown, never its value.
        $pending = FinanceFigure::query()->where('status', FinanceFigureStatus::Pending)->get(['municipality_id', 'fiscal_year', 'line_code'])
            ->mapWithKeys(fn (FinanceFigure $figure) => ["{$figure->municipality_id} {$figure->fiscal_year} {$figure->line_code}" => true])->all();
        // Keyed "town year kind"; town 0 records that at least one town has the document (Sum of Reporting Towns).
        $documents = [];
        foreach (FinanceDocument::query()->get(['municipality_id', 'fiscal_year', 'kind', 'availability']) as $document) {
            $documents["{$document->municipality_id} {$document->fiscal_year} {$document->kind->value}"] = $document->availability;
            if ($document->availability === FinanceAvailability::Available) {
                $documents["0 {$document->fiscal_year} {$document->kind->value}"] = FinanceAvailability::Available;
            }
        }

        $finances = [];
        $detailTowns = (array) config('cagayan.finances.summary_detail_towns');
        // Union, not spread: spreading would renumber the municipality ids that key the stats.
        foreach ([0 => self::ALL_TOWNS] + $towns->pluck('psgc_code', 'id')->all() as $id => $code) {
            if ($stats->has($id)) {
                $detailLoaded = $id === 0 || in_array($code, $detailTowns, true);
                $finances[$code] = $lines->map(fn (Collection $group) => $this->history($group, $stats[$id], $years, $id, $documents, $pending, $evidence['references'], $detailLoaded))->all();
            }
        }

        return new AnalyticsCatalog(
            towns: [new SelectOption(self::ALL_TOWNS, 'Sum of Reporting Towns'),
                ...$towns->map(fn (Municipality $town) => new SelectOption($town->psgc_code, $town->name))->all()],
            measures: array_map(fn (string $key) => new AnalyticsMeasure($key, $groups[$key]['label'], $this->coverage($finances, $key)), array_keys($lines->all())),
            periods: ['all' => $years[0], 'recent' => (int) config('cagayan.performance.recent_first_year')],
            lastYear: end($years),
            finances: $finances,
            businesses: $this->businesses->get(),
            guide: $guide,
            sources: $evidence['sources'],
        );
    }

    /**
     * The towns that have at least one signed-off figure for a measure, so a picker can say where it applies (null: every town).
     *
     * @param  array<string, array<string, FinancialHistory>>  $finances
     * @return list<string>|null
     */
    private function coverage(array $finances, string $measure): ?array
    {
        $towns = [];
        foreach ($finances as $code => $histories) {
            foreach ($histories[$measure]->rows ?? [] as $row) {
                if (collect($row['observations'])->contains(fn (EconomicObservation $point) => $point->state === 'shown')) {
                    $towns[] = $code;
                    break;
                }
            }
        }

        return count($towns) === count($finances) ? null : $towns;
    }

    /**
     * @param  Collection<int, FinanceLine>  $lines
     * @param  Collection<int, stdClass>  $stats
     * @param  list<int>  $years
     * @param  array<string, FinanceAvailability>  $documents
     * @param  array<string, true>  $pending
     * @param  array<string, list<string>>  $references
     */
    private function history(Collection $lines, Collection $stats, array $years, int $townId, array $documents, array $pending, array $references, bool $detailLoaded): FinancialHistory
    {
        $detailLines = (array) config('cagayan.finances.summary_detail_lines');
        $byKey = $stats->keyBy(fn (stdClass $row) => "{$row->metric} {$row->fiscal_year}");
        $rows = array_map(fn (int $year) => ['year' => $year, 'observations' => $lines->mapWithKeys(function (FinanceLine $line) use ($byKey, $year, $townId, $documents, $pending, $references, $detailLoaded, $detailLines) {
            $row = $byKey->get("{$line->code} {$year}");
            if ($row === null) {
                $kind = self::SOURCE_KINDS[$line->source] ?? self::DERIVED_KINDS[$line->code] ?? 'executive_summary';
                $reason = ! $detailLoaded && in_array($line->code, $detailLines, true) ? 'not_loaded' : match ($documents["{$townId} {$year} {$kind}"] ?? null) {
                    FinanceAvailability::Available => isset($pending["{$townId} {$year} {$line->code}"]) ? 'pending_review' : 'not_reported',
                    FinanceAvailability::NotPublished => 'not_published',
                    null => 'not_collected',
                };

                return [$line->code => new EconomicObservation('unavailable', null, false, null, null, null, $reason)];
            }

            return [$line->code => new EconomicObservation(
                state: 'shown',
                value: (string) $row->value,
                comparable: (bool) $row->comparable,
                change: $row->change === null ? null : (string) $row->change,
                percentageChange: $row->percent_change === null ? null : (float) $row->percent_change,
                comparisonYear: $row->comparable ? $year - 1 : null,
                coverage: $row->reporting_towns === null ? null
                    : ['reporting' => (int) $row->reporting_towns, 'total' => (int) $row->total_towns, 'matched' => (int) $row->matched_towns],
                censusYear: $row->census_year === null ? null : (int) $row->census_year,
                restatedIn: $row->restated_in === null ? null : (int) $row->restated_in,
                sourceRefs: $references["{$townId} {$year} {$line->code}"] ?? [],
            )];
        })->all()], $years);

        $values = $stats->whereIn('metric', $lines->pluck('code'))->pluck('value');

        return new FinancialHistory(
            metrics: array_values($lines->map(fn (FinanceLine $line) => new EconomicMetric($line->code, $line->label, $line->unit, $line->source))->all()),
            rows: $rows,
            axisMaximum: (string) ($values->max() ?? '0'),
        );
    }
}
