<?php

namespace App\Services;

use App\Enums\FinanceEntryMethod;
use App\Enums\FinanceFigureStatus;
use App\Models\FinanceDocument;
use App\Models\FinanceFigure;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;

/**
 * Sign-off rules for the finance ledger (D35): one current figure per town, year, line and basis;
 * the latest report wins and a manual correction counts as the latest; nobody signs off their own entry.
 */
final class FinanceSignOff
{
    public function verify(FinanceFigure $figure, ?User $reviewer, string $reviewedOn, ?string $note = null): void
    {
        $this->guardReviewer($figure, $reviewer);

        DB::transaction(function () use ($figure, $reviewer, $reviewedOn, $note): void {
            $current = FinanceFigure::query()
                ->where('municipality_id', $figure->municipality_id)
                ->where('fiscal_year', $figure->fiscal_year)
                ->where('line_code', $figure->line_code)
                ->where('basis', $figure->basis)
                ->where('status', FinanceFigureStatus::Verified)
                ->whereKeyNot($figure->id)
                ->lockForUpdate()
                ->first();

            $wins = $current === null || $this->rank($figure) >= $this->rank($current);

            if ($wins && $current !== null) {
                $current->update(['status' => FinanceFigureStatus::Superseded]);
            }

            $figure->update([
                'status' => $wins ? FinanceFigureStatus::Verified : FinanceFigureStatus::Superseded,
                'reviewed_by' => $reviewer?->id,
                'reviewed_on' => $reviewedOn,
                'note' => $note ?? $figure->note,
            ]);
        });
    }

    public function reject(FinanceFigure $figure, ?User $reviewer, string $reviewedOn, ?string $note = null): void
    {
        $this->guardReviewer($figure, $reviewer);

        $figure->update([
            'status' => FinanceFigureStatus::Rejected,
            'reviewed_by' => $reviewer?->id,
            'reviewed_on' => $reviewedOn,
            'note' => $note ?? $figure->note,
        ]);
    }

    private function guardReviewer(FinanceFigure $figure, ?User $reviewer): void
    {
        if ($reviewer !== null && $figure->entered_by === $reviewer->id) {
            throw new DomainException('A figure cannot be signed off by the person who entered it.');
        }
    }

    /** Later reports restate earlier years; a manual correction is the most recent decision. */
    private function rank(FinanceFigure $figure): int
    {
        if ($figure->entry_method === FinanceEntryMethod::Manual) {
            return PHP_INT_MAX;
        }

        return (int) FinanceDocument::query()->whereKey($figure->finance_document_id)->value('fiscal_year');
    }
}
