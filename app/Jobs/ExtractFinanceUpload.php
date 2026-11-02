<?php

namespace App\Jobs;

use App\Actions\RecordParsedFigures;
use App\Enums\FinanceAvailability;
use App\Enums\FinanceDocumentKind;
use App\Enums\FinanceUploadStatus;
use App\Models\ActivityLog;
use App\Models\FinanceDocument;
use App\Models\FinanceUpload;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/** Reads one uploaded report with tools/coa (coa.intake), never the repository-wide extraction (D34). */
final class ExtractFinanceUpload implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries;

    public int $timeout;

    public int $uniqueFor = 600;

    public function __construct(public int $uploadId)
    {
        $this->tries = (int) config('cagayan.finances.uploads.tries');
        $this->timeout = (int) config('cagayan.finances.uploads.timeout_seconds') + 15;
    }

    public function uniqueId(): string
    {
        return (string) $this->uploadId;
    }

    public function handle(RecordParsedFigures $record): void
    {
        $upload = FinanceUpload::query()->findOrFail($this->uploadId);
        if (! in_array($upload->status, [FinanceUploadStatus::Queued, FinanceUploadStatus::Running], true)) {
            return;
        }
        $upload->update(['status' => FinanceUploadStatus::Running]);
        $disk = Storage::disk('local');
        $temporary = $disk->path(FinanceUpload::FOLDER.'/tmp/'.Str::uuid());
        $limits = config('cagayan.finances.uploads');

        try {
            $result = Process::path(base_path('tools/coa'))->timeout((int) $limits['timeout_seconds'])->run([
                (string) $limits['python'], '-m', 'coa.intake', $disk->path($upload->path), $upload->kind,
                (string) $upload->fiscal_year, $temporary,
                '--members', (string) $limits['archive_members_max'], '--bytes', (string) $limits['archive_bytes_max'],
            ])->throw();
            /** @var array{parser_version: string, figures: list<array<string, mixed>>, issues: list<array<string, mixed>>, children: list<array{kind: string, name: string, member: string, sha256: string}>} $parsed */
            $parsed = json_decode($result->output(), true, flags: JSON_THROW_ON_ERROR);

            DB::transaction(function () use ($upload, $parsed, $temporary, $disk, $record): void {
                // Each document kind the file carries: the report itself, or the workbooks inside an archive.
                $sources = $upload->kind === 'archive'
                    ? collect($parsed['children'])->mapWithKeys(function (array $child) use ($upload, $temporary, $disk): array {
                        $stored = FinanceUpload::FOLDER."/{$upload->public_id}-{$child['kind']}.xlsx";
                        $contents = File::get($temporary.'/'.basename($child['name']));
                        if (! hash_equals($child['sha256'], hash('sha256', $contents)) || ! $disk->put($stored, $contents)) {
                            throw new \RuntimeException('An archive workbook could not be stored.');
                        }

                        return [$child['kind'] => ['file' => $stored, 'sha256' => $child['sha256']]];
                    })->all()
                    : [$upload->kind => ['file' => $upload->path, 'sha256' => $upload->sha256]];

                // A PDF reader failure carries raw library text, which can name server paths; show a fixed sentence.
                $issues = array_map(fn (array $issue) => [
                    'severity' => (string) ($issue['severity'] ?? 'warning'),
                    'message' => in_array($issue['kind'] ?? null, ['engine_failed', 'digits_unconfirmed'], true)
                        ? 'One PDF reader could not read this file.' : (string) ($issue['message'] ?? ''),
                    'location' => isset($issue['page']) ? 'page '.$issue['page'] : ($issue['location'] ?? null),
                ], $parsed['issues']);
                $waiting = 0;
                foreach ($sources as $kind => $source) {
                    $document = FinanceDocument::query()->firstOrNew([
                        'municipality_id' => $upload->municipality_id, 'fiscal_year' => $upload->fiscal_year, 'kind' => $kind,
                    ]);
                    // A different file already on record is never overwritten from an upload.
                    if ($document->exists && $document->sha256 !== null && $document->sha256 !== $source['sha256']) {
                        $issues[] = ['severity' => 'error', 'location' => null, 'message' => sprintf(
                            'A different %s for %d is already on record, so its figures were not changed.',
                            FinanceDocumentKind::from($kind)->label(), $upload->fiscal_year,
                        )];

                        continue;
                    }
                    $document->fill([
                        'availability' => FinanceAvailability::Available, 'file' => $document->file ?? $source['file'],
                        'sha256' => $source['sha256'], 'checked_on' => now()->toDateString(),
                    ])->save();
                    $figures = array_values(array_filter($parsed['figures'], fn (array $figure) => $figure['document_kind'] === $kind));
                    $waiting += $record->handle($document, $figures, $upload->uploaded_by, $parsed['parser_version']);
                }

                $upload->update([
                    'status' => FinanceUploadStatus::Done, 'figures' => $waiting, 'issues' => $issues,
                    'message' => $waiting === 0 ? 'No new or changed figures were found.' : null,
                ]);
                ActivityLog::record('finance_report_read', $upload->uploaded_by);
            });
        } finally {
            File::deleteDirectory($temporary);
        }
    }

    public function failed(?Throwable $exception): void
    {
        FinanceUpload::query()->whereKey($this->uploadId)->update([
            'status' => FinanceUploadStatus::Failed,
            'message' => 'The report could not be read. Enter its figures by hand.',
        ]);
    }
}
