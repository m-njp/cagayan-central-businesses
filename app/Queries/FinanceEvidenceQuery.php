<?php

namespace App\Queries;

use App\Data\PublicFinanceCitation;
use App\Enums\FinanceFigureStatus;
use App\Models\FinanceFigure;
use App\Models\FinanceLine;
use App\Services\PublicExecutiveSummaries;
use App\Services\PublicWorkbooks;
use Illuminate\Support\Collection;
use stdClass;

/** Public source metadata only. No reviewer identity, internal notes, or pending values leave the ledger. */
final class FinanceEvidenceQuery
{
    public function __construct(private PublicExecutiveSummaries $summaries, private PublicWorkbooks $workbooks) {}

    /**
     * @param  Collection<int, stdClass>  $stats
     * @param  array<string, list<string>>  $dependencies
     * @return array{sources: array<string, array<string, mixed>>, references: array<string, list<string>>}
     */
    public function get(Collection $stats, array $dependencies): array
    {
        $sources = [];
        $inputs = [];
        $figures = FinanceFigure::query()->where('status', FinanceFigureStatus::Verified)
            ->whereIn('line_code', FinanceLine::query()->where('displayed', true)->select('code'))
            ->with(['document', 'municipality'])->get();
        $files = $this->publicFiles($figures);
        foreach ($figures as $figure) {
            $key = $figure->public_id;
            $sources[$key] = get_object_vars($this->citation($figure, $files[$figure->finance_document_id] ?? null)) + [
                'inputs' => FinanceFigure::cleanInputs($figure->inputs) ?? [],
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

    /** @return list<PublicFinanceCitation> */
    public function publicCatalog(): array
    {
        $originals = $this->summaries->files();
        $figures = $this->catalogFigures();
        $files = $this->publicFiles($figures);
        $citations = [];
        foreach ($originals as $key => $file) {
            $citations[$key] = new PublicFinanceCitation(
                documentKey: $key,
                kind: 'executive_summary',
                label: 'Executive Summary',
                town: $file['town'],
                fiscalYear: $file['year'],
                reportYear: $file['year'],
                page: null,
                location: null,
                url: route('public.source-reports.show', $key, absolute: false),
            );
        }
        // Every registered workbook, not only those with a signed-off figure, so each one can be downloaded.
        foreach ($this->workbooks->files() as $key => $workbook) {
            $originals[$key] = $workbook;
            $citations[$key] = new PublicFinanceCitation(
                documentKey: $key,
                kind: $workbook['kind'],
                label: $workbook['label'],
                town: $workbook['town'],
                fiscalYear: $workbook['year'],
                reportYear: $workbook['year'],
                page: null,
                location: null,
                url: route('public.source-workbooks.show', $key, absolute: false),
            );
        }
        foreach ($figures as $figure) {
            $file = $files[$figure->finance_document_id] ?? null;
            if ($file === null) {
                continue;
            }
            $citation = $this->citation($figure, $file);
            if (isset($originals[$citation->documentKey ?? ''])) {
                continue;
            }
            $key = "{$citation->town}:{$citation->reportYear}:{$citation->documentKey}:{$citation->page}";
            $citations[$key] ??= $citation;
        }

        return array_values($citations);
    }

    /** @return Collection<int, FinanceFigure> */
    public function catalogFigures(): Collection
    {
        return FinanceFigure::query()->where('status', FinanceFigureStatus::Verified)
            ->whereNotNull('finance_document_id')->with(['document', 'municipality'])->orderBy('id')
            ->get(['id', 'public_id', 'status', 'finance_document_id', 'municipality_id', 'fiscal_year', 'location']);
    }

    /**
     * @param  Collection<int, FinanceFigure>  $figures
     * @return array<int, string|null>
     */
    private function publicFiles(Collection $figures): array
    {
        $files = [];
        foreach ($figures->unique('finance_document_id') as $figure) {
            if ($figure->finance_document_id !== null) {
                $files[$figure->finance_document_id] = $figure->publicFile();
            }
        }

        return $files;
    }

    private function citation(FinanceFigure $figure, ?string $file): PublicFinanceCitation
    {
        $document = $figure->document;

        return new PublicFinanceCitation(
            documentKey: $file === null ? null : hash('sha256', (string) $document?->file),
            kind: $document?->file === null ? null : $document->kind->value,
            label: $document?->kind->label() ?? 'Entered Figure',
            town: $figure->municipality->name,
            fiscalYear: $figure->fiscal_year,
            reportYear: $document?->fiscal_year,
            page: $this->page($figure->location),
            location: $figure->location,
            url: $file === null ? null : route('public.finance-sources.show', $figure->public_id, absolute: false),
        );
    }

    /** The first page of a citation such as "page 2" or "pages 2-3"; sheet cells have none. */
    private function page(?string $location): ?int
    {
        return preg_match('/^pages?\s+(\d+)/i', (string) $location, $match) === 1 ? (int) $match[1] : null;
    }
}
