<?php

namespace App\Actions;

use App\Models\ActivityLog;
use App\Models\BusinessCategory;
use App\Models\BusinessType;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class UpdateBusinessType
{
    /** The code never changes, so wishes, records and links keep pointing at the same type. */
    public function handle(User $user, BusinessType $type, string $name, string $section, string $categoryCode): void
    {
        DB::transaction(function () use ($user, $type, $name, $section, $categoryCode): void {
            $type->update([
                'name' => $name,
                'psic_section' => $section,
                'psic_section_name' => (string) BusinessType::query()->where('psic_section', $section)->value('psic_section_name'),
                'business_category_id' => BusinessCategory::query()->where('code', $categoryCode)->value('id'),
            ]);
            ActivityLog::record('business_type_updated', $user->id);
        });
    }
}
