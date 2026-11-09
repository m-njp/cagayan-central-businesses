<?php

namespace App\Actions;

use App\Models\ActivityLog;
use App\Models\BusinessCategory;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class UpdateBusinessCategory
{
    /** The code never changes, so links and analytics keep pointing at the same category. */
    public function handle(User $user, BusinessCategory $category, string $name, int $color): void
    {
        DB::transaction(function () use ($user, $category, $name, $color): void {
            $category->update(['name' => $name, 'color' => $color]);
            ActivityLog::record('business_category_updated', $user->id);
        });
    }
}
