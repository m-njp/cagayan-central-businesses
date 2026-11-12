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
use App\Models\Municipality;
use App\Models\User;
use App\Services\FinanceEntryCells;
use App\Services\FinanceReportIssues;
use App\Services\FinanceYears;
use App\Services\PublicExecutiveSummaries;
use Illuminate\Database\Eloquent\Builder;

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
        private PublicExecutiveSummaries $summaries,
        private FinanceReportIssues $reportIssues,
    ) {}

    public function get(User $user, Municipality $town, int $year): FinanceReport
    {
        $entryYears = FinanceEntryCells::years($year);
        $sourceUrls = [];
        foreach ($this->summaries->files() as $key => $file) {
            $sourceUrls[$key.' '.$file['sha256']] = route('public.source-reports.show', $key, absolute: false);
        }
        $figures = FinanceFigure::query()
            ->select('finance_figures.*', 'l.label as line_label', 'l.group as line_group')
            ->join('finance_lines as l', 'l.code', '=', 'finance_figures.line_code')
            ->leftJoin('finance_documents as d', 'd.id', '=', 'finance_figures.finance_document_id')
            ->where('l.displayed', true)
            ->where('finance_figures.municipality_id', $town->id)
            ->where(fn (Builder $query) => $query->where('d.fiscal_year', $year)
                ->orWhere(fn (Builder $manual) => $manual->whereNull('finance_figures.finance_document_id')->where('finance_figures.fiscal_year', $year)))
            ->with(['document:id,kind,source_url,file,sha256', 'reviewer:id,name'])
            ->orderBy('l.sort')->orderByDesc('finance_figures.fiscal_year')->orderBy('finance_figures.basis')->orderBy('finance_figures.entry_method')
            ->get();

        $uploads = $this->uploadQuery->recent($town, $year);
        $documents = FinanceDocument::query()->where('municipality_id', $town->id)->where('fiscal_year', $year)->orderBy('kind')->get();
        $issues = [];
        foreach ($this->reportIssues->get($documents, $town->psgc_code, $year) as $documentIssues) {
            foreach ($documentIssues as $issue) {
                $issues[serialize($issue)] = new FinanceIssue($issue['severity'], $issue['kind'], $issue['message'], $issue['location']);
            }
        }

        return new FinanceReport(
            town: new SelectOption($town->psgc_code, $town->name),
            year: $year,
            canReview: $user->can('finance-review'),
            figures: array_values($figures->map(fn (FinanceFigure $figure) => $this->row($figure, $user, $sourceUrls))->all()),
            issues: array_values($issues),
            documents: array_values($documents
                ->map(fn (FinanceDocument $document) => new FinanceDocumentLink(
                    $document->kind->value, self::KINDS[$document->kind->value],
                    $document->availability === FinanceAvailability::Available, $this->documentUrl($document, $sourceUrls),
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
                'lastYear' => min($year, app(FinanceYears::class)->reportLastYear()),
                'noteMax' => (int) config('cagayan.finances.note_max'),
                'reviewBatchMax' => (int) config('cagayan.finances.review_batch_max'),
                'changesMax' => (int) config('cagayan.finances.entry_changes_max'),
            ],
        );
    }

    /** @param array<string, string> $sourceUrls */
    private function row(FinanceFigure $figure, User $user, array $sourceUrls): FinanceFigureRow
    {
        $manual = $figure->entry_method === FinanceEntryMethod::Manual;
        $kind = $figure->document?->kind;
        $sourceUrl = $figure->source_url ?? $this->documentUrl($figure->document, $sourceUrls);
        if ($sourceUrl !== null && preg_match('/^page ([1-9][0-9]*)$/', $figure->location ?? '', $page)) {
            $sourceUrl = explode('#', $sourceUrl)[0].'#page='.$page[1];
        }

        return new FinanceFigureRow(
            id: $figure->public_id,
            label: (string) $figure->getAttribute('line_label'),
            fiscalYear: $figure->fiscal_year,
            basis: $figure->basis->value,
            value: $figure->value,
            source: $manual ? 'Entered by Hand' : self::KINDS[($kind ?? FinanceDocumentKind::ExecutiveSummary)->value],
            location: $figure->location,
            status: $figure->status->value,
            enteredByYou: $figure->entered_by === $user->id,
            reviewedBy: $figure->reviewer?->name,
            note: $figure->note,
            group: (string) $figure->getAttribute('line_group'),
            groupLabel: (string) config('cagayan.finances.groups.'.$figure->getAttribute('line_group').'.label'),
            sourceUrl: $sourceUrl,
        );
    }

    /** @param array<string, string> $sourceUrls */
    private function documentUrl(?FinanceDocument $document, array $sourceUrls): ?string
    {
        if ($document === null) {
            return null;
        }

        return $sourceUrls[hash('sha256', $document->file ?? '').' '.$document->sha256] ?? $document->source_url;
    }
}
