<?php

namespace App\Services;

use App\Models\FinanceDocument;
use App\Models\FinanceFigure;
use Illuminate\Support\Facades\DB;

/** Configured ranges remain defaults; actual records can extend them without a calendar rollover. */
final class FinanceYears
{
    public function reportLastYear(): int
    {
        return max(
            (int) config('cagayan.finances.last_year'),
            (int) FinanceDocument::query()->where('availability', 'available')->max('fiscal_year'),
            (int) FinanceFigure::query()->max('fiscal_year'),
        );
    }

    public function publishedLastYear(): int
    {
        return max((int) config('cagayan.performance.last_year'), (int) DB::table('stats_finance_year')->max('fiscal_year'));
    }
}
