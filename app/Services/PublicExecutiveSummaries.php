<?php

namespace App\Services;

use App\Rules\OfficialCoaUrl;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

final class PublicExecutiveSummaries
{
    private readonly string $root;

    public function __construct(?string $root = null)
    {
        $this->root = $root ?? base_path('data/towns/executive-summaries');
    }

    /** @return array<string, array{path: string, sha256: string, town: string, year: int, archiveName: string}> */
    public function files(): array
    {
        $root = realpath($this->root);
        if ($root === false || ! is_file($root.'/sources.csv')) {
            return [];
        }
        $handle = fopen($root.'/sources.csv', 'r');
        if ($handle === false) {
            return [];
        }

        try {
            $columns = array_map(strval(...), fgetcsv($handle, escape: '') ?: []);
            $files = [];
            while (($cells = fgetcsv($handle, escape: '')) !== false) {
                if (count($columns) !== count($cells)) {
                    continue;
                }
                $row = array_combine($columns, array_map(strval(...), $cells));
                $relative = $row['file'] ?? '';
                $year = $row['year'] ?? '';
                $hash = $row['sha256'] ?? '';
                if (! preg_match('~^\d{4}/[^/]+/[^/]+\.pdf$~', $relative)
                    || str_contains($relative, '\\') || in_array('..', explode('/', $relative), true)
                    || ! str_starts_with($relative, $year.'/') || ! preg_match('/^[a-f0-9]{64}$/', $hash)
                    || ($row['town'] ?? '') === ''
                    || Validator::make(['url' => $row['source_url'] ?? null], ['url' => ['required', new OfficialCoaUrl]])->fails()) {
                    continue;
                }
                $path = realpath($root.'/'.$relative);
                if ($path === false || ! str_starts_with($path, $root.DIRECTORY_SEPARATOR)
                    || ! is_file($path) || filesize($path) !== (int) ($row['bytes'] ?? 0)) {
                    continue;
                }
                $key = hash('sha256', 'executive-summaries/'.$relative);
                $files[$key] = [
                    'path' => $path,
                    'sha256' => $hash,
                    'town' => $row['town'],
                    'year' => (int) $year,
                    'archiveName' => Str::slug($row['town']).'/'.$year.'/'.basename($path),
                ];
            }

            return $files;
        } finally {
            fclose($handle);
        }
    }

    public function file(string $key): ?string
    {
        $file = $this->files()[$key] ?? null;

        return $file !== null && hash_equals($file['sha256'], hash_file('sha256', $file['path']) ?: '')
            ? $file['path'] : null;
    }
}
