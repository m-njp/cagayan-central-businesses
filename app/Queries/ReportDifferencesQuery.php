<?php

namespace App\Queries;

use App\Data\ReportDifferenceCell;
use App\Data\ReportDifferenceDetail;
use App\Data\ReportDifferenceItem;
use App\Data\ReportDifferenceRow;
use App\Data\ReportDifferences;
use App\Enums\FinanceAvailability;
use App\Enums\FinanceDocumentKind;
use App\Enums\FinanceFigureStatus;
use App\Models\FinanceDocument;
use App\Models\FinanceLine;
use App\Models\Municipality;
use App\Services\FinanceReportIssues;
use App\Services\PublicExecutiveSummaries;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Executive Summary findings by municipality and report year, from data/towns/finances/issues. Errors and warnings
 * count; of the informational notes only "between reports" ones do, such as a later report restating a year.
 */
final class ReportDifferencesQuery
{
    public const array FAMILIES = ['report', 'between_reports', 'reading'];

    public function __construct(
        private FinanceReportIssues $issues,
        private PublicExecutiveSummaries $summaries,
    ) {}

    public function get(): ReportDifferences
    {
        $documents = $this->documents()->get(['municipality_id', 'fiscal_year']);
        $years = array_values(array_unique(array_map(fn (FinanceDocument $document): int => $document->fiscal_year, $documents->all())));
        sort($years);
        $statuses = $this->statusCounts();
        $findings = [];
        foreach ($years as $year) {
            foreach ($this->findings($year) as $row) {
                $findings[$row['psgc_code']][$year][$this->family($row)] ??= 0;
                $findings[$row['psgc_code']][$year][$this->family($row)]++;
            }
        }
        $held = $documents->mapWithKeys(fn (FinanceDocument $document) => [$document->municipality_id.'|'.$document->fiscal_year => true]);

        $rows = Municipality::query()->orderBy('name')->get(['id', 'psgc_code', 'name'])
            ->map(function (Municipality $town) use ($years, $held, $findings, $statuses): ReportDifferenceRow {
                $cells = [];
                foreach ($years as $year) {
                    $counts = $findings[$town->psgc_code][$year] ?? [];
                    $status = $statuses[$town->id.'|'.$year] ?? [];
                    $cells[$year] = isset($held[$town->id.'|'.$year]) ? new ReportDifferenceCell(
                        $counts['report'] ?? 0, $counts['between_reports'] ?? 0, $counts['reading'] ?? 0,
                        $status[FinanceFigureStatus::Pending->value] ?? 0,
                        ($status[FinanceFigureStatus::Verified->value] ?? 0) + ($status[FinanceFigureStatus::Superseded->value] ?? 0),
                    ) : null;
                }

                return new ReportDifferenceRow($town->psgc_code, $town->name, $cells);
            });

        return new ReportDifferences($years, array_values($rows->all()));
    }

    public function detail(Municipality $town, int $year): ?ReportDifferenceDetail
    {
        $document = $this->documents()->where('municipality_id', $town->id)->where('fiscal_year', $year)->first();
        if ($document === null) {
            return null;
        }
        $file = $this->summaries->files()[hash('sha256', $document->file ?? '')] ?? null;
        $sourceUrl = $file !== null && $file['sha256'] === $document->sha256
            ? route('public.source-reports.show', hash('sha256', (string) $document->file), absolute: false) : null;
        $labels = FinanceLine::query()->pluck('label', 'code')->all();
        $status = $this->statusCounts($town->id, $year)[$town->id.'|'.$year] ?? [];

        $items = [];
        foreach ($this->findings($year) as $row) {
            if ($row['psgc_code'] !== $town->psgc_code) {
                continue;
            }
            $page = preg_match('/^page ([1-9][0-9]*)$/', $row['location'], $match) ? $match[1] : null;
            $items[] = new ReportDifferenceItem(
                family: $this->family($row),
                severity: $row['severity'],
                kind: $row['kind'],
                line: $labels[$row['line_code']] ?? null,
                fiscalYear: ($row['fiscal_year'] ?? '') !== '' ? (int) $row['fiscal_year'] : null,
                message: $row['message'],
                sourceUrl: $sourceUrl !== null && $page !== null ? $sourceUrl.'#page='.$page : $sourceUrl,
            );
        }
        usort($items, fn (ReportDifferenceItem $a, ReportDifferenceItem $b) => [array_search($a->family, self::FAMILIES, true), $a->severity === 'error' ? 0 : 1]
            <=> [array_search($b->family, self::FAMILIES, true), $b->severity === 'error' ? 0 : 1]);

        return new ReportDifferenceDetail(
            townCode: $town->psgc_code,
            town: $town->name,
            year: $year,
            sourceUrl: $sourceUrl,
            pending: $status[FinanceFigureStatus::Pending->value] ?? 0,
            verified: ($status[FinanceFigureStatus::Verified->value] ?? 0) + ($status[FinanceFigureStatus::Superseded->value] ?? 0),
            corroborated: $status['corroborated'] ?? 0,
            items: $items,
        );
    }

    /** @return Builder<FinanceDocument> */
    private function documents()
    {
        return FinanceDocument::query()->where('kind', FinanceDocumentKind::ExecutiveSummary)->where('availability', FinanceAvailability::Available);
    }

    /** @return list<array<string, string>> */
    private function findings(int $year): array
    {
        return array_values(array_filter($this->issues->yearRows($year), fn (array $row): bool => in_array($row['document_kind'] ?? '', ['', 'executive_summary'], true)
            && ($row['severity'] !== 'info' || $this->family($row) === 'between_reports')));
    }

    /** @param array<string, string> $row */
    private function family(array $row): string
    {
        return in_array($row['family'] ?? '', self::FAMILIES, true) ? $row['family'] : 'reading';
    }

    /** @return array<string, array<string, int>> Keyed by "municipality id|report year" */
    private function statusCounts(?int $townId = null, ?int $year = null): array
    {
        $rows = DB::table('finance_figures as f')
            ->join('finance_documents as d', 'd.id', '=', 'f.finance_document_id')
            ->where('d.kind', FinanceDocumentKind::ExecutiveSummary->value)
            ->when($townId !== null, fn ($query) => $query->where('f.municipality_id', $townId)->where('d.fiscal_year', $year))
            ->groupBy('f.municipality_id', 'd.fiscal_year', 'f.status')
            ->selectRaw("f.municipality_id, d.fiscal_year, f.status, count(*) as total, count(*) filter (where f.note like 'Corroborated:%') as corroborated")
            ->get();
        $counts = [];
        foreach ($rows as $row) {
            $key = $row->municipality_id.'|'.$row->fiscal_year;
            $counts[$key][$row->status] = (int) $row->total;
            $counts[$key]['corroborated'] = ($counts[$key]['corroborated'] ?? 0) + (int) $row->corroborated;
        }

        return $counts;
    }
}
