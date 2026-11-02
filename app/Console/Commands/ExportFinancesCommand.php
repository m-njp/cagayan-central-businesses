<?php

namespace App\Console\Commands;

use App\Enums\FinanceEntryMethod;
use App\Enums\FinanceFigureStatus;
use App\Models\FinanceFigure;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Writes sign-offs and manual figures to data/towns/finances so a fresh database (composer db:reset)
 * gets the same verified ledger from finances:import. Reviewer accounts are not written: they differ per machine.
 */
#[Signature('finances:export {--path=data/towns/finances : Folder, relative to the project root}')]
#[Description('Save finance sign-offs and manual figures to the committed CSV files')]
final class ExportFinancesCommand extends Command
{
    public function handle(): int
    {
        $folder = base_path((string) $this->option('path'));

        $reviews = FinanceFigure::query()
            ->with(['municipality:id,psgc_code', 'document:id,fiscal_year,kind'])
            ->where('entry_method', FinanceEntryMethod::Parsed)
            ->where('status', '!=', FinanceFigureStatus::Pending)
            ->whereDoesntHave('document', fn ($document) => $document->uploaded())
            ->get()
            ->map(fn (FinanceFigure $figure) => [
                $figure->municipality->psgc_code, $figure->document?->fiscal_year, $figure->document?->kind->value,
                $figure->line_code, $figure->fiscal_year, $figure->basis->value, $figure->value,
                $figure->status === FinanceFigureStatus::Rejected ? 'rejected' : 'verified',
                $figure->reviewed_on?->toDateString(), $figure->note,
            ])
            ->sortBy(fn (array $row) => implode(' ', [$row[0], $row[1], $row[3], $row[4], $row[5]]))
            ->values()
            ->all();

        $manual = FinanceFigure::query()
            ->with(['municipality:id,psgc_code', 'document:id,fiscal_year,kind'])
            ->where('entry_method', FinanceEntryMethod::Manual)
            ->whereDoesntHave('document', fn ($document) => $document->uploaded())
            ->orderBy('public_id')
            ->get()
            ->map(fn (FinanceFigure $figure) => [
                $figure->public_id, $figure->municipality->psgc_code, $figure->fiscal_year, $figure->line_code,
                $figure->basis->value, $figure->value, $figure->definition_variant, $figure->document?->fiscal_year,
                $figure->document?->kind->value, $figure->source_url, $figure->location, $figure->note,
                $figure->status->value, $figure->reviewed_on?->toDateString(),
            ])
            ->all();

        $this->write("{$folder}/reviews.csv", ImportFinancesCommand::REVIEW_COLUMNS, $reviews);
        $this->write("{$folder}/manual-figures.csv", ImportFinancesCommand::MANUAL_COLUMNS, $manual);
        $this->info(sprintf('Saved %d sign-offs and %d manual figures.', count($reviews), count($manual)));

        return self::SUCCESS;
    }

    /**
     * @param  list<string>  $columns
     * @param  array<int, array<int, mixed>>  $rows
     */
    private function write(string $path, array $columns, array $rows): void
    {
        $handle = fopen($path, 'w');
        if ($handle === false) {
            throw new \RuntimeException("Cannot write {$path}");
        }
        fputcsv($handle, $columns, eol: "\n", escape: '');
        foreach ($rows as $row) {
            fputcsv($handle, $row, eol: "\n", escape: '');
        }
        fclose($handle);
    }
}
