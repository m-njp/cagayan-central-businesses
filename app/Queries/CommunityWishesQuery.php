<?php

namespace App\Queries;

use App\Data\Pagination;
use App\Data\SelectOption;
use App\Data\WorkspaceTable;
use App\Models\BusinessType;
use App\Models\Municipality;
use Illuminate\Support\Facades\DB;

final class CommunityWishesQuery
{
    public function get(?string $town, ?string $category): WorkspaceTable
    {
        $results = DB::table('wishes as w')->join('municipalities as m', 'm.id', '=', 'w.municipality_id')
            ->leftJoin('business_types as b', 'b.id', '=', 'w.business_type_id')
            ->when($town, fn ($q) => $q->where('m.psgc_code', $town))
            ->when($category, fn ($q) => $q->where('b.code', $category))
            ->selectRaw("m.name as town, COALESCE(b.name, 'Unspecified Type') as category, COUNT(*) as count")
            ->groupBy('m.psgc_code', 'm.name', 'b.id', 'b.name')->orderByDesc('count')->orderBy('m.name')->orderBy('b.id')
            ->paginate(20)->withQueryString();

        return new WorkspaceTable(
            array_values(array_map(fn ($r) => ['town' => (string) $r->town, 'category' => (string) $r->category, 'count' => (int) $r->count], $results->items())),
            Pagination::from($results),
            array_values(Municipality::query()->orderBy('name')->get(['psgc_code', 'name'])->map(fn ($m) => new SelectOption($m->psgc_code, $m->name))->all()),
            array_values(BusinessType::query()->orderBy('name')->get(['code', 'name'])->map(fn ($b) => new SelectOption($b->code, $b->name))->all()),
            ['town' => $town, 'category' => $category],
        );
    }
}
