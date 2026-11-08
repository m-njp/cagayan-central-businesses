<?php

namespace App\Queries;

use App\Data\PopulationPage;
use App\Data\PopulationRow;
use App\Data\SelectOption;
use App\Enums\FinanceFigureStatus;
use App\Models\Barangay;
use App\Models\Municipality;
use App\Models\PopulationFigure;
use App\Models\TownFact;
use App\Models\User;
use App\Services\PopulationCells;

/** A town's census counts for one year: the published count or the newest proposal for each place. */
final class PopulationQuery
{
    public function __construct(private PopulationCells $cells) {}

    public function get(User $user, Municipality $town, ?int $year): PopulationPage
    {
        $towns = Municipality::query()
            ->when(! $user->isCapitolAdmin(), fn ($query) => $query->whereIn('id', $user->assignedTowns()->select('municipalities.id')))
            ->orderBy('name')->get(['psgc_code', 'name']);
        $known = collect([
            ...TownFact::query()->where('municipality_id', $town->id)->where('measure', 'population')->pluck('year'),
            ...Barangay::query()->where('municipality_id', $town->id)->whereNotNull('population_year')->distinct()->pluck('population_year'),
            ...PopulationFigure::query()->where('municipality_id', $town->id)->distinct()->pluck('year'),
            now()->year,
        ])->map(fn ($value) => (int) $value)->unique()->sortDesc()->values();
        $year ??= (int) ($known->first(fn (int $value) => $value < now()->year) ?? now()->year);

        $pending = PopulationFigure::query()->where('municipality_id', $town->id)->where('year', $year)
            ->where('status', FinanceFigureStatus::Pending)->get(['public_id', 'barangay_id', 'entered_by'])
            ->keyBy(fn (PopulationFigure $figure) => $figure->barangay_id ?? 0);

        // A place shows Returned while its newest proposal is the one a reviewer sent back.
        $newest = PopulationFigure::query()->where('municipality_id', $town->id)->where('year', $year)
            ->orderBy('id')->get(['barangay_id', 'status', 'note'])
            ->keyBy(fn (PopulationFigure $figure) => $figure->barangay_id ?? 0);

        $rows = [];
        foreach ($this->cells->current($town, $year) as $place => $cell) {
            $key = $cell['barangay'] ?? 0;
            $proposal = $pending->get($key);
            $last = $newest->get($key);
            $rows[] = new PopulationRow((string) $place, $cell['name'], $cell['value'], $cell['status'], $cell['source'],
                $proposal?->public_id, $proposal !== null && $proposal->entered_by === $user->id,
                $last?->status === FinanceFigureStatus::Rejected ? ($last->note ?? '') : null);
        }

        return new PopulationPage(
            towns: array_values($towns->map(fn (Municipality $item) => new SelectOption($item->psgc_code, $item->name))->all()),
            town: new SelectOption($town->psgc_code, $town->name),
            year: $year,
            years: array_values($known->all()),
            rows: $rows,
            canReview: $user->can('finance-review'),
            limits: ['valueMax' => (int) config('cagayan.population.value_max'), 'changesMax' => (int) config('cagayan.population.changes_max'),
                'noteMax' => (int) config('cagayan.population.note_max'), 'sourceUrlMax' => (int) config('cagayan.population.source_url_max')],
        );
    }
}
