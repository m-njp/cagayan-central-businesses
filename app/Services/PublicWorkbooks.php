<?php

namespace App\Services;

use App\Rules\OfficialCoaUrl;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * Every registered COA workbook copy (data/towns/financial-statements/index.csv) that can be served publicly: an
 * official COA source, a path inside the register's folder and a file whose checksum still matches.
 */
final class PublicWorkbooks
{
    private const array LABELS = [
        'fs_workbook' => 'Financial Statements',
        'annex_workbook' => 'General Fund Annex',
        'aapsi_workbook' => 'AAPSI',
        'appendices_workbook' => 'Appendices',
    ];

    private readonly string $root;

    public function __construct(?string $root = null)
    {
        $this->root = $root ?? base_path('data/towns');
    }

    /** @return array<string, array{path: string, sha256: string, town: string, year: int, kind: string, label: string, name: string, archiveName: string}> */
    public function files(): array
    {
        $root = realpath($this->root);
        $register = $root === false ? false : $root.'/financial-statements/index.csv';
        if ($register === false || ! is_file($register) || ($handle = fopen($register, 'r')) === false) {
            return [];
        }

        try {
            $columns = array_map(fn ($column) => ltrim((string) $column, "\u{FEFF}"), fgetcsv($handle, escape: '') ?: []);
            $files = [];
            while (($cells = fgetcsv($handle, escape: '')) !== false) {
                if (count($columns) !== count($cells)) {
                    continue;
                }
                $row = array_combine($columns, array_map(strval(...), $cells));
                $relative = $row['file'] ?? '';
                $kind = $row['kind'] ?? '';
                if (! isset(self::LABELS[$kind])
                    || ! preg_match('~^financial-statements/\d{4}/[^/]+/[^/]+\.xlsx$~', $relative)
                    || ! str_starts_with($relative, 'financial-statements/'.($row['report_year'] ?? '').'/')
                    || in_array('..', explode('/', $relative), true) || ! preg_match('/^[a-f0-9]{64}$/', $row['sha256'] ?? '')
                    || ($row['municipality'] ?? '') === ''
                    || Validator::make(['url' => $row['source_url'] ?? null], ['url' => ['required', new OfficialCoaUrl]])->fails()) {
                    continue;
                }
                $path = realpath($root.'/'.$relative);
                if ($path === false || ! str_starts_with($path, $root.DIRECTORY_SEPARATOR.'financial-statements'.DIRECTORY_SEPARATOR) || ! is_file($path)) {
                    continue;
                }
                // The same key a cited workbook figure carries, so the library lists each workbook once.
                $files[hash('sha256', $relative)] = [
                    'path' => $path,
                    'sha256' => $row['sha256'],
                    'town' => $row['municipality'],
                    'year' => (int) $row['report_year'],
                    'kind' => $kind,
                    'label' => self::LABELS[$kind],
                    'name' => basename($path),
                    'archiveName' => Str::slug($row['municipality']).'/'.$row['report_year'].'/'.basename($path),
                ];
            }

            return $files;
        } finally {
            fclose($handle);
        }
    }

    /** @return array{path: string, name: string}|null The file to download, only while its checksum matches the register. */
    public function file(string $key): ?array
    {
        $file = $this->files()[$key] ?? null;

        return $file !== null && hash_equals($file['sha256'], hash_file('sha256', $file['path']) ?: '')
            ? ['path' => $file['path'], 'name' => $file['name']] : null;
    }
}
