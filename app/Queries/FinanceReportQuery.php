<?php

namespace App\Queries;

use App\Data\FinanceDocumentLink;
use App\Data\FinanceEntryCell;
use App\Data\FinanceFigureRow;
use App\Data\FinanceIssue;
use App\Data\FinanceLineOption;
use App\Data\FinanceReport;
use App\Data\SelectOption;
use App\Enums\FinanceAvailability;
use App\Enums\FinanceDocumentKind;
use App\Enums\FinanceEntryMethod;
use App\Models\FinanceDocument;
use App\Models\FinanceFigure;
use App\Models\FinanceLine;
use App\Models\FinanceUpload;
use App\Models\Municipality;
use App\Models\User;
use App\Services\FinanceEntryCells;
use App\Services\FinanceYears;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * One town and report year for review: its displayed figures, the parser's errors and warnings,
 * and the COA documents behind them. Manual figures without a document belong to their fiscal year.
 */
final class FinanceReportQuery
{
    public const array KINDS = [
        'executive_summary' => 'Executive Summary',
        'fs_workbook' => 'Financial Statements',
        'annex_workbook' => 'Annexes',
    ];

    public function __construct(
        private FinanceEntryCells $cells,
        private FinanceUploadQuery $uploadQuery,
    ) {}

    public function get(User $user, Municipality $town, int $year): FinanceReport
    {
        $entryYears = FinanceEntryCells::years($year);
        $figures = FinanceFigure::query()
            ->select('finance_figures.*', 'l.label as line_label', 'l.group as line_group')
            ->join('finance_lines as l', 'l.code', '=', 'finance_figures.line_code')
            ->leftJoin('finance_documents as d', 'd.id', '=', 'finance_figures.finance_document_id')
            ->where('l.displayed', true)
            ->where('finance_figures.municipality_id', $town->id)
            ->where(fn (Builder $query) => $query->where('d.fiscal_year', $year)
                ->orWhere(fn (Builder $manual) => $manual->whereNull('finance_figures.finance_document_id')->where('finance_figures.fiscal_year', $year)))
            ->with(['document:id,kind,source_url', 'reviewer:id,name'])
            ->orderBy('l.sort')->orderByDesc('finance_figures.fiscal_year')->orderBy('finance_figures.basis')->orderBy('finance_figures.entry_method')
            ->get();

        $uploads = $this->uploadQuery->recent($town, $year);

        return new FinanceReport(
            town: new SelectOption($town->psgc_code, $town->name),
            year: $year,
            canReview: $user->can('finance-review'),
            figures: array_values($figures->map(fn (FinanceFigure $figure) => $this->row($figure, $user))->all()),
            issues: [...$this->issues($town->psgc_code, $year), ...$this->uploadIssues($uploads)],
            documents: array_values(FinanceDocument::query()->where('municipality_id', $town->id)->where('fiscal_year', $year)->orderBy('kind')->get()
                ->map(fn (FinanceDocument $document) => new FinanceDocumentLink(
                    $document->kind->value, self::KINDS[$document->kind->value],
                    $document->availability === FinanceAvailability::Available, $document->source_url,
                ))->all()),
            lines: array_values(FinanceLine::query()->enterable()->orderBy('sort')->get(['code', 'label', 'group'])
                ->map(fn (FinanceLine $line) => new FinanceLineOption($line->code, $line->label,
                    config('cagayan.finances.groups.'.$line->getAttribute('group').'.bases', ['actual']),
                    (string) $line->getAttribute('group'), (string) config('cagayan.finances.groups.'.$line->getAttribute('group').'.label')))->all()),
            entryYears: $entryYears,
            cells: array_values($this->cells->current($town->id, $entryYears)->map(fn (FinanceFigure $figure) => new FinanceEntryCell(
                $figure->line_code, $figure->fiscal_year, $figure->basis->value, (string) $figure->value, $figure->status->value,
            ))->all()),
            uploads: $this->uploadQuery->rows($uploads),
            uploadKinds: array_map(fn (string $kind, array $item) => new SelectOption($kind, $item['label']),
                array_keys((array) config('cagayan.finances.uploads.kinds')), (array) config('cagayan.finances.uploads.kinds')),
            limits: [
                'firstYear' => (int) config('cagayan.finances.first_year'),
                'lastYear' => min($year, app(FinanceYears::class)->reportLastYear()),
                'noteMax' => (int) config('cagayan.finances.note_max'),
                'valueMax' => (string) config('cagayan.finances.value_max'),
                'sourceUrlMax' => (int) config('cagayan.finances.source_url_max'),
                'reviewBatchMax' => (int) config('cagayan.finances.review_batch_max'),
                'uploadMaxKb' => (int) config('cagayan.finances.uploads.file_max_kb'),
                'changesMax' => (int) config('cagayan.finances.entry_changes_max'),
            ],
        );
    }

    private function row(FinanceFigure $figure, User $user): FinanceFigureRow
    {
        $manual = $figure->entry_method === FinanceEntryMethod::Manual;
        $kind = $figure->document?->kind;

        return new FinanceFigureRow(
            id: $figure->public_id,
            line: $figure->line_code,
            label: (string) $figure->getAttribute('line_label'),
            fiscalYear: $figure->fiscal_year,
            basis: $figure->basis->value,
            value: $figure->value,
            variant: $figure->definition_variant,
            source: $manual ? 'Entered by Hand' : self::KINDS[($kind ?? FinanceDocumentKind::ExecutiveSummary)->value],
            location: $figure->location,
            status: $figure->status->value,
            manual: $manual,
            enteredByYou: $figure->entered_by === $user->id,
            reviewedOn: $figure->reviewed_on?->toDateString(),
            reviewedBy: $figure->reviewer?->name,
            note: $figure->note,
            group: (string) $figure->getAttribute('line_group'),
            groupLabel: (string) config('cagayan.finances.groups.'.$figure->getAttribute('line_group').'.label'),
            sourceUrl: $figure->source_url ?? $figure->document?->source_url,
        );
    }

    /**
     * The newest finished upload's errors and warnings, shown with the committed source checks.
     *
     * @param  Collection<int, FinanceUpload>  $uploads
     * @return list<FinanceIssue>
     */
    private function uploadIssues(Collection $uploads): array
    {
        $latest = $uploads->first(fn (FinanceUpload $upload) => $upload->issues !== null);

        return array_values(array_map(fn (array $issue) => new FinanceIssue(
            (string) $issue['severity'], 'upload', null, (string) $issue['message'], $issue['location'] ?? null,
        ), array_filter($latest->issues ?? [], fn (array $issue) => in_array($issue['severity'] ?? '', ['error', 'warning'], true))));
    }

    /**
     * Errors and warnings only; informational notes such as cent-level restatements stay in the CSV.
     *
     * @return list<FinanceIssue>
     */
    private function issues(string $psgc, int $year): array
    {
        $path = base_path("data/towns/finances/issues/{$year}.csv");
        $handle = is_file($path) ? fopen($path, 'r') : false;
        if ($handle === false) {
            return [];
        }

        $issues = [];
        $columns = fgetcsv($handle, escape: '') ?: [];
        while (($cells = fgetcsv($handle, escape: '')) !== false) {
            if (count($cells) !== count($columns)) {
                continue;
            }
            $row = array_combine($columns, $cells);
            if ($row['psgc_code'] === $psgc && in_array($row['severity'], ['error', 'warning'], true)) {
                $issues[] = new FinanceIssue($row['severity'], $row['kind'], $row['line_code'] ?: null, $row['message'], $row['location'] ?: null);
            }
        }
        fclose($handle);
        usort($issues, fn (FinanceIssue $a, FinanceIssue $b) => [$a->severity, $a->kind] <=> [$b->severity, $b->kind]);

        return $issues;
    }
}
