<?php

namespace App\Console\Commands;

use App\Enums\FinanceAvailability;
use App\Enums\FinanceBasis;
use App\Enums\FinanceDocumentKind;
use App\Enums\FinanceEntryMethod;
use App\Enums\FinanceFigureStatus;
use App\Models\FinanceDocument;
use App\Models\FinanceFigure;
use App\Models\FinanceLine;
use App\Models\Municipality;
use App\Rules\OfficialCoaUrl;
use App\Services\FinanceSignOff;
use App\Services\FinanceYears;
use App\Services\RefreshFinanceMetrics;
use Carbon\CarbonInterface;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Loads data/towns/finances into the ledger (D35). Parsed figures arrive pending; reviews.csv and
 * manual-figures.csv replay sign-offs exported by finances:export. Manual rows already in the database
 * are never deleted, and a parsed figure whose value changed goes back to pending.
 * Nothing is written when any file has an invalid row.
 */
#[Signature('finances:import {--path=data/towns/finances : Folder, relative to the project root}')]
#[Description('Load COA finance figures, documents, and sign-offs into the finance ledger')]
final class ImportFinancesCommand extends Command
{
    public const array LINE_COLUMNS = ['code', 'parent_code', 'label', 'group', 'unit', 'displayed', 'source', 'aliases'];

    public const array DOCUMENT_COLUMNS = ['psgc_code', 'town', 'fiscal_year', 'kind', 'availability', 'file', 'source_url', 'sha256', 'checked_on'];

    public const array EXTRACTED_COLUMNS = ['psgc_code', 'town', 'reported_in_year', 'document_kind', 'fiscal_year', 'line_code', 'basis', 'value', 'definition_variant', 'source_label', 'location', 'document_sha256', 'extractor_version'];

    public const array REVIEW_COLUMNS = ['psgc_code', 'reported_in_year', 'document_kind', 'line_code', 'fiscal_year', 'basis', 'value', 'decision', 'reviewed_on', 'note'];

    public const array MANUAL_COLUMNS = ['public_id', 'psgc_code', 'fiscal_year', 'line_code', 'basis', 'value', 'definition_variant', 'reported_in_year', 'document_kind', 'source_url', 'location', 'note', 'status', 'reviewed_on'];

    /** @var list<string> */
    private array $errors = [];

    /** @var list<string> */
    private array $warnings = [];

    public function handle(FinanceSignOff $signOff, RefreshFinanceMetrics $metrics): int
    {
        $folder = base_path((string) $this->option('path'));
        /** @var array<string, int> $towns */
        $towns = Municipality::query()->pluck('id', 'psgc_code')->all();

        $lines = $this->read("{$folder}/lines.csv", self::LINE_COLUMNS);
        $documents = $this->read("{$folder}/documents.csv", self::DOCUMENT_COLUMNS);
        $extracted = [];
        foreach (glob("{$folder}/extracted/*.csv") ?: [] as $file) {
            $extracted = [...$extracted, ...$this->read($file, self::EXTRACTED_COLUMNS)];
        }
        // Available workbooks load only for the configured towns; a not-published record still says why a year is missing.
        $workbookTowns = (array) config('cagayan.finances.workbook_towns');
        $loads = fn (string $town, string $kind, string $availability = 'available') => $kind === FinanceDocumentKind::ExecutiveSummary->value
            || $availability !== 'available' || in_array($town, $workbookTowns, true);
        $documents = array_values(array_filter($documents, fn (array $item) => $loads($item['row']['psgc_code'], $item['row']['kind'], $item['row']['availability'])));
        // Executive Summary lines beyond the totals load only for the configured towns (metrics.md M35).
        $detailTowns = (array) config('cagayan.finances.summary_detail_towns');
        $detailLines = (array) config('cagayan.finances.summary_detail_lines');
        $extracted = array_values(array_filter($extracted, fn (array $item) => $loads($item['row']['psgc_code'], $item['row']['document_kind'])
            && (! in_array($item['row']['line_code'], $detailLines, true) || in_array($item['row']['psgc_code'], $detailTowns, true))));
        $reviews = $this->read("{$folder}/reviews.csv", self::REVIEW_COLUMNS, required: false);
        $manual = $this->read("{$folder}/manual-figures.csv", self::MANUAL_COLUMNS, required: false);

        $codes = array_flip(array_column(array_column($lines, 'row'), 'code'));
        $this->validateLines($lines);
        $documentKeys = $this->validateDocuments($documents, $towns);
        $this->validateExtracted($extracted, $towns, $codes, $documentKeys);
        $this->validateReviews($reviews, $codes);
        $this->validateManual($manual, $towns, array_column(array_column($lines, 'row'), 'group', 'code'), $documentKeys);

        if ($this->errors !== []) {
            foreach ($this->errors as $error) {
                $this->error($error);
            }
            $this->error('Nothing was imported. Fix the lines above and run it again.');

            return self::FAILURE;
        }

        $counts = DB::transaction(function () use ($signOff, $metrics, $lines, $documents, $extracted, $reviews, $manual, $towns): array {
            $counts = $this->write($signOff, $lines, $documents, $extracted, $reviews, $manual, $towns);
            $metrics->handle();

            return $counts;
        });

        foreach ($this->warnings as $warning) {
            $this->warn($warning);
        }
        $this->info(sprintf(
            'Loaded %d documents and %d parsed figures (%d new, %d changed back to pending, %d removed). %d manual figures. %d figures verified.',
            count($documents), count($extracted), $counts['new'], $counts['changed'], $counts['removed'], count($manual),
            FinanceFigure::query()->where('status', FinanceFigureStatus::Verified)->count(),
        ));

        return self::SUCCESS;
    }

    /**
     * @param  list<array{file: string, line: int, row: array<string, string>}>  $lines
     * @param  list<array{file: string, line: int, row: array<string, string>}>  $documents
     * @param  list<array{file: string, line: int, row: array<string, string>}>  $extracted
     * @param  list<array{file: string, line: int, row: array<string, string>}>  $reviews
     * @param  list<array{file: string, line: int, row: array<string, string>}>  $manual
     * @param  array<string, int>  $towns
     * @return array{new: int, changed: int, removed: int}
     */
    private function write(FinanceSignOff $signOff, array $lines, array $documents, array $extracted, array $reviews, array $manual, array $towns): array
    {
        $now = now();

        FinanceLine::query()->upsert(array_map(fn (array $item, int $sort) => [
            'code' => $item['row']['code'], 'parent_code' => $item['row']['parent_code'] ?: null, 'label' => $item['row']['label'],
            'group' => $item['row']['group'], 'unit' => $item['row']['unit'], 'displayed' => $item['row']['displayed'] === '1',
            'source' => $item['row']['source'], 'sort' => $sort + 1, 'created_at' => $now, 'updated_at' => $now,
        ], $lines, array_keys($lines)), ['code'], ['parent_code', 'label', 'group', 'unit', 'displayed', 'source', 'sort', 'updated_at']);

        FinanceDocument::query()->upsert(array_map(fn (array $item) => [
            'municipality_id' => $towns[$item['row']['psgc_code']], 'fiscal_year' => (int) $item['row']['fiscal_year'],
            'kind' => $item['row']['kind'], 'availability' => $item['row']['availability'], 'file' => $item['row']['file'] ?: null,
            'source_url' => $item['row']['source_url'] ?: null, 'sha256' => $item['row']['sha256'] ?: null,
            'checked_on' => $item['row']['checked_on'], 'created_at' => $now, 'updated_at' => $now,
        ], $documents), ['municipality_id', 'fiscal_year', 'kind'], ['availability', 'file', 'source_url', 'sha256', 'checked_on', 'updated_at']);

        $documentIds = $this->documentIds();
        $counts = $this->writeParsed($extracted, $towns, $documentIds, $now);

        foreach ($manual as $item) {
            $row = $item['row'];
            FinanceFigure::query()->updateOrCreate(['public_id' => $row['public_id']], [
                'municipality_id' => $towns[$row['psgc_code']], 'fiscal_year' => (int) $row['fiscal_year'], 'line_code' => $row['line_code'],
                'basis' => $row['basis'], 'value' => $row['value'], 'definition_variant' => $row['definition_variant'] ?: null,
                'finance_document_id' => $row['reported_in_year'] === '' ? null : $documentIds["{$row['psgc_code']} {$row['reported_in_year']} {$row['document_kind']}"],
                'source_url' => $row['source_url'] ?: null, 'location' => $row['location'] ?: null, 'note' => $row['note'] ?: null,
                'entry_method' => FinanceEntryMethod::Manual,
            ]);
        }

        usort($reviews, fn (array $a, array $b) => $a['row']['reported_in_year'] <=> $b['row']['reported_in_year']);
        foreach ($reviews as $item) {
            $this->applyReview($signOff, $item, $towns, $documentIds);
        }

        // Replayed after parsed reviews so manual corrections keep the last word (D35).
        usort($manual, fn (array $a, array $b) => [$a['row']['reviewed_on'], $a['row']['status'] !== 'superseded'] <=> [$b['row']['reviewed_on'], $b['row']['status'] !== 'superseded']);
        foreach ($manual as $item) {
            $row = $item['row'];
            $figure = FinanceFigure::query()->where('public_id', $row['public_id'])->firstOrFail();
            match ($row['status']) {
                'verified', 'superseded' => $signOff->verify($figure, null, $row['reviewed_on'], $row['note'] ?: null),
                'rejected' => $signOff->reject($figure, null, $row['reviewed_on'], $row['note'] ?: null),
                default => null,
            };
        }

        return $counts;
    }

    /**
     * @param  list<array{file: string, line: int, row: array<string, string>}>  $extracted
     * @param  array<string, int>  $towns
     * @param  array<string, int>  $documentIds
     * @return array{new: int, changed: int, removed: int}
     */
    private function writeParsed(array $extracted, array $towns, array $documentIds, CarbonInterface $now): array
    {
        $existing = FinanceFigure::query()
            ->where('entry_method', FinanceEntryMethod::Parsed)
            ->get(['id', 'finance_document_id', 'line_code', 'fiscal_year', 'basis', 'value', 'definition_variant', 'status'])
            ->keyBy(fn (FinanceFigure $figure) => "{$figure->finance_document_id} {$figure->line_code} {$figure->fiscal_year} {$figure->basis->value}");

        $inserts = [];
        $seen = [];
        $changed = 0;
        foreach ($extracted as $item) {
            $row = $item['row'];
            $documentId = $documentIds["{$row['psgc_code']} {$row['reported_in_year']} {$row['document_kind']}"];
            $key = "{$documentId} {$row['line_code']} {$row['fiscal_year']} {$row['basis']}";
            $seen[$key] = true;
            $details = [
                'source_label' => $row['source_label'] ?: null, 'location' => $row['location'] ?: null,
                'extractor_version' => $row['extractor_version'] ?: null,
            ];
            $figure = $existing->get($key);

            if ($figure === null) {
                $inserts[] = $details + [
                    'public_id' => (string) Str::uuid(), 'municipality_id' => $towns[$row['psgc_code']], 'fiscal_year' => (int) $row['fiscal_year'],
                    'line_code' => $row['line_code'], 'basis' => $row['basis'], 'value' => $row['value'],
                    'definition_variant' => $row['definition_variant'] ?: null, 'finance_document_id' => $documentId,
                    'entry_method' => FinanceEntryMethod::Parsed->value, 'status' => FinanceFigureStatus::Pending->value,
                    'created_at' => $now, 'updated_at' => $now,
                ];

                continue;
            }

            if (! $this->sameAmount($figure->value, $row['value']) || $figure->definition_variant !== ($row['definition_variant'] ?: null)) {
                $changed++;
                $figure->update($details + [
                    'value' => $row['value'], 'definition_variant' => $row['definition_variant'] ?: null,
                    'status' => FinanceFigureStatus::Pending, 'reviewed_by' => null, 'reviewed_on' => null,
                ]);
            } else {
                $figure->fill($details)->save();
            }
        }

        foreach (array_chunk($inserts, 500) as $chunk) {
            FinanceFigure::query()->insert($chunk);
        }

        $stale = $existing->reject(fn (FinanceFigure $figure, string $key) => isset($seen[$key]));
        foreach ($stale->where('status', FinanceFigureStatus::Verified) as $figure) {
            $this->warnings[] = "A verified figure is no longer in the extracted files and was removed: {$figure->line_code} {$figure->fiscal_year}.";
        }
        FinanceFigure::query()->whereKey($stale->pluck('id')->all())->delete();

        return ['new' => count($inserts), 'changed' => $changed, 'removed' => $stale->count()];
    }

    /**
     * @param  array{file: string, line: int, row: array<string, string>}  $item
     * @param  array<string, int>  $towns
     * @param  array<string, int>  $documentIds
     */
    private function applyReview(FinanceSignOff $signOff, array $item, array $towns, array $documentIds): void
    {
        $row = $item['row'];
        $figure = FinanceFigure::query()
            ->where('entry_method', FinanceEntryMethod::Parsed)
            ->where('finance_document_id', $documentIds["{$row['psgc_code']} {$row['reported_in_year']} {$row['document_kind']}"] ?? 0)
            ->where('line_code', $row['line_code'])
            ->where('fiscal_year', (int) $row['fiscal_year'])
            ->where('basis', $row['basis'])
            ->first();

        if ($figure === null || ! $this->sameAmount($figure->value, $row['value'])) {
            $this->warnings[] = "reviews.csv line {$item['line']}: the figure is missing or its value changed, so it stays pending. Review it again.";

            return;
        }

        $row['decision'] === 'verified'
            ? $signOff->verify($figure, null, $row['reviewed_on'], $row['note'] ?: null)
            : $signOff->reject($figure, null, $row['reviewed_on'], $row['note'] ?: null);
    }

    /**
     * @return array<string, int>
     */
    private function documentIds(): array
    {
        return FinanceDocument::query()->with('municipality:id,psgc_code')->get(['id', 'municipality_id', 'fiscal_year', 'kind'])
            ->mapWithKeys(fn (FinanceDocument $document) => ["{$document->municipality->psgc_code} {$document->fiscal_year} {$document->kind->value}" => $document->id])
            ->all();
    }

    /**
     * @param  list<array{file: string, line: int, row: array<string, string>}>  $lines
     */
    private function validateLines(array $lines): void
    {
        $seen = [];
        foreach ($lines as ['line' => $line, 'row' => $row]) {
            $this->problem('lines.csv', $line, match (true) {
                $row['code'] === '' => 'code is empty',
                isset($seen[$row['code']]) => "repeats line {$seen[$row['code']]}",
                ! in_array($row['displayed'], ['0', '1'], true) => 'displayed must be 0 or 1',
                default => null,
            });
            $seen[$row['code']] = $line;
        }
    }

    /**
     * @param  list<array{file: string, line: int, row: array<string, string>}>  $documents
     * @param  array<string, int>  $towns
     * @return array<string, array{available: bool, sha256: string}>
     */
    private function validateDocuments(array $documents, array $towns): array
    {
        $keys = [];
        foreach ($documents as ['line' => $line, 'row' => $row]) {
            $key = "{$row['psgc_code']} {$row['fiscal_year']} {$row['kind']}";
            $available = $row['availability'] === FinanceAvailability::Available->value;
            $this->problem('documents.csv', $line, match (true) {
                ! isset($towns[$row['psgc_code']]) => "unknown town code {$row['psgc_code']}",
                ! ctype_digit($row['fiscal_year']) => "fiscal_year {$row['fiscal_year']} is not a year",
                FinanceDocumentKind::tryFrom($row['kind']) === null => "unknown kind {$row['kind']}",
                FinanceAvailability::tryFrom($row['availability']) === null => "unknown availability {$row['availability']}",
                $available && Validator::make(['source_url' => $row['source_url']], ['source_url' => ['required', 'url:https', 'max:'.config('cagayan.finances.source_url_max'), new OfficialCoaUrl]])->fails() => 'source_url must be an official HTTPS COA link',
                $available && ! preg_match('/^[0-9a-f]{64}$/', $row['sha256']) => 'sha256 must be 64 lower-case hex characters',
                ! $this->isDate($row['checked_on']) => 'checked_on must be YYYY-MM-DD',
                isset($keys[$key]) => 'repeats an earlier document for the same town, year, and kind',
                default => null,
            });
            $keys[$key] = ['available' => $available, 'sha256' => $row['sha256']];
        }

        return $keys;
    }

    /**
     * @param  list<array{file: string, line: int, row: array<string, string>}>  $extracted
     * @param  array<string, int>  $towns
     * @param  array<string, int>  $codes
     * @param  array<string, array{available: bool, sha256: string}>  $documents
     */
    private function validateExtracted(array $extracted, array $towns, array $codes, array $documents): void
    {
        $seen = [];
        foreach ($extracted as ['file' => $file, 'line' => $line, 'row' => $row]) {
            $document = $documents["{$row['psgc_code']} {$row['reported_in_year']} {$row['document_kind']}"] ?? null;
            $key = "{$row['psgc_code']} {$row['reported_in_year']} {$row['document_kind']} {$row['line_code']} {$row['fiscal_year']} {$row['basis']}";
            $this->problem($file, $line, match (true) {
                ! isset($towns[$row['psgc_code']]) => "unknown town code {$row['psgc_code']}",
                FinanceDocumentKind::tryFrom($row['document_kind']) === null => "unknown document_kind {$row['document_kind']}",
                $document === null || ! $document['available'] => 'no available document for this town, report year, and kind in documents.csv',
                $document['sha256'] !== $row['document_sha256'] => 'document_sha256 does not match documents.csv',
                ! isset($codes[$row['line_code']]) => "unknown line_code {$row['line_code']}",
                FinanceBasis::tryFrom($row['basis']) === null => "unknown basis {$row['basis']}",
                ! ctype_digit($row['fiscal_year']) => "fiscal_year {$row['fiscal_year']} is not a year",
                ! is_numeric($row['value']) => "value {$row['value']} is not a number",
                mb_strlen($row['location']) > 60 => 'location is longer than 60 characters',
                mb_strlen($row['source_label']) > 300 => 'source_label is longer than 300 characters',
                isset($seen[$key]) => "repeats line {$seen[$key]}",
                default => null,
            });
            $seen[$key] = $line;
        }
    }

    /**
     * @param  list<array{file: string, line: int, row: array<string, string>}>  $reviews
     * @param  array<string, int>  $codes
     */
    private function validateReviews(array $reviews, array $codes): void
    {
        foreach ($reviews as ['line' => $line, 'row' => $row]) {
            $this->problem('reviews.csv', $line, match (true) {
                ! isset($codes[$row['line_code']]) => "unknown line_code {$row['line_code']}",
                FinanceDocumentKind::tryFrom($row['document_kind']) === null => "unknown document_kind {$row['document_kind']}",
                FinanceBasis::tryFrom($row['basis']) === null => "unknown basis {$row['basis']}",
                ! is_numeric($row['value']) => "value {$row['value']} is not a number",
                ! in_array($row['decision'], ['verified', 'rejected'], true) => 'decision must be verified or rejected',
                ! $this->isDate($row['reviewed_on']) => 'reviewed_on must be YYYY-MM-DD',
                default => null,
            });
        }
    }

    /**
     * @param  list<array{file: string, line: int, row: array<string, string>}>  $manual
     * @param  array<string, int>  $towns
     * @param  array<array-key, string>  $codes
     * @param  array<string, array{available: bool, sha256: string}>  $documents
     */
    private function validateManual(array $manual, array $towns, array $codes, array $documents): void
    {
        $digits = strlen(explode('.', (string) config('cagayan.finances.value_max'))[0]);
        foreach ($manual as ['line' => $line, 'row' => $row]) {
            $hasDocument = $row['reported_in_year'] !== '';
            $status = FinanceFigureStatus::tryFrom($row['status']);
            $group = $codes[$row['line_code']] ?? '';
            $lastYear = $hasDocument ? (int) $row['reported_in_year'] : app(FinanceYears::class)->reportLastYear();
            $validation = Validator::make($row, [
                'fiscal_year' => ['required', 'integer', 'between:'.config('cagayan.finances.first_year').','.$lastYear],
                'basis' => [Rule::in(config("cagayan.finances.groups.{$group}.bases", []))],
                'value' => ['required', 'regex:/^\d{1,'.$digits.'}(\.\d{1,2})?$/', 'numeric', 'between:0,'.config('cagayan.finances.value_max')],
                'note' => ['max:'.config('cagayan.finances.note_max'), 'not_regex:/[\p{Cc}\p{Cf}<>]/u'],
                'source_url' => [$hasDocument ? 'nullable' : 'required', 'url:https', 'max:'.config('cagayan.finances.source_url_max'), new OfficialCoaUrl],
            ]);
            $this->problem('manual-figures.csv', $line, match (true) {
                ! Str::isUuid($row['public_id']) => 'public_id must be a UUID',
                ! isset($towns[$row['psgc_code']]) => "unknown town code {$row['psgc_code']}",
                ! isset($codes[$row['line_code']]) => "unknown line_code {$row['line_code']}",
                FinanceBasis::tryFrom($row['basis']) === null => "unknown basis {$row['basis']}",
                ! ctype_digit($row['fiscal_year']) => "fiscal_year {$row['fiscal_year']} is not a year",
                ! is_numeric($row['value']) => "value {$row['value']} is not a number",
                $hasDocument && ! ($documents["{$row['psgc_code']} {$row['reported_in_year']} {$row['document_kind']}"]['available'] ?? false) => 'the document is not available in documents.csv',
                ! $hasDocument && ! str_starts_with($row['source_url'], 'https://') => 'needs a document or an https source_url',
                $status === null => "unknown status {$row['status']}",
                $status !== FinanceFigureStatus::Pending && ! $this->isDate($row['reviewed_on']) => 'reviewed_on must be YYYY-MM-DD once reviewed',
                $validation->fails() => $validation->errors()->first(),
                default => null,
            });
        }
    }

    /**
     * @param  list<string>  $columns
     * @return list<array{file: string, line: int, row: array<string, string>}>
     */
    private function read(string $path, array $columns, bool $required = true): array
    {
        $name = Str::after($path, base_path((string) $this->option('path')).'/');
        if (! is_file($path)) {
            if ($required) {
                $this->errors[] = "{$name}: file not found";
            }

            return [];
        }

        $handle = fopen($path, 'r');
        if ($handle === false) {
            $this->errors[] = "{$name}: cannot be opened";

            return [];
        }

        $rows = [];
        $line = 0;
        while (($cells = fgetcsv($handle, escape: '')) !== false) {
            $line++;
            if ($line === 1) {
                if ($cells !== $columns) {
                    $this->errors[] = "{$name}: the header must be ".implode(',', $columns);

                    break;
                }

                continue;
            }
            if ($cells === [null]) {
                continue;
            }
            if (count($cells) > count($columns)) {
                $this->errors[] = "{$name} line {$line}: more cells than the header";

                continue;
            }
            $rows[] = ['file' => $name, 'line' => $line, 'row' => array_combine($columns, array_pad(array_map(fn ($cell) => trim((string) $cell), $cells), count($columns), ''))];
        }
        fclose($handle);

        return $rows;
    }

    private function problem(string $file, int $line, ?string $problem): void
    {
        if ($problem !== null) {
            $this->errors[] = "{$file} line {$line}: {$problem}";
        }
    }

    private function sameAmount(string $stored, string $file): bool
    {
        return is_numeric($stored) && is_numeric($file) && bccomp($stored, $file, 2) === 0;
    }

    private function isDate(string $value): bool
    {
        return (bool) preg_match('/^\d{4}-\d{2}-\d{2}$/', $value);
    }
}
