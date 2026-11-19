<?php

namespace App\Console\Commands;

use App\Actions\CorroborateFinanceFigures;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('finances:corroborate {--dry-run : Count the figures without signing them off} {--reopen-only : Return held automatic decisions to Pending without signing off new figures}')]
#[Description('Sign off figures that two COA reports print identically, and newest-year figures no later report can confirm yet')]
final class CorroborateFinancesCommand extends Command
{
    public function handle(CorroborateFinanceFigures $corroborate): int
    {
        $dryRun = (bool) $this->option('dry-run');
        ['corroborated' => $signed, 'newest' => $newest, 'reopened' => $reopened] = $corroborate->handle($dryRun, (bool) $this->option('reopen-only'));
        $this->table(['Fiscal Year', 'Corroborated', 'Newest Year'], array_map(
            fn (int $year): array => [$year, $signed[$year] ?? 0, $newest[$year] ?? 0],
            array_values(array_unique([...array_keys($signed), ...array_keys($newest)])),
        ));
        $total = array_sum($signed);
        $newestTotal = array_sum($newest);
        $this->info($dryRun
            ? "{$total} Pending figures are corroborated and {$newestTotal} are newest-year figures. Nothing was signed off."
            : "Signed off {$total} corroborated and {$newestTotal} newest-year figures. Run php artisan finances:export to save them to reviews.csv.");
        if ($reopened > 0) {
            $this->warn($dryRun
                ? "{$reopened} automatic sign-offs are now held by a finding and would return to Pending."
                : "Returned {$reopened} automatic sign-offs that a finding now holds to Pending.");
        }

        return self::SUCCESS;
    }
}
