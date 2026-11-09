<?php

namespace App\Console\Commands;

use App\Services\RefreshFinanceMetrics;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Rebuilds every stats_ summary table from the raw records (decisions.md, D7). Each file in
 * database/sql/metrics/ fills one table; files run in name order, all inside one transaction.
 */
#[Signature('metrics:refresh')]
#[Description('Rebuild every summary table from the raw business records')]
class RefreshMetricsCommand extends Command
{
    /**
     * Where the time of the last successful refresh is kept. `app:doctor`
     * compares it with the newest business record to spot stale numbers.
     */
    private const STAMP_PATH = 'metrics/refreshed-at.txt';

    /**
     * Execute the console command.
     */
    public function handle(RefreshFinanceMetrics $finances): int
    {
        $files = glob(database_path('sql/metrics/*.sql')) ?: [];
        sort($files);

        DB::transaction(function () use ($files, $finances): void {
            foreach ($files as $file) {
                // Each file is a trusted multi-statement script from the repository, so it runs through PDO.
                if (basename($file) === '20_stats_finance_year.sql') {
                    $finances->handle();
                } else {
                    DB::connection()->getPdo()->exec((string) file_get_contents($file));
                }
                $this->components->twoColumnDetail(basename($file), '<fg=green;options=bold>DONE</>');
            }
        });

        Storage::disk('local')->put(self::STAMP_PATH, now()->toIso8601String());

        return self::SUCCESS;
    }

    /**
     * The time the last refresh finished, or null if it never ran here.
     */
    public static function lastRefreshedAt(): ?Carbon
    {
        $stamp = Storage::disk('local')->get(self::STAMP_PATH);

        return $stamp ? Carbon::parse(trim($stamp)) : null;
    }
}
