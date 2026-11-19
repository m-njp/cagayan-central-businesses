<?php

namespace App\Actions;

use App\Enums\FinanceBasis;
use App\Enums\FinanceEntryMethod;
use App\Enums\FinanceFigureStatus;
use App\Models\FinanceDocument;
use App\Models\FinanceFigure;
use App\Models\FinanceLine;
use Illuminate\Support\Str;

/**
 * Figures the parser read from one uploaded report. New or changed amounts wait as pending, credited to the uploader,
 * so another person signs them off (D35). An unchanged amount keeps its sign-off and only refreshes its citation.
 */
final class RecordParsedFigures
{
    /**
     * @param  list<array<string, mixed>>  $figures  Parser output, checked row by row before anything is written.
     * @return int The figures that now wait for sign-off.
     */
    public function handle(FinanceDocument $document, array $figures, ?int $uploaderId, ?string $extractorVersion): int
    {
        $enterable = FinanceLine::query()->enterable()->pluck('code')->flip();
        $existing = FinanceFigure::query()->where('finance_document_id', $document->id)->where('entry_method', FinanceEntryMethod::Parsed)->get()
            ->keyBy(fn (FinanceFigure $figure) => "{$figure->line_code} {$figure->fiscal_year} {$figure->basis->value}");
        $max = (string) config('cagayan.finances.value_max');
        $waiting = 0;
        foreach ($figures as $row) {
            $line = $row['line_code'] ?? null;
            $year = $row['fiscal_year'] ?? null;
            $basis = FinanceBasis::tryFrom((string) ($row['basis'] ?? ''));
            $value = $row['value'] ?? null;
            $magnitude = is_string($value) ? ltrim($value, '-') : null;
            if (! is_string($line) || ! isset($enterable[$line]) || ! is_int($year) || $basis === null
                || ! is_numeric($value) || ! is_numeric($magnitude) || ! is_numeric($max) || bccomp($magnitude, $max, 2) > 0) {
                continue;
            }
            $variant = is_string($row['definition_variant'] ?? null) ? $row['definition_variant'] : null;
            $details = [
                'source_label' => is_string($row['source_label'] ?? null) ? $row['source_label'] : null,
                'location' => mb_substr(is_string($row['location'] ?? null) ? $row['location'] : '', 0, (int) config('cagayan.finances.location_max')) ?: null,
                'inputs' => FinanceFigure::cleanInputs($row['inputs'] ?? null),
                'extractor_version' => $extractorVersion,
            ];
            $figure = $existing->get("{$line} {$year} {$basis->value}");
            $stored = $figure === null ? null : (string) $figure->value;

            if ($figure === null) {
                FinanceFigure::query()->create($details + [
                    'public_id' => (string) Str::uuid(), 'municipality_id' => $document->municipality_id,
                    'fiscal_year' => $year, 'line_code' => $line, 'basis' => $basis, 'value' => $value,
                    'definition_variant' => $variant, 'finance_document_id' => $document->id,
                    'entry_method' => FinanceEntryMethod::Parsed, 'status' => FinanceFigureStatus::Pending, 'entered_by' => $uploaderId,
                ]);
                $waiting++;
            } elseif (! is_numeric($stored) || bccomp($stored, $value, 2) !== 0 || $figure->definition_variant !== $variant) {
                $figure->update($details + [
                    'value' => $value, 'definition_variant' => $variant, 'status' => FinanceFigureStatus::Pending,
                    'entered_by' => $uploaderId, 'reviewed_by' => null, 'reviewed_on' => null,
                ]);
                $waiting++;
            } elseif ($figure->status === FinanceFigureStatus::Pending) {
                // A reviewed figure keeps the citation its reviewer saw; only Pending ones are refreshed.
                $figure->update($details);
            }
        }

        return $waiting;
    }
}
