<?php

namespace App\Actions;

use App\Enums\FinanceDocumentKind;
use App\Enums\FinanceEntryMethod;
use App\Enums\FinanceFigureStatus;
use App\Models\ActivityLog;
use App\Models\FinanceDocument;
use App\Models\FinanceFigure;
use App\Services\FinanceReportIssues;
use App\Services\FinanceSignOff;
use App\Services\RefreshFinanceMetrics;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Signs off Executive Summary figures that two COA reports print identically (owner direction, 26-11-12): a year's
 * own report and the next report's prior-year column, to the cent, for the same line, basis and definition, with no
 * blocking or error finding on that line or document in either report. Everything else stays Pending for a person.
 */
final class CorroborateFinanceFigures
{
    public function __construct(
        private readonly FinanceSignOff $signOff,
        private readonly RefreshFinanceMetrics $metrics,
        private readonly FinanceReportIssues $issues,
    ) {}

    /** @return array<int, int> Pending figures signed off, by the fiscal year they describe */
    public function handle(bool $dryRun = false): array
    {
        return DB::transaction(function () use ($dryRun): array {
            $signed = [];
            $today = now()->timezone(config('cagayan.display_timezone'))->toDateString();
            foreach ($this->corroboratedPairs() as [$current, $prior]) {
                foreach ([$current, $prior] as $figure) {
                    if ($figure->status !== FinanceFigureStatus::Pending) {
                        continue;
                    }
                    $signed[$figure->fiscal_year] = ($signed[$figure->fiscal_year] ?? 0) + 1;
                    if (! $dryRun) {
                        $this->signOff->verify($figure, null, $today, sprintf(
                            'Corroborated: the %d and %d COA reports print the same amount',
                            $current->document->fiscal_year, $prior->document->fiscal_year,
                        ));
                    }
                }
            }
            ksort($signed);
            if (! $dryRun && $signed !== []) {
                $this->metrics->handle();
                ActivityLog::record('finance_figures_corroborated', null);
            }

            return $signed;
        });
    }

    /** @return list<array{FinanceFigure, FinanceFigure}> [the year's own report, the next report's prior column] */
    private function corroboratedPairs(): array
    {
        $figures = FinanceFigure::query()
            ->where('entry_method', FinanceEntryMethod::Parsed)
            ->whereIn('status', [FinanceFigureStatus::Pending, FinanceFigureStatus::Verified, FinanceFigureStatus::Superseded])
            ->whereHas('document', fn (Builder $document) => $document->where('kind', FinanceDocumentKind::ExecutiveSummary))
            ->with(['document', 'municipality:id,psgc_code'])
            ->get();
        $byReport = $figures->groupBy(fn (FinanceFigure $figure): string => implode('|', [
            $figure->municipality_id, $figure->fiscal_year, $figure->line_code, $figure->basis->value,
            $figure->definition_variant ?? '', $figure->document->fiscal_year - $figure->fiscal_year,
        ]));
        $blocked = $this->blockedFigures($figures);

        $pairs = [];
        foreach ($figures as $current) {
            if ($current->document->fiscal_year !== $current->fiscal_year) {
                continue;
            }
            $key = implode('|', [$current->municipality_id, $current->fiscal_year, $current->line_code, $current->basis->value, $current->definition_variant ?? '']);
            foreach ($byReport->get($key.'|1', collect()) as $prior) {
                $bothPublishable = ! isset($blocked[$current->id]) && ! isset($blocked[$prior->id]);
                $eitherPending = $current->status === FinanceFigureStatus::Pending || $prior->status === FinanceFigureStatus::Pending;
                // Both values use the decimal:2 cast, so equal strings mean equal to the cent.
                if ($bothPublishable && $eitherPending && $current->value === $prior->value) {
                    $pairs[] = [$current, $prior];
                }
            }
        }

        return $pairs;
    }

    /**
     * @param  Collection<int, FinanceFigure>  $figures
     * @return array<int, true>
     */
    private function blockedFigures(Collection $figures): array
    {
        $blocked = [];
        foreach ($figures->groupBy(fn (FinanceFigure $figure): string => $figure->municipality->psgc_code.'|'.$figure->document->fiscal_year) as $group) {
            /** @var FinanceFigure $first */
            $first = $group->first();
            /** @var Collection<int, FinanceDocument> $documents */
            $documents = $group->pluck('document')->unique('id')->values();
            $issues = $this->issues->get($documents, $first->municipality->psgc_code, $first->document->fiscal_year);
            foreach ($group as $figure) {
                foreach ($issues[$figure->finance_document_id] ?? [] as $issue) {
                    $onThisFigure = ($issue['line'] === '' || $issue['line'] === $figure->line_code)
                        && (! isset($issue['fiscal_year']) || $issue['fiscal_year'] === $figure->fiscal_year);
                    if ($this->issues->blocksVerification($issue, $figure->line_code, $figure->fiscal_year)
                        || ($issue['severity'] === 'error' && $onThisFigure)) {
                        $blocked[$figure->id] = true;
                        break;
                    }
                }
            }
        }

        return $blocked;
    }
}
