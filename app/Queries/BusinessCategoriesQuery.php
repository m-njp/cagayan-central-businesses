<?php

namespace App\Queries;

use App\Data\BusinessCategories;
use App\Data\BusinessCategoryRow;
use App\Data\BusinessTypeRow;
use App\Data\SelectOption;
use App\Models\BusinessType;
use Illuminate\Support\Facades\DB;
use stdClass;

final class BusinessCategoriesQuery
{
    public function get(): BusinessCategories
    {
        // Business records are simulated until a real registry is loaded, so counts show only when the label can too (P13, D37).
        $year = config('cagayan.simulated') ? DB::table('stats_area_year')->max('year') : null;
        // Active businesses per type in the latest data year (M1, M28). Counts only, no sales.
        $active = DB::table('stats_area_year')->where('year', $year)->groupBy('business_type_id')
            ->selectRaw('business_type_id, sum(active_count) as active');

        $types = DB::table('business_types as t')->join('business_categories as c', 'c.id', '=', 't.business_category_id')
            ->leftJoinSub($active, 'a', 'a.business_type_id', '=', 't.id')
            ->orderBy('t.name')->get(['t.code', 't.name', 't.psic_section', 't.psic_section_name', 'c.code as category', 'a.active']);
        $categories = DB::table('business_categories as c')
            ->leftJoin('business_types as t', 't.business_category_id', '=', 'c.id')
            ->leftJoinSub($active, 'a', 'a.business_type_id', '=', 't.id')
            ->groupBy('c.id', 'c.code', 'c.name', 'c.color', 'c.sort_order')->orderBy('c.sort_order')->orderBy('c.name')
            ->selectRaw('c.code, c.name, c.color, count(t.id) as types, coalesce(sum(a.active), 0) as registered')->get();

        return new BusinessCategories(
            types: array_values($types->map(fn (stdClass $type) => new BusinessTypeRow(
                $type->code, $type->name, $type->category, $type->psic_section_name, $type->psic_section,
                $year === null ? null : (int) $type->active,
            ))->all()),
            categories: array_values($categories->map(fn (stdClass $category) => new BusinessCategoryRow(
                $category->code, $category->name, (int) $category->color, (int) $category->types,
                $year === null ? null : (int) $category->registered,
            ))->all()),
            industries: array_values(BusinessType::query()->distinct()->orderBy('psic_section')->get(['psic_section', 'psic_section_name'])
                ->map(fn (BusinessType $type) => new SelectOption($type->psic_section, $type->psic_section_name))->all()),
            year: $year === null ? null : (int) $year,
            typeNameMax: (int) config('cagayan.inputs.business_type_name_max'),
            categoryNameMax: (int) config('cagayan.inputs.category_name_max'),
            colors: (int) config('cagayan.categories.colors'),
            pageSize: (int) config('cagayan.categories.page_size'),
        );
    }
}
