<?php

namespace App\Queries;

use App\Enums\FinanceFigureStatus;
use App\Models\FinanceFigure;
use Illuminate\Support\Collection;
use stdClass;

/** Public source metadata only. No reviewer identity, internal notes, or pending values leave the ledger. */
final class FinanceEvidenceQuery
{
    /**
     * @param  Collection<int, stdClass>  $stats
     * @param  array<string, list<string>>  $dependencies
     * @return array{sources: array<string, array<string, mixed>>, references: array<string, list<string>>}
     */
    public function get(Collection $stats, array $dependencies): array
    {
        $sources = [];
        $inputs = [];
        foreach (FinanceFigure::query()->where('status', FinanceFigureStatus::Verified)
            ->with(['document', 'municipality'])->get() as $figure) {
            $key = $figure->public_id;
            $document = $figure->document;
            // The stored COA report is served by public.finance-sources.show; the page goes in the link's fragment.
            $sources[$key] = [
                'kind' => $document?->file === null ? null : $document->kind->value,
                'label' => $document?->kind->label() ?? 'Entered Figure',
                'town' => $figure->municipality->name,
                'fiscalYear' => $figure->fiscal_year,
                'reportYear' => $document?->fiscal_year,
                'page' => $this->page($figure->location),
                'location' => $figure->location,
                // Contributing cells and amounts, so a summed figure can be checked against the workbook.
                'inputs' => FinanceFigure::cleanInputs($figure->inputs) ?? [],
                // Uploaded reports stay private (D42), so they get no public link.
                'url' => $document?->publicSourceFile() === null
                    ? null : route('public.finance-sources.show', $key, absolute: false),
            ];
            $inputs["{$figure->municipality_id} {$figure->fiscal_year} {$figure->line_code}:{$figure->basis->value}"] = $key;
        }
        $references = [];
        $townStats = $stats->whereNotNull('municipality_id');
        foreach ($townStats as $row) {
            $refs = [];
            foreach ($dependencies[$row->metric] ?? [$row->metric] as $input) {
                $input = str_contains($input, ':') ? $input : "$input:actual";
                $key = $inputs["{$row->municipality_id} {$row->fiscal_year} {$input}"] ?? null;
                if ($key !== null) {
                    $refs[] = $key;
                }
            }
            $references["{$row->municipality_id} {$row->fiscal_year} {$row->metric}"] = array_values(array_unique($refs));
        }
        $townsBySeries = $townStats->groupBy(fn (stdClass $row) => "{$row->fiscal_year} {$row->metric}");
        foreach ($stats->whereNull('municipality_id') as $sum) {
            $refs = [];
            $series = "{$sum->fiscal_year} {$sum->metric}";
            foreach ($townsBySeries->get($series, []) as $row) {
                foreach ($references["{$row->municipality_id} {$series}"] ?? [] as $key) {
                    $refs[$key] = true;
                }
            }
            $references["0 {$series}"] = array_keys($refs);
        }
        // Send only evidence used by a published observation, deduplicated across all views.
        $used = array_unique(array_merge([], ...array_values($references)));

        return ['sources' => array_intersect_key($sources, array_flip($used)), 'references' => $references];
    }

    /** The first page of a citation such as "page 2" or "pages 2-3"; sheet cells have none. */
    public static function page(?string $location): ?int
    {
        return preg_match('/^pages?\s+(\d+)/i', (string) $location, $match) === 1 ? (int) $match[1] : null;
    }
}
