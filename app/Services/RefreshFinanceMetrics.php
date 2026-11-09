<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

final class RefreshFinanceMetrics
{
    public function handle(): void
    {
        DB::transaction(function (): void {
            // Serialize rebuilds so concurrent publications cannot retain an older snapshot.
            DB::statement('LOCK TABLE stats_finance_year IN EXCLUSIVE MODE');
            DB::connection()->getPdo()->exec((string) file_get_contents(database_path('sql/metrics/20_stats_finance_year.sql')));
        });
    }
}
