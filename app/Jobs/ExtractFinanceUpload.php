<?php

namespace App\Jobs;

use App\Actions\RecordParsedFigures;
use App\Enums\FinanceAvailability;
use App\Enums\FinanceDocumentKind;
use App\Enums\FinanceEntryMethod;
use App\Enums\FinanceFigureStatus;
use App\Enums\FinanceUploadStatus;
use App\Models\ActivityLog;
use App\Models\FinanceDocument;
use App\Models\FinanceFigure;
use App\Models\FinanceLine;
use App\Models\FinanceUpload;
use App\Models\Municipality;
use App\Services\FinanceParserStream;
use App\Services\FinanceParsingProgress;
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

    public bool $failOnTimeout = true;

    public ?string $attemptId = null;

    public function __construct(public int $uploadId, ?string $attemptId = null)
    {
        $this->attemptId = $attemptId;
        $this->tries = (int) config('cagayan.finances.uploads.tries');
        $this->timeout = (int) config('cagayan.finances.uploads.timeout_seconds') + 15;
    }

    public function uniqueId(): string
    {
        return $this->uploadId.':'.$this->attemptId;
    }

    public function handle(RecordParsedFigures $record): void
    {
        $upload = DB::transaction(function (): ?FinanceUpload {
            $upload = FinanceUpload::query()->lockForUpdate()->findOrFail($this->uploadId);
            if ($upload->status !== FinanceUploadStatus::Queued || $upload->isStale()
                || ($this->attemptId !== null && ($upload->progress['attempt'] ?? null) !== $this->attemptId)) {
                return null;
            }
            $this->attemptId ??= $upload->progress['attempt'] ?? (string) Str::uuid();
            $snapshot = FinanceParsingProgress::initial($this->attemptId);
            $snapshot['startedAt'] = now()->toIso8601String();
            $upload->update(['status' => FinanceUploadStatus::Running, 'progress' => $snapshot]);

            return $upload;
        });
        if ($upload === null) {
            return;
        }
        $progress = new FinanceParsingProgress($upload->id, $this->attemptId, $upload->progress ?? []);
        $stream = new FinanceParserStream(fn (array $event) => $progress->accept($event), $upload->fiscal_year, $upload->kind, $upload->sha256);
        $disk = Storage::disk('local');
        $temporary = $disk->path(FinanceUpload::FOLDER.'/tmp/'.Str::uuid());
        $limits = config('cagayan.finances.uploads');

        try {
            $townName = Municipality::query()->whereKey($upload->municipality_id)->value('name');
            if (! is_string($townName) || $townName === '') {
                throw new \RuntimeException('The report municipality could not be resolved.');
            }
            $sourcePath = $disk->path($upload->path);
            $this->assertSourceHash($sourcePath, $upload->sha256);
            Process::path(base_path('tools/coa'))->env(['PYTHONIOENCODING' => 'utf-8'])->timeout((int) $limits['timeout_seconds'])->start([
                (string) $limits['python'], '-u', '-m', 'coa.intake', $sourcePath, $upload->kind,
                (string) $upload->fiscal_year, $temporary,
                '--members', (string) $limits['archive_members_max'], '--bytes', (string) $limits['archive_bytes_max'], '--events',
                '--pace', (string) $limits['pace_seconds'],
                '--town', $townName,
                '--province', (string) config('cagayan.finances.source_province'),
            ], function (string $type, string $chunk) use ($stream): void {
                if ($type === 'out') {
                    $stream->push($chunk);
                }
            })->wait()->throw();
            $this->assertSourceHash($sourcePath, $upload->sha256);
            $stream->finish();
            /** @var array{parser_version: string, figures: list<array<string, mixed>>, issues: list<array<string, mixed>>, children: list<array{kind: string, name: string, member: string, sha256: string}>, confidence?: int} $parsed */
            $parsed = $stream->result;
            foreach (['executive_summary', 'fs_workbook', 'annex_workbook'] as $kind) {
                $progress->accept(['type' => 'replace', 'document_kind' => $kind,
                    'figures' => array_values(array_filter($parsed['figures'], fn (array $row) => $row['document_kind'] === $kind))]);
            }
            $progress->accept(['type' => 'stage', 'stage' => 'saving', 'message' => 'Saving eligible figures for independent review.']);

            DB::transaction(function () use ($upload, $parsed, $temporary, $disk, $record, $progress): void {
                $current = FinanceUpload::query()->lockForUpdate()->findOrFail($upload->id);
                if ($current->status !== FinanceUploadStatus::Running || ($current->progress['attempt'] ?? null) !== $this->attemptId) {
                    throw new \RuntimeException('Parser attempt is no longer current.');
                }
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
                $issues = array_map(FinanceParsingProgress::issue(...), $parsed['issues']);
                $waiting = 0;
                $serverIntegrityError = false;
                foreach ($sources as $kind => $source) {
                    $document = FinanceDocument::query()->firstOrNew([
                        'municipality_id' => $upload->municipality_id, 'fiscal_year' => $upload->fiscal_year, 'kind' => $kind,
                    ]);
                    // A different file already on record is never overwritten from an upload.
                    if ($document->exists && $document->sha256 !== null && $document->sha256 !== $source['sha256']) {
                        $issues[] = ['severity' => 'error', 'code' => 'source_conflict', 'line' => '', 'location' => null, 'message' => sprintf(
                            'A different %s for %d is already on record, so its figures were not changed.',
                            FinanceDocumentKind::from($kind)->label(), $upload->fiscal_year,
                        )];
                        $serverIntegrityError = true;

                        continue;
                    }
                    $document->fill([
                        'availability' => FinanceAvailability::Available, 'file' => $document->file ?? $source['file'],
                        'sha256' => $source['sha256'], 'checked_on' => now()->toDateString(),
                    ])->save();
                    $figures = array_values(array_filter($parsed['figures'], fn (array $figure) => $figure['document_kind'] === $kind));
                    $found = [];
                    foreach ($figures as $figure) {
                        $found[implode(' ', [(string) $figure['line_code'], (string) $figure['fiscal_year'], (string) $figure['basis']])] = true;
                    }
                    // Only lines the parser still delivers can be omitted; legacy check-only rows stay as they are.
                    $previous = FinanceFigure::query()->where('finance_document_id', $document->id)
                        ->where('entry_method', FinanceEntryMethod::Parsed)->where('status', FinanceFigureStatus::Pending)
                        ->whereIn('line_code', FinanceLine::query()->enterable()->select('code'))
                        ->get(['line_code', 'fiscal_year', 'basis', 'location']);
                    foreach ($previous as $figure) {
                        if (! isset($found[implode(' ', [$figure->line_code, (string) $figure->fiscal_year, $figure->basis->value])])) {
                            $issues[] = ['severity' => 'error', 'code' => 'previous_figure_not_reproduced', 'line' => $figure->line_code,
                                'fiscal_year' => $figure->fiscal_year, 'location' => $figure->location,
                                'document_kind' => $kind, 'document_sha256' => $source['sha256'],
                                'message' => 'A previous pending figure was not reproduced by this parse. Inspect its source before review.'];
                            $serverIntegrityError = true;
                        }
                    }
                    $waiting += $record->handle($document, $figures, $upload->uploaded_by, $parsed['parser_version']);
                }

                $snapshot = $progress->snapshot;
                $snapshot['stage'] = 'done';
                $snapshot['found'] = count($parsed['figures']);
                $snapshot['confidence'] = $serverIntegrityError ? 0 : ($parsed['confidence'] ?? null);
                $snapshot['issues'] = array_slice($issues, 0, (int) config('cagayan.finances.uploads.progress_issues_max'));
                $snapshot['finishedAt'] = now()->toIso8601String();
                $snapshot['updatedAt'] = $snapshot['finishedAt'];
                $current->update([
                    'status' => FinanceUploadStatus::Done, 'figures' => $waiting, 'issues' => $issues,
                    'progress' => $snapshot,
                    'message' => $waiting === 0 ? 'No new or changed figures were found.' : null,
                ]);
                ActivityLog::record('finance_report_read', $upload->uploaded_by);
            });
        } catch (Throwable $exception) {
            report($exception);
            $this->failed($exception);
            throw $exception;
        } finally {
            File::deleteDirectory($temporary);
        }
    }

    private function assertSourceHash(string $path, string $expected): void
    {
        $actual = is_file($path) ? hash_file('sha256', $path) : false;
        if ($actual === false || ! hash_equals($expected, $actual)) {
            throw new \RuntimeException('Stored report checksum changed or its file is unavailable.');
        }
    }

    public function failed(?Throwable $exception): void
    {
        DB::transaction(function (): void {
            $upload = FinanceUpload::query()->lockForUpdate()->find($this->uploadId);
            if ($upload === null || ! in_array($upload->status, [FinanceUploadStatus::Queued, FinanceUploadStatus::Running], true)
                || ($this->attemptId !== null && ($upload->progress['attempt'] ?? null) !== $this->attemptId)) {
                return;
            }
            $snapshot = $upload->progress ?? FinanceParsingProgress::initial($this->attemptId ?? '');
            $snapshot['failedStage'] = $snapshot['stage'];
            $snapshot['stage'] = 'failed';
            $snapshot['errorCode'] = 'report_unreadable';
            $snapshot['confidence'] = 0;
            $snapshot['finishedAt'] = now()->toIso8601String();
            $upload->update(['status' => FinanceUploadStatus::Failed, 'progress' => $snapshot,
                'message' => 'The report could not be read. Check the file and retry parsing.']);
        });
    }
}
