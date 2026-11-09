<?php

namespace App\Console\Commands;

use App\Enums\TownMeasure;
use App\Models\Municipality;
use App\Models\TownFact;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * The CSV is the source of truth: every run replaces all town facts with the
 * file's rows, so editing the file and rerunning is the whole update process
 * (data/towns/README.md). Nothing is written when any row is invalid.
 */
#[Signature('towns:import {--path=data/towns/town-facts.csv : CSV file, relative to the project root}')]
#[Description('Load population and economic figures for each town from the CSV')]
final class ImportTownFactsCommand extends Command
{
    private const array COLUMNS = ['psgc_code', 'town', 'measure', 'year', 'value', 'source_url', 'checked_on'];

    public function handle(): int
    {
        $path = base_path((string) $this->option('path'));

        if (! is_file($path)) {
            $this->error("File not found: {$path}");

            return self::FAILURE;
        }

        /** @var array<string, int> $townIds */
        $townIds = Municipality::query()->pluck('id', 'psgc_code')->all();
        $now = now();
        $rows = [];
        $errors = [];
        $seen = [];

        $lines = file($path, FILE_IGNORE_NEW_LINES) ?: [];

        foreach ($lines as $index => $text) {
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
            $measure = TownMeasure::tryFrom($row['measure']);
            $key = "{$row['psgc_code']} {$row['measure']} {$row['year']}";

            $problem = match (true) {
                ! isset($townIds[$row['psgc_code']]) => "unknown town code {$row['psgc_code']}",
                $measure === null => "unknown measure {$row['measure']}",
                ! ctype_digit($row['year']) => "year {$row['year']} is not a year",
                ! is_numeric($row['value']) => "value {$row['value']} is not a number",
                ! str_starts_with($row['source_url'], 'https://') => 'source_url is missing or not https',
                $row['checked_on'] !== '' && ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $row['checked_on']) => 'checked_on must be YYYY-MM-DD',
                isset($seen[$key]) => "repeats line {$seen[$key]}",
                default => null,
            };

            if ($problem !== null) {
                $errors[] = "Line {$lineNumber}: {$problem}";

                continue;
            }

            $seen[$key] = $lineNumber;
            $rows[] = [
                'municipality_id' => $townIds[$row['psgc_code']],
                'measure' => $row['measure'],
                'year' => (int) $row['year'],
                'value' => $row['value'],
                'source_url' => $row['source_url'],
                'checked_on' => $row['checked_on'] === '' ? null : $row['checked_on'],
                'created_at' => $now,
                'updated_at' => $now,
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
            TownFact::query()->delete();

            foreach (array_chunk($rows, 500) as $chunk) {
                TownFact::query()->insert($chunk);
            }
        });

        $unchecked = count(array_filter($rows, fn (array $row) => $row['checked_on'] === null));
        $this->info(sprintf('Imported %d figures for %d towns. %d still need a checked_on date.', count($rows), count(array_unique(array_column($rows, 'municipality_id'))), $unchecked));

        return self::SUCCESS;
    }
}
