<?php

namespace App\Actions;

use App\Enums\FinanceAvailability;
use App\Enums\FinanceEntryMethod;
use App\Enums\FinanceFigureStatus;
use App\Models\ActivityLog;
use App\Models\FinanceDocument;
use App\Models\FinanceFigure;
use App\Models\Municipality;
use App\Models\User;
use App\Services\FinanceEntryCells;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * The changed cells of a report grid, saved as pending proposals (D35). Nothing published changes here: a verified
 * figure stays live until someone else signs off its replacement. The save is refused when any cell changed since the
 * grid was opened, and correcting a verified amount needs a reason.
 */
final class SubmitFinanceEntries
{
    public function __construct(private FinanceEntryCells $cells) {}

    /**
     * @param  array{submission: string, document_kind: string|null, source_url: string|null, page: int|null, reason: string|null, changes: list<array{line_code: string, fiscal_year: int, basis: string, value: string, base: string|null}>}  $entries
     * @return int|null The figures sent for review, or null when this submission was already saved.
     */
    public function handle(User $user, Municipality $town, int $reportYear, array $entries): ?int
    {
        // A double click or a resent form carries the same submission id; only the first one writes.
        if (! Cache::add('finance-entry:'.$entries['submission'], true, now()->addMinutes(10))) {
            return null;
        }

        try {
            return DB::transaction(function () use ($user, $town, $reportYear, $entries): int {
                // One save per town at a time, so the stale check below cannot race another save.
                Municipality::query()->whereKey($town->id)->lockForUpdate()->first();
                $years = array_values(array_unique(array_column($entries['changes'], 'fiscal_year')));
                $current = $this->cells->current($town->id, $years);

                $saved = [];
                $correctsVerified = false;
                foreach ($entries['changes'] as $change) {
                    $figure = $current->get("{$change['line_code']} {$change['fiscal_year']} {$change['basis']}");
                    if (! $this->same($figure?->value, $change['base'])) {
                        throw ValidationException::withMessages(['changes' => 'This report changed since you opened it. Reload to see the latest figures.']);
                    }
                    if ($this->same($figure?->value, $change['value'])) {
                        continue;
                    }
                    $correctsVerified = $correctsVerified || $figure?->status === FinanceFigureStatus::Verified;
                    $saved[] = $change;
                }
                if ($saved === []) {
                    throw ValidationException::withMessages(['changes' => 'Change at least one amount before saving.']);
                }
                if ($correctsVerified && $entries['reason'] === null) {
                    throw ValidationException::withMessages(['reason' => 'Give a reason for correcting a verified amount.']);
                }

                $document = $entries['document_kind'] === null ? null : FinanceDocument::query()
                    ->where('municipality_id', $town->id)->where('fiscal_year', $reportYear)
                    ->where('kind', $entries['document_kind'])->where('availability', FinanceAvailability::Available)
                    ->firstOrFail();
                foreach ($saved as $change) {
                    FinanceFigure::query()->create([
                        'public_id' => (string) Str::uuid(),
                        'municipality_id' => $town->id,
                        'fiscal_year' => $change['fiscal_year'],
                        'line_code' => $change['line_code'],
                        'basis' => $change['basis'],
                        'value' => $change['value'],
                        'finance_document_id' => $document?->id,
                        'source_url' => $document === null ? $entries['source_url'] : null,
                        'location' => $entries['page'] === null ? null : 'page '.$entries['page'],
                        'note' => $entries['reason'],
                        'entry_method' => FinanceEntryMethod::Manual,
                        'status' => FinanceFigureStatus::Pending,
                        'entered_by' => $user->id,
                    ]);
                }
                ActivityLog::record('finance_figure_entered', $user->id);

                return count($saved);
            });
        } catch (\Throwable $exception) {
            // A refused save may be corrected and sent again with the same id.
            Cache::forget('finance-entry:'.$entries['submission']);
            throw $exception;
        }
    }

    private function same(?string $stored, ?string $shown): bool
    {
        if ($stored === null || $shown === null || $shown === '') {
            return $stored === null && ($shown === null || $shown === '');
        }

        return is_numeric($stored) && is_numeric($shown) && bccomp($stored, $shown, 2) === 0;
    }
}
