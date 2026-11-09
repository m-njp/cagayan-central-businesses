<?php

namespace App\Actions;

use App\Enums\FinanceFigureStatus;
use App\Models\ActivityLog;
use App\Models\Municipality;
use App\Models\PopulationFigure;
use App\Models\User;
use App\Services\PopulationCells;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Changed census counts saved as pending proposals. Nothing published changes here: a map keeps the verified count
 * until someone else verifies the replacement. Refused when a count changed since the grid was opened; correcting a
 * verified count needs a reason; a resent submission writes once.
 */
final class SubmitPopulation
{
    public function __construct(private PopulationCells $cells) {}

    /**
     * @param  array{submission: string, source_url: string, reason: string|null, changes: list<array{place: string, value: int, base: int|null}>}  $entries
     * @return int|null The counts sent for review, or null when this submission was already saved.
     */
    public function handle(User $user, Municipality $town, int $year, array $entries): ?int
    {
        if (! Cache::add('population-entry:'.$entries['submission'], true, now()->addMinutes(10))) {
            return null;
        }

        try {
            return DB::transaction(function () use ($user, $town, $year, $entries): int {
                Municipality::query()->whereKey($town->id)->lockForUpdate()->first();
                $current = $this->cells->current($town, $year);

                $saved = [];
                $correctsVerified = false;
                foreach ($entries['changes'] as $change) {
                    $cell = $current[$change['place']] ?? null;
                    if ($cell === null) {
                        throw ValidationException::withMessages(['changes' => 'One of these places is not in this town.']);
                    }
                    if ($cell['value'] !== $change['base']) {
                        throw ValidationException::withMessages(['changes' => 'These counts changed since you opened them. Reload to see the latest.']);
                    }
                    if ($cell['value'] === $change['value']) {
                        continue;
                    }
                    $correctsVerified = $correctsVerified || $cell['status'] === 'verified';
                    $saved[] = [$cell, $change];
                }
                if ($saved === []) {
                    throw ValidationException::withMessages(['changes' => 'Change at least one count before saving.']);
                }
                if ($correctsVerified && $entries['reason'] === null) {
                    throw ValidationException::withMessages(['reason' => 'Give a reason for correcting a verified count.']);
                }

                foreach ($saved as [$cell, $change]) {
                    // The newer proposal replaces an older one still waiting for the same place and year.
                    PopulationFigure::query()->where('municipality_id', $town->id)->where('barangay_id', $cell['barangay'])
                        ->where('year', $year)->where('status', FinanceFigureStatus::Pending)
                        ->update(['status' => FinanceFigureStatus::Superseded]);
                    PopulationFigure::query()->create([
                        'public_id' => (string) Str::uuid(), 'municipality_id' => $town->id, 'barangay_id' => $cell['barangay'],
                        'year' => $year, 'value' => $change['value'], 'source_url' => $entries['source_url'],
                        'status' => FinanceFigureStatus::Pending, 'entered_by' => $user->id, 'note' => $entries['reason'],
                    ]);
                }
                ActivityLog::record('population_entered', $user->id);

                return count($saved);
            });
        } catch (\Throwable $exception) {
            // A refused save may be corrected and sent again with the same id.
            Cache::forget('population-entry:'.$entries['submission']);
            throw $exception;
        }
    }
}
