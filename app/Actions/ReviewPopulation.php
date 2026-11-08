<?php

namespace App\Actions;

use App\Enums\FinanceFigureStatus;
use App\Models\ActivityLog;
use App\Models\Barangay;
use App\Models\Municipality;
use App\Models\PopulationFigure;
use App\Models\TownFact;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Verify or return pending census counts, reviewed independently as finance figures are (D35). A verified count is
 * published into town_facts or barangays in the same transaction, so every map reads it without a rebuild. Nobody
 * reviews a count they entered.
 */
final class ReviewPopulation
{
    /**
     * @param  list<string>  $ids
     * @return array{done: int, skipped: int}
     */
    public function handle(User $reviewer, Municipality $town, int $year, string $decision, array $ids, ?string $note): array
    {
        return DB::transaction(function () use ($reviewer, $town, $year, $decision, $ids, $note): array {
            $figures = PopulationFigure::query()->where('municipality_id', $town->id)->where('year', $year)
                ->where('status', FinanceFigureStatus::Pending)->whereIn('public_id', $ids)->orderBy('id')->lockForUpdate()->get();
            $done = 0;
            $skipped = 0;
            foreach ($figures as $figure) {
                if ($figure->entered_by === $reviewer->id) {
                    $skipped++;

                    continue;
                }
                if ($decision === 'verified') {
                    $this->publish($figure);
                }
                $figure->update([
                    'status' => $decision === 'verified' ? FinanceFigureStatus::Verified : FinanceFigureStatus::Rejected,
                    'reviewed_by' => $reviewer->id, 'reviewed_on' => now()->toDateString(),
                    'note' => $decision === 'rejected' ? $note : $figure->note,
                ]);
                $done++;
            }
            if ($done > 0) {
                ActivityLog::record($decision === 'verified' ? 'population_verified' : 'population_returned', $reviewer->id);
            }

            return ['done' => $done, 'skipped' => $skipped];
        });
    }

    private function publish(PopulationFigure $figure): void
    {
        PopulationFigure::query()->where('municipality_id', $figure->municipality_id)->where('barangay_id', $figure->barangay_id)
            ->where('year', $figure->year)->where('status', FinanceFigureStatus::Verified)
            ->update(['status' => FinanceFigureStatus::Superseded]);

        if ($figure->barangay_id === null) {
            TownFact::query()->updateOrCreate(
                ['municipality_id' => $figure->municipality_id, 'measure' => 'population', 'year' => $figure->year],
                ['value' => $figure->value, 'source_url' => $figure->source_url, 'checked_on' => now()->toDateString()],
            );

            return;
        }
        // A barangay keeps one current count; an older census year never replaces a newer one.
        Barangay::query()->whereKey($figure->barangay_id)
            ->where(fn (Builder $query) => $query->whereNull('population_year')->orWhere('population_year', '<=', $figure->year))
            ->update(['population' => $figure->value, 'population_year' => $figure->year, 'population_source' => $figure->source_url]);
    }
}
