<?php

namespace App\Services;

use App\Enums\FinanceUploadStatus;
use App\Models\FinanceUpload;

final class FinanceParsingProgress
{
    /** @var array<string, mixed> */
    public array $snapshot;

    private float $lastFlush = 0;

    /** @param array<string, mixed> $snapshot */
    public function __construct(private readonly int $uploadId, public readonly string $attempt, array $snapshot)
    {
        $this->snapshot = $snapshot;
    }

    /** @return array<string, mixed> */
    public static function initial(string $attempt): array
    {
        return ['attempt' => $attempt, 'stage' => 'queued', 'message' => 'Waiting for the parser.',
            'location' => null, 'current' => null, 'total' => null, 'engine' => null,
            'startedAt' => null, 'updatedAt' => now()->toIso8601String(), 'finishedAt' => null,
            'found' => 0, 'confidence' => null, 'rows' => [], 'issues' => [], 'activity' => [], 'errorCode' => null,
            'sequence' => 0];
    }

    /** @param array<string, mixed> $event */
    public function accept(array $event): void
    {
        $type = $event['type'] ?? null;
        if (! in_array($type, ['stage', 'scan', 'figure', 'replace', 'issue', 'failure'], true)) {
            throw new \RuntimeException('Unknown parser event.');
        }
        if (in_array($type, ['stage', 'scan'], true)) {
            $stage = $event['stage'] ?? null;
            if (! in_array($stage, ['inspecting', 'scanning', 'checking', 'saving'], true)) {
                throw new \RuntimeException('Invalid parser stage.');
            }
            $this->snapshot['stage'] = $stage;
            $this->snapshot['message'] = self::text($event['message'] ?? ($stage === 'checking' ? 'Checking report figures.' : 'Scanning report records.'));
            $this->snapshot['location'] = isset($event['location']) ? self::text($event['location']) : null;
            $this->snapshot['current'] = is_int($event['current'] ?? null) ? max(0, $event['current']) : null;
            $this->snapshot['total'] = is_int($event['total'] ?? null) ? max(0, $event['total']) : null;
            $this->snapshot['engine'] = in_array($event['engine'] ?? null, ['pdfplumber', 'pdfium'], true) ? $event['engine'] : null;
            $this->addActivity(['kind' => $type, 'message' => $this->snapshot['message'], 'location' => $this->snapshot['location']]);
        } elseif ($type === 'figure') {
            $this->putFigure($event['figure'] ?? [], (string) ($event['document_kind'] ?? ''));
            $this->snapshot['location'] = self::text($event['figure']['location'] ?? '');
            $this->snapshot['message'] = 'Adding a discovered figure.';
            $this->addActivity(['kind' => 'figure', 'line' => self::text($event['figure']['line_code']),
                'message' => $this->snapshot['message'], 'location' => $this->snapshot['location']]);
        } elseif ($type === 'replace') {
            $kind = (string) ($event['document_kind'] ?? '');
            $this->snapshot['rows'] = array_values(array_filter($this->snapshot['rows'], fn (array $row) => $row['documentKind'] !== $kind));
            foreach ($event['figures'] ?? [] as $row) {
                $this->putFigure($row, $kind);
            }
        } elseif ($type === 'issue') {
            $issue = self::issue($event['issue'] ?? []);
            $this->snapshot['issues'][] = $issue;
            // Info notes (such as workbook headings outside the catalog) stay in the report but out of the live feed.
            if ($issue['severity'] !== 'info') {
                $this->snapshot['message'] = $issue['message'];
                $this->addActivity(['kind' => 'issue', 'message' => $issue['message'], 'location' => $issue['location']]);
            }
        } else {
            $this->snapshot['errorCode'] = 'report_unreadable';
        }
        $this->snapshot['found'] = count($this->snapshot['rows']);
        $this->snapshot['activity'] = array_slice($this->snapshot['activity'], -(int) config('cagayan.finances.uploads.progress_events_max'));
        $this->snapshot['issues'] = array_slice($this->snapshot['issues'], 0, (int) config('cagayan.finances.uploads.progress_issues_max'));
        $this->flush($type !== 'scan');
    }

    /** @param array<string, mixed> $activity */
    private function addActivity(array $activity): void
    {
        $this->snapshot['sequence'] = ($this->snapshot['sequence'] ?? 0) + 1;
        $this->snapshot['activity'][] = $activity + ['sequence' => $this->snapshot['sequence'], 'at' => now()->toIso8601String()];
    }

    /** @param array<string, mixed> $row */
    private function putFigure(array $row, string $kind): void
    {
        if (! is_string($row['line_code'] ?? null) || ! is_int($row['fiscal_year'] ?? null)
            || ! is_string($row['value'] ?? null) || ! preg_match('/^-?\d+\.\d{2}$/D', $row['value'])
            || ! in_array($kind, ['executive_summary', 'fs_workbook', 'annex_workbook'], true)
            || ! in_array($row['basis'] ?? null, ['actual', 'budget_final'], true)) {
            throw new \RuntimeException('Invalid provisional figure.');
        }
        $key = $kind.' '.$row['line_code'].' '.$row['fiscal_year'].' '.$row['basis'];
        $figure = ['key' => $key, 'line' => self::text($row['line_code']), 'fiscalYear' => $row['fiscal_year'],
            'basis' => $row['basis'], 'value' => $row['value'], 'location' => self::text($row['location'] ?? ''), 'documentKind' => $kind];
        foreach ($this->snapshot['rows'] as $index => $earlier) {
            if ($earlier['key'] === $key) {
                $this->snapshot['rows'][$index] = $figure;

                return;
            }
        }
        if (count($this->snapshot['rows']) < (int) config('cagayan.finances.uploads.progress_figures_max')) {
            $this->snapshot['rows'][] = $figure;
        }
    }

    /** @param array<string, mixed> $issue
     * @return array{severity: string, code: string, line: string, message: string, location: ?string, document_kind?: string, document_sha256?: string, fiscal_year?: int}
     */
    public static function issue(array $issue): array
    {
        $kind = self::text($issue['kind'] ?? 'source_check');
        $message = match ($kind) {
            'engine_failed', 'digits_unconfirmed' => 'One PDF reader could not read or confirm this file.',
            'parser_failed', 'section_failed' => 'This section could not be read. Check its figures against the source.',
            // Uploads are stored under a random file name that means nothing to the reader.
            default => preg_replace('/^[\w.-]+\.xlsx[!:] ?/', '', self::text($issue['message'] ?? 'Check this figure against its source.')) ?? '',
        };

        $normalized = ['severity' => in_array($issue['severity'] ?? null, ['info', 'warning', 'error'], true) ? $issue['severity'] : 'warning',
            'code' => $kind, 'line' => self::text($issue['code'] ?? ''), 'message' => $message,
            'location' => isset($issue['page']) ? 'page '.(int) $issue['page'] : (isset($issue['location']) ? self::text($issue['location']) : null)];
        if (is_string($issue['document_kind'] ?? null)) {
            $normalized['document_kind'] = self::text($issue['document_kind']);
        }
        if (is_string($issue['document_sha256'] ?? null)) {
            $normalized['document_sha256'] = self::text($issue['document_sha256']);
        }
        $fiscalYear = $issue['fiscalYear'] ?? $issue['fiscal_year'] ?? null;
        if (is_int($fiscalYear) && $fiscalYear > 0) {
            $normalized['fiscal_year'] = $fiscalYear;
        }

        return $normalized;
    }

    public function flush(bool $force = false): void
    {
        $now = microtime(true);
        if (! $force && ($now - $this->lastFlush) * 1000 < (int) config('cagayan.finances.uploads.progress_flush_ms')) {
            return;
        }
        $this->snapshot['updatedAt'] = now()->toIso8601String();
        $updated = FinanceUpload::query()->whereKey($this->uploadId)->where('status', FinanceUploadStatus::Running)
            ->where('progress->attempt', $this->attempt)->update(['progress' => $this->snapshot]);
        if ($updated !== 1) {
            throw new \RuntimeException('Parser attempt is no longer current.');
        }
        $this->lastFlush = $now;
    }

    private static function text(mixed $text): string
    {
        return mb_substr(preg_replace('/[\x00-\x1F\x7F]/u', ' ', strip_tags(is_string($text) ? $text : '')) ?? '', 0, 500);
    }
}
