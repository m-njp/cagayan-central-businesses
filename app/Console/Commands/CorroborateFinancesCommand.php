<?php

namespace App\Console\Commands;

use App\Actions\CorroborateFinanceFigures;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('finances:corroborate {--dry-run : Count the figures without signing them off}')]
#[Description('Sign off Executive Summary figures that two COA reports print identically')]
final class CorroborateFinancesCommand extends Command
{
    public function handle(CorroborateFinanceFigures $corroborate): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $signed = $corroborate->handle($dryRun);
        $this->table(['Fiscal Year', 'Figures'], array_map(fn (int $year, int $count): array => [$year, $count], array_keys($signed), $signed));
        $total = array_sum($signed);
        $this->info($dryRun
            ? "{$total} Pending figures are corroborated. Nothing was signed off."
            : "Signed off {$total} corroborated figures. Run php artisan finances:export to save them to reviews.csv.");

        return self::SUCCESS;
    }
}
