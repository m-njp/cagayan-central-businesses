<?php

namespace App\Actions;

use App\Enums\FinanceFigureStatus;
use App\Models\ActivityLog;
use App\Models\FinanceFigure;
use App\Models\Municipality;
use App\Models\User;
use App\Services\FinanceSignOff;
use App\Services\RefreshFinanceMetrics;
use DomainException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/** Signs off or rejects pending figures of one town and report year. Figures the reviewer entered are skipped. */
final class ReviewFinanceFigures
{
    public function __construct(private readonly FinanceSignOff $signOff, private readonly RefreshFinanceMetrics $metrics) {}

    /**
     * @param  list<string>  $ids
     * @return array{done: int, skipped: int}
     */
    public function handle(User $reviewer, Municipality $town, int $year, string $decision, array $ids, ?string $note): array
    {
        return DB::transaction(function () use ($reviewer, $town, $year, $decision, $ids, $note): array {
            $result = $this->review($reviewer, $town, $year, $decision, $ids, $note);
            if ($result['done'] > 0) {
                $this->metrics->handle();
            }

            return $result;
        });
    }

    /** @param list<string> $ids
     * @return array{done: int, skipped: int}
     */
    private function review(User $reviewer, Municipality $town, int $year, string $decision, array $ids, ?string $note): array
    {
        $figures = FinanceFigure::query()
            ->where('municipality_id', $town->id)
            ->whereIn('public_id', $ids)
            ->where('status', FinanceFigureStatus::Pending)
            ->where(fn (Builder $query) => $query->whereHas('document', fn (Builder $document) => $document->where('fiscal_year', $year))
                ->orWhere(fn (Builder $manual) => $manual->whereNull('finance_document_id')->where('fiscal_year', $year)))
            ->get();

        $today = now()->timezone(config('cagayan.display_timezone'))->toDateString();
        $done = 0;
        foreach ($figures as $figure) {
            try {
                $decision === 'verified'
                    ? $this->signOff->verify($figure, $reviewer, $today, $note)
                    : $this->signOff->reject($figure, $reviewer, $today, $note);
                $done++;
            } catch (DomainException) {
                continue;
            }
        }

        if ($done > 0) {
            ActivityLog::record($decision === 'verified' ? 'finance_figures_signed_off' : 'finance_figures_rejected', $reviewer->id);
        }

        return ['done' => $done, 'skipped' => $figures->count() - $done];
    }
}
