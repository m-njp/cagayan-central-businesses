<?php

namespace App\Actions;

use App\Enums\FinanceAvailability;
use App\Enums\FinanceDocumentKind;
use App\Enums\FinanceEntryMethod;
use App\Enums\FinanceFigureStatus;
use App\Models\ActivityLog;
use App\Models\FinanceDocument;
use App\Models\FinanceFigure;
use App\Models\FinanceLine;
use App\Services\FinanceReportIssues;
use App\Services\FinanceSignOff;
use App\Services\RefreshFinanceMetrics;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Signs off parsed figures only when independent evidence agrees (D45, D47). Everything else stays Pending for a person.
 *
 * - Two reports: a year's own Executive Summary and the next one print the same amount, or amounts at most ₱1.00
 *   apart, for the same line, basis and definition (since 26-11-12, the ₱1.00 limit since 26-11-14). The later report
 *   prevails and the note records any difference; the extractor's restated finding keeps it visible to reviewers.
 * - Newest year: a government's latest available Executive Summary has no later one to confirm it, so its figure for
 *   that year is shown.
 *
 * An automatic sign-off that a current finding now holds (the rules tightened on 26-11-14) is reopened to Pending with
 * its automatically superseded companion. A person's sign-off is never reopened here.
 *
 * Neither rule applies when a blocking or error finding covers the figure, the same amount is recorded for another
 * government, or the figure changed more than three times from the year before.
 */
final class CorroborateFinanceFigures
{
    /** Findings that hold automatic sign-off even as warnings: a person must confirm them first. */
    private const array HOLDING_KINDS = ['implausible_change', 'duplicate_across_governments'];

    /** Two reports this close agree for sign-off (owner, 26-11-14); every difference is still flagged for review. */
    private const string AGREEMENT_LIMIT = '1.00';

    public function __construct(
        private readonly FinanceSignOff $signOff,
        private readonly RefreshFinanceMetrics $metrics,
        private readonly FinanceReportIssues $issues,
    ) {}

    /** @return array{corroborated: array<int, int>, newest: array<int, int>, reopened: int} Pending figures signed off, by fiscal year */
    public function handle(bool $dryRun = false, bool $reopenOnly = false): array
    {
        return DB::transaction(function () use ($dryRun, $reopenOnly): array {
            $figures = FinanceFigure::query()
                ->whereIn('line_code', FinanceLine::query()->where('displayed', true)->select('code'))
                ->where('entry_method', FinanceEntryMethod::Parsed)
                ->whereIn('status', [FinanceFigureStatus::Pending, FinanceFigureStatus::Verified, FinanceFigureStatus::Superseded])
                ->whereNotNull('finance_document_id')
                ->with(['document', 'municipality:id,psgc_code'])
                ->get();
            $held = $this->heldFigures($figures);
            $reopened = $this->reopenHeld($figures, $held, $dryRun);
            $signed = [];
            $newest = [];
            $today = now()->timezone(config('cagayan.display_timezone'))->toDateString();
            foreach ($reopenOnly ? [] : $this->corroboratedPairs($figures, $held) as [$current, $prior]) {
                foreach ([$current, $prior] as $figure) {
                    if ($figure->status !== FinanceFigureStatus::Pending) {
                        continue;
                    }
                    $signed[$figure->fiscal_year] = ($signed[$figure->fiscal_year] ?? 0) + 1;
                    if (! $dryRun) {
                        $this->signOff->verify($figure, null, $today, $this->corroborationNote($current, $prior));
                    }
                }
            }
            foreach ($reopenOnly ? [] : $this->newestYearFigures($figures, $held) as $figure) {
                if ($figure->status !== FinanceFigureStatus::Pending) {
                    continue;
                }
                $newest[$figure->fiscal_year] = ($newest[$figure->fiscal_year] ?? 0) + 1;
                if (! $dryRun) {
                    $this->signOff->verify($figure, null, $today, sprintf(
                        'Newest year: only the %d COA report prints this figure so far; the %d report will confirm or restate it',
                        $figure->fiscal_year, $figure->fiscal_year + 1,
                    ));
                }
            }
            ksort($signed);
            ksort($newest);
            if (! $dryRun && ($signed !== [] || $newest !== [] || $reopened > 0)) {
                $this->metrics->handle();
                ActivityLog::record('finance_figures_corroborated', null);
            }

            return ['corroborated' => $signed, 'newest' => $newest, 'reopened' => $reopened];
        });
    }

    /**
     * @param  Collection<int, FinanceFigure>  $figures
     * @param  array<int, true>  $held
     * @return list<array{FinanceFigure, FinanceFigure}> [the year's own report, the next report's prior column]
     */
    private function corroboratedPairs(Collection $figures, array $held): array
    {
        $byReport = $figures->groupBy(fn (FinanceFigure $figure): string => $this->seriesKey($figure).'|'.($figure->document->fiscal_year - $figure->fiscal_year));

        $pairs = [];
        foreach ($figures as $current) {
            if ($current->document->fiscal_year !== $current->fiscal_year) {
                continue;
            }
            foreach ($byReport->get($this->seriesKey($current).'|1', collect()) as $prior) {
                $bothPublishable = ! isset($held[$current->id]) && ! isset($held[$prior->id]);
                $eitherPending = $current->status === FinanceFigureStatus::Pending || $prior->status === FinanceFigureStatus::Pending;
                if ($bothPublishable && $eitherPending && $this->agree($current, $prior)) {
                    $pairs[] = [$current, $prior];
                }
            }
        }

        return $pairs;
    }

    /**
     * Returns automatic sign-offs that a current finding holds to Pending, with the companions they superseded.
     *
     * @param  Collection<int, FinanceFigure>  $figures
     * @param  array<int, true>  $held
     */
    private function reopenHeld(Collection $figures, array $held, bool $dryRun): int
    {
        $automatic = fn (FinanceFigure $figure): bool => $figure->reviewed_by === null
            && (str_starts_with((string) $figure->note, 'Corroborated') || str_starts_with((string) $figure->note, 'Newest year'));
        $reopen = $figures->filter(fn (FinanceFigure $figure): bool => isset($held[$figure->id])
            && $figure->status === FinanceFigureStatus::Verified && $automatic($figure));
        $companions = $figures->filter(fn (FinanceFigure $figure): bool => $figure->status === FinanceFigureStatus::Superseded && $automatic($figure)
            && $reopen->contains(fn (FinanceFigure $verified): bool => $verified->municipality_id === $figure->municipality_id
                && $verified->fiscal_year === $figure->fiscal_year && $verified->line_code === $figure->line_code && $verified->basis === $figure->basis));
        if (! $dryRun) {
            foreach ($reopen->merge($companions) as $figure) {
                $figure->update(['status' => FinanceFigureStatus::Pending, 'reviewed_by' => null, 'reviewed_on' => null, 'note' => null]);
            }
        }

        return $reopen->count();
    }

    private function agree(FinanceFigure $current, FinanceFigure $prior): bool
    {
        $size = ltrim($this->difference($current, $prior) ?? '', '-');

        return is_numeric($size) && bccomp($size, self::AGREEMENT_LIMIT, 2) <= 0;
    }

    /** The later report's amount less the earlier one's, or null when either is not a decimal string. */
    private function difference(FinanceFigure $current, FinanceFigure $prior): ?string
    {
        $later = (string) $prior->value;
        $earlier = (string) $current->value;

        return is_numeric($later) && is_numeric($earlier) ? bcsub($later, $earlier, 2) : null;
    }

    private function corroborationNote(FinanceFigure $current, FinanceFigure $prior): string
    {
        $years = [$current->document->fiscal_year, $prior->document->fiscal_year];
        $difference = $this->difference($current, $prior) ?? '0.00';
        if (ltrim($difference, '-0.') === '') {
            return sprintf('Corroborated: the %d and %d COA reports print the same amount', ...$years);
        }

        return sprintf(
            'Corroborated within ₱1.00: the %d report prints %s and the %d report %s, a difference of %s; the %d report prevails',
            $years[0], $this->peso((string) $current->value), $years[1], $this->peso((string) $prior->value),
            $this->peso(ltrim($difference, '-')), $years[1],
        );
    }

    /** ₱1,234,567.89 from a decimal string, grouping digits without converting money to a float. */
    private function peso(string $amount): string
    {
        $negative = str_starts_with($amount, '-');
        [$whole, $cents] = array_pad(explode('.', ltrim($amount, '-')), 2, '00');
        $grouped = strrev(implode(',', str_split(strrev($whole), 3)));

        return ($negative ? '-' : '').'₱'.$grouped.'.'.str_pad(substr($cents, 0, 2), 2, '0');
    }

    /** One line of one government in one kind of report. */
    private function seriesKey(FinanceFigure $figure): string
    {
        return implode('|', [
            $figure->municipality_id, $figure->document->kind->value, $figure->fiscal_year, $figure->line_code,
            $figure->basis->value, $figure->definition_variant ?? '',
        ]);
    }

    /**
     * Pending figures from a government's latest available report of their kind, printed for that report's own year,
     * that pass the newest-year evidence rule.
     *
     * @param  Collection<int, FinanceFigure>  $figures
     * @param  array<int, true>  $held
     * @return Collection<int, FinanceFigure>
     */
    private function newestYearFigures(Collection $figures, array $held): Collection
    {
        $latest = FinanceDocument::query()->where('availability', FinanceAvailability::Available)
            ->selectRaw('municipality_id, kind, max(fiscal_year) as latest')->groupBy('municipality_id', 'kind')->get()
            ->mapWithKeys(fn (FinanceDocument $document): array => ["{$document->municipality_id}|{$document->kind->value}" => (int) $document->getAttribute('latest')]);

        return $figures->filter(fn (FinanceFigure $figure): bool => $figure->status === FinanceFigureStatus::Pending
            && ! isset($held[$figure->id])
            && $figure->document->kind === FinanceDocumentKind::ExecutiveSummary
            && $figure->document->fiscal_year === $figure->fiscal_year
            && ($latest["{$figure->municipality_id}|{$figure->document->kind->value}"] ?? null) === $figure->fiscal_year)->values();
    }

    /**
     * Figures a finding holds back from any automatic sign-off.
     *
     * @param  Collection<int, FinanceFigure>  $figures
     * @return array<int, true>
     */
    private function heldFigures(Collection $figures): array
    {
        $held = [];
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
                    $onThisLine = $issue['line'] === $figure->line_code
                        && (! isset($issue['fiscal_year']) || $issue['fiscal_year'] === $figure->fiscal_year);
                    if ($this->issues->blocksVerification($issue, $figure->line_code, $figure->fiscal_year)
                        || ($issue['severity'] === 'error' && $onThisFigure)
                        || (in_array($issue['kind'], self::HOLDING_KINDS, true) && $onThisLine)) {
                        $held[$figure->id] = true;
                        break;
                    }
                }
            }
        }

        return $held;
    }
}
