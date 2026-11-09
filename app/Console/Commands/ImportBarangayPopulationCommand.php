<?php

namespace App\Console\Commands;

use App\Models\Barangay;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * The CSV is the source of truth for barangay population (data/barangays/README.md).
 * Nothing is written when any row is invalid.
 */
#[Signature('barangays:import {--path=data/barangays/tuguegarao-population.csv : CSV file, relative to the project root}')]
#[Description('Load barangay population from the CSV')]
final class ImportBarangayPopulationCommand extends Command
{
    private const array COLUMNS = ['psgc_code', 'barangay', 'year', 'population', 'source_url', 'checked_on'];

    public function handle(): int
    {
        $path = base_path((string) $this->option('path'));

        if (! is_file($path)) {
            $this->error("File not found: {$path}");

            return self::FAILURE;
        }

        /** @var array<string, int> $barangayIds */
        $barangayIds = Barangay::query()->pluck('id', 'psgc_code')->all();
        $rows = [];
        $errors = [];

        foreach (file($path, FILE_IGNORE_NEW_LINES) ?: [] as $index => $text) {
            if (trim($text) === '') {
                continue;
            }

            $line = str_getcsv($text, escape: '');

            if ($index === 0) {
                if ($line !== self::COLUMNS) {
                    $this->error('The header must be: '.implode(',', self::COLUMNS));

                    return self::FAILURE;
                }

                continue;
            }

            $lineNumber = $index + 1;
            $row = array_combine(self::COLUMNS, array_pad(array_map(fn ($cell) => trim((string) $cell), $line), count(self::COLUMNS), ''));

            $problem = match (true) {
                ! isset($barangayIds[$row['psgc_code']]) => "unknown barangay code {$row['psgc_code']}",
                ! ctype_digit($row['year']) => "year {$row['year']} is not a year",
                ! ctype_digit($row['population']) => "population {$row['population']} is not a whole number",
                ! str_starts_with($row['source_url'], 'https://') => 'source_url is missing or not https',
                isset($rows[$row['psgc_code']]) => 'repeats a barangay',
                default => null,
            };

            if ($problem !== null) {
                $errors[] = "Line {$lineNumber}: {$problem}";

                continue;
            }

            $rows[$row['psgc_code']] = [
                'population' => (int) $row['population'],
                'population_year' => (int) $row['year'],
                'population_source' => $row['source_url'],
            ];
        }

        if ($errors !== []) {
            foreach ($errors as $error) {
                $this->error($error);
            }
            $this->error('Nothing was imported. Fix the lines above and run it again.');

            return self::FAILURE;
        }

        DB::transaction(function () use ($rows): void {
            Barangay::query()->update(['population' => null, 'population_year' => null, 'population_source' => null]);

            foreach ($rows as $code => $values) {
                Barangay::query()->where('psgc_code', $code)->update($values);
            }
        });

        $this->info(sprintf('Imported population for %d barangays.', count($rows)));

        return self::SUCCESS;
    }
}
