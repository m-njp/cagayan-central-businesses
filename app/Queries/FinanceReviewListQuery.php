<?php

namespace App\Queries;

use App\Data\FinanceReportRow;
use App\Data\FinanceReviewList;
use App\Data\Pagination;
use App\Data\SelectOption;
use App\Models\Municipality;
use App\Models\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use stdClass;

/**
 * Towns and report years with displayed finance figures, counted by review status (D35).
 * Staff see only their assigned towns.
 */
final class FinanceReviewListQuery
{
    public function get(User $user, ?string $town, ?int $year, ?string $status): FinanceReviewList
    {
        $reportYear = 'coalesce(d.fiscal_year, f.fiscal_year)';
        $reports = DB::table('finance_figures as f')
            ->join('finance_lines as l', fn ($join) => $join->on('l.code', '=', 'f.line_code')->where('l.displayed', true))
            ->join('municipalities as m', 'm.id', '=', 'f.municipality_id')
            ->leftJoin('finance_documents as d', 'd.id', '=', 'f.finance_document_id')
            ->when(! $user->isCapitolAdmin(), fn (Builder $query) => $query->whereIn('m.id', $user->assignedTowns()->select('municipalities.id')));

        $years = (clone $reports)->selectRaw("{$reportYear} as year")->distinct()->orderByDesc('year')
            ->pluck('year')->map(fn ($value) => (int) $value)->all();
        $year ??= $years[0] ?? (int) config('cagayan.finances.last_year');

        $results = $reports
            ->when($town, fn (Builder $query) => $query->where('m.psgc_code', $town))
            ->whereRaw("{$reportYear} = ?", [$year])
            ->selectRaw("m.psgc_code, m.name, {$reportYear} as year,
                count(*) filter (where f.status = 'pending') as pending,
                count(*) filter (where f.status = 'verified') as verified,
                count(*) filter (where f.status = 'rejected') as rejected")
            ->groupBy('m.psgc_code', 'm.name', DB::raw($reportYear))
            ->when($status === 'pending', fn (Builder $query) => $query->havingRaw("count(*) filter (where f.status = 'pending') > 0"))
            ->orderByDesc('year')->orderBy('m.name')
            ->paginate((int) config('cagayan.finances.per_page'))->appends(['year' => $year])->withQueryString();

        $towns = Municipality::query()
            ->when(! $user->isCapitolAdmin(), fn ($query) => $query->whereIn('id', $user->assignedTowns()->select('municipalities.id')))
            ->orderBy('name')->get(['psgc_code', 'name']);

        return new FinanceReviewList(
            rows: array_values(array_map(fn (stdClass $row) => new FinanceReportRow(
                (string) $row->psgc_code, (string) $row->name, (int) $row->year,
                (int) $row->pending, (int) $row->verified, (int) $row->rejected,
            ), $results->items())),
            pagination: Pagination::from($results),
            towns: array_values($towns->map(fn (Municipality $town) => new SelectOption($town->psgc_code, $town->name))->all()),
            years: array_values(array_map(fn (int $year) => new SelectOption((string) $year, (string) $year),
                $years)),
            filters: ['town' => $town, 'year' => (string) $year, 'status' => $status],
        );
    }
}
