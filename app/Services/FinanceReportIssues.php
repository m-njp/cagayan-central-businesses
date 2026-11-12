<?php

namespace App\Services;

use App\Enums\FinanceUploadStatus;
use App\Models\FinanceDocument;
use App\Models\FinanceLine;
use App\Models\FinanceUpload;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/** @phpstan-type SourceIssue array{severity: string, kind: string, line: string, message: string, location: ?string, fiscal_year?: int} */
final class FinanceReportIssues
{
    private readonly string $root;

    public function __construct(?string $root = null)
    {
        $this->root = $root ?? base_path('data/towns/finances');
    }

    /**
     * @param  Collection<int, FinanceDocument>  $documents
     * @return array<int, list<SourceIssue>>
     */
    public function get(Collection $documents, string $psgc, int $year): array
    {
        $issues = $this->registeredIssues($documents, $psgc, $year);
        if ($documents->isEmpty()) {
            return $issues;
        }
        $hashes = $documents->pluck('sha256')->filter()->all();
        $knownLines = FinanceLine::query()->pluck('code')->flip()->all();
        $archives = [];
        foreach ($documents as $document) {
            if (preg_match('~^'.FinanceUpload::FOLDER.'/([a-f0-9-]{36})-(?:fs_workbook|annex_workbook)\.xlsx$~', $document->file ?? '', $match)) {
                $archives[] = $match[1];
            }
        }
        $uploads = FinanceUpload::query()->where('municipality_id', $documents->first()->municipality_id)
            ->where('fiscal_year', $year)
            ->where(fn (Builder $query) => $query->whereIn('sha256', $hashes)->orWhereIn('public_id', $archives))
            ->orderByDesc('updated_at')->orderByDesc('id')->get();

        foreach ($documents as $document) {
            $upload = $uploads->first(fn (FinanceUpload $upload) => $this->matches($upload, $document));
            if ($upload === null) {
                continue;
            }
            // Only a completed reparse of these same bytes can replace earlier findings.
            $issues[$document->id] = [];
            if ($upload->status !== FinanceUploadStatus::Done || $upload->issues === null) {
                $issues[$document->id][] = ['severity' => 'error', 'kind' => 'parser_incomplete', 'line' => '',
                    'message' => 'Parsing this source has not completed successfully. Retry parsing before verifying its parsed figures.', 'location' => null];

                continue;
            }
            foreach ($upload->issues ?? [] as $row) {
                if (! in_array($row['severity'] ?? '', ['error', 'warning'], true)) {
                    continue;
                }
                $kind = is_string($row['document_kind'] ?? null) ? $row['document_kind'] : null;
                if ($kind !== null && $kind !== $document->kind->value) {
                    if ($upload->kind !== 'archive') {
                        $issues[$document->id][] = $this->mismatchedEvidence();
                    }

                    continue;
                }
                $hash = is_string($row['document_sha256'] ?? null) ? $row['document_sha256'] : null;
                if ($hash !== null && $hash !== $document->sha256) {
                    $issues[$document->id][] = $this->mismatchedEvidence();

                    continue;
                }
                $issue = [
                    'severity' => (string) $row['severity'], 'kind' => (string) ($row['code'] ?? 'source_check'),
                    'line' => isset($knownLines[(string) ($row['line'] ?? '')]) ? (string) $row['line'] : '',
                    'message' => (string) ($row['message'] ?? 'Check this figure against its source.'),
                    'location' => is_string($row['location'] ?? null) ? $row['location'] : null,
                ];
                if (is_int($row['fiscal_year'] ?? null) && $row['fiscal_year'] > 0) {
                    $issue['fiscal_year'] = $row['fiscal_year'];
                }
                $issues[$document->id][] = $issue;
            }
        }

        return $issues;
    }

    /**
     * Every registered parser finding for one report year, or an empty list when the file is missing or incomplete.
     *
     * @return list<array<string, string>>
     */
    public function yearRows(int $year): array
    {
        return $this->rows($this->root."/issues/{$year}.csv", [
            'psgc_code', 'reported_in_year', 'severity', 'kind', 'line_code', 'message', 'location',
        ]) ?? [];
    }

    /** @param SourceIssue $issue */
    public function blocksVerification(array $issue, string $line, ?int $fiscalYear = null): bool
    {
        $documentWide = in_array($issue['kind'], [
            'checksum_mismatch', 'unsupported_units', 'unsupported_fund_scope', 'municipality_mismatch', 'municipality_unconfirmed',
            'document_year_mismatch', 'header_year_mismatch', 'header_year_context_changed', 'header_years_missing', 'header_years_repeated',
            'no_text_layer', 'parser_failed', 'issue_source_mismatch', 'digits_unconfirmed', 'engine_failed', 'no_financial_rows',
            'ambiguous_sheets', 'year_columns_ambiguous',
        ], true);
        if (! $documentWide) {
            if ($issue['line'] !== '' && $issue['line'] !== $line) {
                return false;
            }
            if ($fiscalYear !== null && isset($issue['fiscal_year']) && $issue['fiscal_year'] !== $fiscalYear) {
                return false;
            }
        }
        if (in_array($issue['kind'], ['balance_mismatch', 'net_income_mismatch', 'revenue_sources_mismatch', 'change_mismatch'], true)) {
            return false;
        }

        return $issue['severity'] === 'error' || in_array($issue['kind'], [
            'digits_unconfirmed', 'engine_failed', 'header_years_missing', 'header_years_repeated', 'found_after_section_end', 'line_not_found',
        ], true);
    }

    private function matches(FinanceUpload $upload, FinanceDocument $document): bool
    {
        return ($upload->kind === $document->kind->value && $upload->sha256 === $document->sha256)
            || ($upload->kind === 'archive' && $document->file === FinanceUpload::FOLDER."/{$upload->public_id}-{$document->kind->value}.xlsx");
    }

    /**
     * @param  Collection<int, FinanceDocument>  $documents
     * @return array<int, list<SourceIssue>>
     */
    private function registeredIssues(Collection $documents, string $psgc, int $year): array
    {
        $registered = [];
        foreach ($this->rows($this->root.'/documents.csv') ?? [] as $row) {
            if (($row['psgc_code'] ?? '') === $psgc && ($row['fiscal_year'] ?? '') === (string) $year
                && ($row['availability'] ?? '') === 'available') {
                $registered[$row['kind'] ?? ''] = $row;
            }
        }
        $matched = $documents->filter(function (FinanceDocument $document) use ($registered): bool {
            $row = $registered[$document->kind->value] ?? null;

            return $row !== null && $document->file !== null && $document->sha256 !== null
                && $row['file'] === $document->file && $row['sha256'] === $document->sha256;
        });
        $issues = array_fill_keys($documents->pluck('id')->all(), []);
        if ($matched->isEmpty()) {
            return $issues;
        }
        $diagnostics = $this->rows($this->root."/issues/{$year}.csv", [
            'psgc_code', 'reported_in_year', 'severity', 'kind', 'line_code', 'message', 'location',
        ]);
        if ($diagnostics !== null) {
            foreach ($diagnostics as $row) {
                if ($row['reported_in_year'] !== (string) $year) {
                    $diagnostics = null;
                    break;
                }
            }
        }
        if ($diagnostics === null) {
            foreach ($matched as $document) {
                $issues[$document->id][] = ['severity' => 'error', 'kind' => 'diagnostics_unavailable', 'line' => '',
                    'message' => 'The registered source checks are missing or incomplete. Parse the source again before verification.', 'location' => null];
            }

            return $issues;
        }
        /** @var array<string, string> $lineSources */
        $lineSources = FinanceLine::query()->pluck('source', 'code')->all();
        foreach ($diagnostics as $row) {
            if (($row['psgc_code'] ?? '') !== $psgc || ! in_array($row['severity'] ?? '', ['error', 'warning'], true)) {
                continue;
            }
            foreach ($matched as $document) {
                if (! $this->belongsToDocument($row, $document, $lineSources)) {
                    continue;
                }
                if (($row['document_sha256'] ?? '') !== '' && $row['document_sha256'] !== $document->sha256) {
                    $issues[$document->id][] = $this->mismatchedEvidence();

                    continue;
                }
                $issue = [
                    'severity' => $row['severity'], 'kind' => $row['kind'] ?? 'source_check',
                    'line' => isset($lineSources[$row['line_code'] ?? '']) ? $row['line_code'] : '',
                    'message' => $row['message'] ?? 'Check this figure against its source.', 'location' => ($row['location'] ?? '') ?: null,
                ];
                if (($row['fiscal_year'] ?? '') !== '') {
                    $issue['fiscal_year'] = (int) $row['fiscal_year'];
                }
                $issues[$document->id][] = $issue;
            }
        }

        return $issues;
    }

    /** @param array<string, string> $row
     * @param  array<string, string>  $lineSources
     */
    private function belongsToDocument(array $row, FinanceDocument $document, array $lineSources): bool
    {
        if (($row['document_kind'] ?? '') !== '') {
            return $row['document_kind'] === $document->kind->value;
        }
        $source = $lineSources[$row['line_code'] ?? ''] ?? '';
        $kind = match ($source) {
            'executive_summary' => 'executive_summary',
            'fs_workbook', 'fs_totals', 'fs_scbaa' => 'fs_workbook',
            'annex_workbook' => 'annex_workbook',
            default => null,
        };
        if ($kind !== null) {
            return $kind === $document->kind->value;
        }
        $issueKind = $row['kind'] ?? '';
        if (preg_match('/^(?:header_|engine_|digits_|no_highlights|found_after_section)/', $issueKind)) {
            return $document->kind->value === 'executive_summary';
        }
        if (str_contains($row['message'] ?? '', '.xlsx') || preg_match('/^(?:sheet_|year_columns_|national_share_|revenue_sources_)/', $issueKind)) {
            return $document->kind->value !== 'executive_summary'
                && (str_contains($row['message'] ?? '', basename($document->file ?? '')) || ! str_contains($row['message'] ?? '', '.xlsx'));
        }

        // Legacy issue rows without a kind or line cannot safely be narrowed further.
        return true;
    }

    /** @return SourceIssue */
    private function mismatchedEvidence(): array
    {
        return ['severity' => 'error', 'kind' => 'issue_source_mismatch', 'line' => '',
            'message' => 'The parser findings do not match this source document. Parse the source again before verification.', 'location' => null];
    }

    /** @param list<string> $requiredColumns
     * @return list<array<string, string>>|null
     */
    private function rows(string $path, array $requiredColumns = []): ?array
    {
        $contents = is_file($path) && is_readable($path) ? @file_get_contents($path) : false;
        if ($contents === false || ($requiredColumns !== [] && ($contents === '' || ! str_ends_with($contents, "\n") || substr_count($contents, '"') % 2 !== 0))) {
            return null;
        }
        $handle = fopen('php://temp', 'w+');
        if ($handle === false) {
            return null;
        }
        try {
            fwrite($handle, $contents);
            rewind($handle);
            $columns = array_map(strval(...), fgetcsv($handle, escape: '') ?: []);
            if (count(array_unique($columns)) !== count($columns) || array_diff($requiredColumns, $columns) !== []) {
                return null;
            }
            $rows = [];
            while (($cells = fgetcsv($handle, escape: '')) !== false) {
                if (count($cells) !== count($columns)) {
                    return null;
                }
                $row = array_combine($columns, array_map(strval(...), $cells));
                if ($requiredColumns !== [] && (! in_array($row['severity'], ['info', 'warning', 'error'], true)
                    || $row['kind'] === '' || $row['psgc_code'] === '' || ! ctype_digit($row['reported_in_year'])
                    || (($row['fiscal_year'] ?? '') !== '' && ! ctype_digit($row['fiscal_year'])))) {
                    return null;
                }
                $rows[] = $row;
            }

            return $rows;
        } finally {
            fclose($handle);
        }
    }
}
