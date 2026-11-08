<?php

namespace App\Services;

use App\Enums\FinanceFigureStatus;
use App\Models\FinanceFigure;
use Illuminate\Support\Collection;

/**
 * The figure a person sees for each line and year when entering a report: the verified figure, or else the newest
 * pending one. The grid prefills from it and a save is checked against it, so both read the same rule.
 */
final class FinanceEntryCells
{
    /**
     * @param  list<int>  $years
     * @return Collection<string, FinanceFigure> Keyed "line year basis".
     */
    public function current(int $townId, array $years): Collection
    {
        return FinanceFigure::query()
            ->where('municipality_id', $townId)->whereIn('fiscal_year', $years)
            ->whereIn('status', [FinanceFigureStatus::Verified, FinanceFigureStatus::Pending])
            ->orderByRaw("status = 'verified'")->orderBy('id')
            ->get(['id', 'line_code', 'fiscal_year', 'basis', 'value', 'status'])
            // Later rows win: newest pending, then any verified figure over it.
            ->keyBy(fn (FinanceFigure $figure) => "{$figure->line_code} {$figure->fiscal_year} {$figure->basis->value}");
    }

    /** @return list<int> The report year and the year before, as an Executive Summary prints them. */
    public static function years(int $reportYear): array
    {
        return array_values(array_filter([$reportYear - 1, $reportYear], fn (int $year) => $year >= (int) config('cagayan.finances.first_year')));
    }
}
