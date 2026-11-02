<?php

namespace App\Queries;

use App\Data\DataEntry;
use App\Data\DataEntryDataset;
use App\Data\DataEntryReviewRow;
use App\Data\Pagination;
use App\Models\User;
use App\Services\BusinessVisibility;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use stdClass;

/**
 * Every dataset a person can add to, with what waits for review, and for Admins one review list across datasets.
 * Staff see only their assigned towns.
 */
final class DataEntryQuery
{
    public function get(User $user, int $page): DataEntry
    {
        $towns = $user->isCapitolAdmin() ? null : $user->assignedTowns()->select('municipalities.id');
        $finances = DB::table('finance_figures as f')
            ->join('finance_lines as l', fn ($join) => $join->on('l.code', '=', 'f.line_code')->where('l.displayed', true))
            ->when($towns, fn (Builder $query) => $query->whereIn('f.municipality_id', $towns));
        $businesses = BusinessVisibility::apply(DB::table('businesses as b'))
            ->join('barangays as g', 'g.id', '=', 'b.barangay_id')
            ->when($towns, fn (Builder $query) => $query->whereIn('g.municipality_id', $towns));

        $datasets = [
            new DataEntryDataset('finances', 'Government Finances', route('capitol.finances.index', absolute: false),
                (clone $finances)->where('f.status', 'pending')->count(), $this->date((clone $finances)->max('f.updated_at')),
                'Enter Report', route('capitol.finances.index', absolute: false)),
            new DataEntryDataset('businesses', 'Businesses', route('capitol.businesses.index', absolute: false),
                (clone $businesses)->where('b.record_status', 'submitted')->count(), $this->date((clone $businesses)->max('b.updated_at')),
                $user->can('add-business') ? 'Add Business' : null,
                $user->can('add-business') ? route('capitol.businesses.create', absolute: false) : null),
        ];
        if ($user->can('manage-categories')) {
            // Category changes apply on save (D39), so there is nothing pending to count.
            $datasets[] = new DataEntryDataset('categories', 'Business Categories', route('capitol.categories.index', absolute: false),
                null, $this->date(DB::table('business_types')->max('updated_at')),
                'Add Category', route('capitol.categories.index', absolute: false));
        }

        if (! $user->can('finance-review')) {
            return new DataEntry($datasets, null, null);
        }

        $reportYear = 'coalesce(d.fiscal_year, f.fiscal_year)';
        $financeRows = (clone $finances)
            ->join('municipalities as m', 'm.id', '=', 'f.municipality_id')
            ->leftJoin('finance_documents as d', 'd.id', '=', 'f.finance_document_id')
            ->where('f.status', 'pending')
            ->selectRaw("'finances' as dataset, m.name as item, m.psgc_code as ref, {$reportYear} as year, count(*) as count, min(f.updated_at) as since")
            ->groupBy('m.name', 'm.psgc_code', DB::raw($reportYear));
        $businessRows = (clone $businesses)
            ->where('b.record_status', 'submitted')
            ->selectRaw("'businesses' as dataset, b.business_name as item, b.public_id::text as ref, null::integer as year, 1 as count, b.updated_at as since");
        $review = DB::query()->fromSub($financeRows->unionAll($businessRows), 'r')
            ->orderBy('since')->orderBy('item')
            ->paginate((int) config('cagayan.finances.per_page'), ['*'], 'page', $page)->withQueryString();

        return new DataEntry($datasets, array_values(array_map(fn (stdClass $row) => new DataEntryReviewRow(
            $row->dataset === 'finances' ? 'Government Finances' : 'Businesses',
            $row->dataset === 'finances' ? "{$row->item} Report {$row->year}" : (string) $row->item,
            (int) $row->count,
            (string) $this->date($row->since),
            $row->dataset === 'finances'
                ? route('capitol.finances.show', ['town' => $row->ref, 'year' => $row->year], absolute: false)
                : route('capitol.businesses.show', $row->ref, absolute: false),
        ), $review->items())), Pagination::from($review));
    }

    private function date(mixed $value): ?string
    {
        return $value === null ? null : Carbon::parse((string) $value)->timezone((string) config('cagayan.display_timezone'))->toDateString();
    }
}
