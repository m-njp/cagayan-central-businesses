<?php

namespace App\Services;

use App\Enums\FinanceFigureStatus;
use App\Models\Barangay;
use App\Models\Municipality;
use App\Models\PopulationFigure;
use App\Models\TownFact;

/**
 * What the population grid shows for each place in a census year: the newest pending proposal, else the published
 * count (town_facts for the town, barangays for each barangay). The grid prefills from it and a save is checked
 * against it, so both read the same rule.
 */
final class PopulationCells
{
    /**
     * @return array<string, array{name: string, barangay: int|null, value: int|null, status: string|null, source: string|null}>
     *                                                                                                                           Keyed "town" or a barangay PSGC code, town first then barangays by name.
     */
    public function current(Municipality $town, int $year): array
    {
        $pending = PopulationFigure::query()->where('municipality_id', $town->id)->where('year', $year)
            ->where('status', FinanceFigureStatus::Pending)->orderBy('id')->get()
            ->keyBy(fn (PopulationFigure $figure) => $figure->barangay_id ?? 0);
        $fact = TownFact::query()->where('municipality_id', $town->id)->where('measure', 'population')->where('year', $year)->first();

        $cells = ['town' => $this->cell($town->name, null, $pending->get(0),
            $fact === null ? null : (int) $fact->value, $fact?->source_url)];
        foreach (Barangay::query()->where('municipality_id', $town->id)->orderBy('name')
            ->get(['id', 'psgc_code', 'name', 'population', 'population_year', 'population_source']) as $barangay) {
            $published = $barangay->population_year === $year ? $barangay->population : null;
            $cells[$barangay->psgc_code] = $this->cell($barangay->name, $barangay->id, $pending->get($barangay->id),
                $published === null ? null : (int) $published, $published === null ? null : $barangay->population_source);
        }

        return $cells;
    }

    /** @return array{name: string, barangay: int|null, value: int|null, status: string|null, source: string|null} */
    private function cell(string $name, ?int $barangay, ?PopulationFigure $pending, ?int $published, ?string $source): array
    {
        return $pending !== null
            ? ['name' => $name, 'barangay' => $barangay, 'value' => $pending->value, 'status' => 'pending', 'source' => $pending->source_url]
            : ['name' => $name, 'barangay' => $barangay, 'value' => $published, 'status' => $published === null ? null : 'verified', 'source' => $source];
    }
}
