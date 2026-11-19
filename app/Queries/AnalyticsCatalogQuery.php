<?php

namespace App\Queries;

use App\Data\AnalyticsCatalog;
use App\Data\AnalyticsMeasure;
use App\Data\AnalyticsTownFinances;
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
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use stdClass;

/**
 * Signed-off finance series (stats_finance_year, M19 to M27) and business trends for the analytics pages.
 * Only towns with at least one signed-off figure appear; every other town is not available.
 *
 * @phpstan-type CatalogContext array{years: non-empty-list<int>, groups: array<array-key, mixed>, lines: Collection<array-key, EloquentCollection<int, FinanceLine>>, towns: EloquentCollection<int, Municipality>, stats: Collection<array-key, Collection<int, stdClass>>, guide: array<string, mixed>}
 */
final class AnalyticsCatalogQuery
{
    /** The Sum of Reporting Towns uses the province code, but it is never the provincial government's accounts (M27). */
    public const string ALL_TOWNS = '0201500000';

    public function __construct(private BusinessTrendsQuery $businesses, private FinanceEvidenceQuery $evidence) {}

    /**
     * The catalog with the Sum of Reporting Towns and the selected town's finances. Other towns load once on demand
     * through town(), so the first page stays small (frontend.md, "Local Selection Contract", 26-11-14).
     */
    public function get(string $town): AnalyticsCatalog
    {
        $context = $this->context();
        [$finances, $sources] = $this->finances($context, [self::ALL_TOWNS, $town]);

        return new AnalyticsCatalog(
            towns: [new SelectOption(self::ALL_TOWNS, 'Sum of Reporting Towns'),
                ...$context['towns']->map(fn (Municipality $item) => new SelectOption($item->psgc_code, $item->name))->all()],
            measures: array_map(fn (string $key) => new AnalyticsMeasure($key, $context['groups'][$key]['label'], $this->coverage($context, $key)), array_keys($context['lines']->all())),
            periods: ['all' => $context['years'][0], 'recent' => (int) config('cagayan.performance.recent_first_year')],
            lastYear: end($context['years']),
            financeTowns: array_values(array_filter($this->codes($context), fn (string $code, int $id) => $context['stats']->has($id), ARRAY_FILTER_USE_BOTH)),
            finances: $finances,
            businesses: $this->businesses->get(),
            guide: $context['guide'],
            sources: $sources,
        );
    }

    /** One town's finances and their citations, fetched the first time a viewer selects the town. */
    public function town(string $town): AnalyticsTownFinances
    {
        [$finances, $sources] = $this->finances($this->context(), [$town]);

        return new AnalyticsTownFinances(finances: $finances, sources: $sources);
    }

    /**
     * @return CatalogContext
     */
    private function context(): array
    {
        $financeYears = app(FinanceYears::class);
        $years = range($financeYears->publishedFirstYear(), $financeYears->publishedLastYear());
        $groups = config('cagayan.finances.groups');
        /** @var array<string, mixed> $guide */
        $guide = json_decode(File::get(resource_path('content/analytics-guide.json')), true, flags: JSON_THROW_ON_ERROR);

        return [
            'years' => $years,
            'groups' => $groups,
            'lines' => FinanceLine::query()->where('displayed', true)->whereIn('group', array_keys($groups))
                ->orderBy('sort')->get(['code', 'label', 'group', 'unit', 'source'])->groupBy('group')->toBase(),
            'towns' => Municipality::query()->orderBy('name')->get(['id', 'psgc_code', 'name']),
            'stats' => DB::table('stats_finance_year')->whereBetween('fiscal_year', [$years[0], end($years)])->get()
                ->groupBy(fn (stdClass $row): int => (int) ($row->municipality_id ?? 0)),
            'guide' => $guide,
        ];
    }

    /**
     * Town codes keyed by municipality id, the Sum of Reporting Towns first as id 0. A union, not a spread: spreading
     * would renumber the municipality ids that key the stats.
     *
     * @param  CatalogContext  $context
     * @return array<int, string>
     */
    private function codes(array $context): array
    {
        return [0 => self::ALL_TOWNS] + $context['towns']->pluck('psgc_code', 'id')->all();
    }

    /**
     * The histories of the requested towns that have signed-off figures, and the citations their own figures use. The
     * Sum of Reporting Towns cites "COA Annual Audit Reports" as a whole, so it carries no per-figure references.
     *
     * @param  CatalogContext  $context
     * @param  list<string>  $codes
     * @return array{0: array<string, array<string, FinancialHistory>>, 1: array<string, array<string, mixed>>}
     */
    private function finances(array $context, array $codes): array
    {
        $ids = array_filter($this->codes($context), fn (string $code, int $id) => in_array($code, $codes, true) && $context['stats']->has($id), ARRAY_FILTER_USE_BOTH);
        if ($ids === []) {
            return [[], []];
        }
        $dependencies = [];
        foreach ($context['guide']['topics'] as $topic) {
            foreach ($topic['metrics'] as $metric) {
                if ($metric['inputs'] !== []) {
                    $dependencies[$metric['id']] = $metric['inputs'];
                }
            }
        }
        $townIds = array_keys(array_diff_key($ids, [0 => true]));
        $evidence = $this->evidence->get($context['stats']->only($townIds)->flatten(1), $dependencies);
        // Withheld until signed off (D35); only the fact that a figure awaits review is shown, never its value.
        $pending = FinanceFigure::query()->where('status', FinanceFigureStatus::Pending)->whereIn('municipality_id', $townIds)
            ->get(['municipality_id', 'fiscal_year', 'line_code'])
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
        foreach ($ids as $id => $code) {
            $finances[$code] = $context['lines']->map(fn (Collection $group) => $this->history($group, $context['stats'][$id], $context['years'], $id, $documents, $pending, $evidence['references']))->all();
        }

        return [$finances, $evidence['sources']];
    }

    /**
     * The towns that have at least one signed-off figure for a measure, so a picker can say where it applies (null: every town).
     *
     * @param  CatalogContext  $context
     * @return list<string>|null
     */
    private function coverage(array $context, string $measure): ?array
    {
        $metrics = $context['lines'][$measure]->pluck('code')->all();
        $withFigures = 0;
        $towns = [];
        foreach ($this->codes($context) as $id => $code) {
            if (! $context['stats']->has($id)) {
                continue;
            }
            $withFigures++;
            if ($context['stats'][$id]->whereIn('metric', $metrics)->isNotEmpty()) {
                $towns[] = $code;
            }
        }

        return count($towns) === $withFigures ? null : $towns;
    }

    /**
     * @param  Collection<int, FinanceLine>  $lines
     * @param  Collection<int, stdClass>  $stats
     * @param  list<int>  $years
     * @param  array<string, FinanceAvailability>  $documents
     * @param  array<string, true>  $pending
     * @param  array<string, list<string>>  $references
     */
    private function history(Collection $lines, Collection $stats, array $years, int $townId, array $documents, array $pending, array $references): FinancialHistory
    {
        $byKey = $stats->keyBy(fn (stdClass $row) => "{$row->metric} {$row->fiscal_year}");
        $rows = array_map(fn (int $year) => ['year' => $year, 'observations' => $lines->mapWithKeys(function (FinanceLine $line) use ($byKey, $year, $townId, $documents, $pending, $references) {
            $row = $byKey->get("{$line->code} {$year}");
            if ($row === null) {
                $reason = match ($documents["{$townId} {$year} executive_summary"] ?? null) {
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
