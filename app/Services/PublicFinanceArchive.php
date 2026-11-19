<?php

namespace App\Services;

use App\Queries\FinanceEvidenceQuery;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;
use ZipArchive;

final class PublicFinanceArchive
{
    public function __construct(private FinanceEvidenceQuery $evidence, private PublicExecutiveSummaries $summaries, private PublicWorkbooks $workbooks) {}

    /** Build and verify the exact archive the public download route will send. */
    public function build(): string
    {
        $files = $this->files();
        if ($files === [] || ! class_exists(ZipArchive::class)) {
            throw new RuntimeException('No report archive is available.');
        }

        $path = tempnam(sys_get_temp_dir(), 'kita-coa-');
        if ($path === false) {
            throw new RuntimeException('Could not allocate the report archive.');
        }

        try {
            $this->write($path, $files);
            $this->verify($path, $files);

            return $path;
        } catch (Throwable $error) {
            unlink($path);

            throw $error;
        }
    }

    /** @return array<string, array{path: string, sha256: string}> */
    private function files(): array
    {
        $files = [];
        $bytes = 0;
        foreach ($this->summaries->files() as $file) {
            $files[$file['archiveName']] = ['path' => $file['path'], 'sha256' => $file['sha256']];
            $bytes += (int) filesize($file['path']);
        }
        // Every registered workbook (26-11-14), so the download matches the report library.
        foreach ($this->workbooks->files() as $file) {
            $files[$file['archiveName']] = ['path' => $file['path'], 'sha256' => $file['sha256']];
            $bytes += (int) filesize($file['path']);
        }
        foreach ($this->evidence->catalogFigures()->unique('finance_document_id') as $figure) {
            $path = $figure->publicFile();
            $document = $figure->document;
            if ($path === null || $document === null) {
                continue;
            }

            $name = Str::slug($figure->municipality->name).'/'.$document->fiscal_year.'/'.basename($path);
            if (isset($files[$name]) && $files[$name]['path'] === $path) {
                continue;
            }
            $size = filesize($path);
            $hash = $document->sha256;
            if ($size === false || $hash === null || isset($files[$name])) {
                throw new RuntimeException('A report could not be matched to the verified catalog.');
            }

            $bytes += $size;
            $files[$name] = ['path' => $path, 'sha256' => $hash];
        }
        if ($bytes > (int) config('cagayan.finances.source_archive_max_bytes')) {
            throw new RuntimeException('The report archive exceeds its size limit.');
        }

        ksort($files);

        return $files;
    }

    /** @param array<string, array{path: string, sha256: string}> $files */
    private function write(string $path, array $files): void
    {
        $zip = new ZipArchive;
        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Could not open the report archive.');
        }

        try {
            $manifest = [];
            foreach ($files as $name => $file) {
                if (! $zip->addFile($file['path'], $name) || ! $zip->setCompressionName($name, ZipArchive::CM_STORE)) {
                    throw new RuntimeException('Could not add a report to the archive.');
                }
                $manifest[$name] = $file['sha256'];
            }
            if (! $zip->addFromString('manifest.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR))) {
                throw new RuntimeException('Could not write the report manifest.');
            }
        } finally {
            if (! $zip->close()) {
                throw new RuntimeException('Could not finish the report archive.');
            }
        }
    }

    /** @param array<string, array{path: string, sha256: string}> $files */
    private function verify(string $path, array $files): void
    {
        $zip = new ZipArchive;
        if ($zip->open($path, ZipArchive::CHECKCONS) !== true) {
            throw new RuntimeException('The report archive could not be read.');
        }

        try {
            foreach ($files as $name => $file) {
                $stream = $zip->getStream($name);
                if ($stream === false) {
                    throw new RuntimeException('A report is missing from the archive.');
                }
                try {
                    $hash = hash_init('sha256');
                    hash_update_stream($hash, $stream);
                } finally {
                    fclose($stream);
                }
                if (! hash_equals($file['sha256'], hash_final($hash))) {
                    throw new RuntimeException('A report differs from its approved checksum.');
                }
            }
        } finally {
            $zip->close();
        }
    }
}
