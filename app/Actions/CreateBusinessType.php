<?php

namespace App\Actions;

use App\Models\ActivityLog;
use App\Models\BusinessCategory;
use App\Models\BusinessType;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class CreateBusinessType
{
    public function handle(User $user, string $name, string $section, string $categoryCode): BusinessType
    {
        return DB::transaction(function () use ($user, $name, $section, $categoryCode): BusinessType {
            $type = BusinessType::query()->create([
                'code' => Str::slug($name),
                'name' => $name,
                'psic_section' => $section,
                'psic_section_name' => (string) BusinessType::query()->where('psic_section', $section)->value('psic_section_name'),
                'business_category_id' => BusinessCategory::query()->where('code', $categoryCode)->value('id'),
                'sort_order' => (int) BusinessType::query()->max('sort_order') + 1,
            ]);
            ActivityLog::record('business_type_created', $user->id);

            return $type;
        });
    }
}
