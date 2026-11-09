<?php

namespace App\Actions;

use App\Models\ActivityLog;
use App\Models\BusinessCategory;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class CreateBusinessCategory
{
    public function handle(User $user, string $name): BusinessCategory
    {
        return DB::transaction(function () use ($user, $name): BusinessCategory {
            $category = BusinessCategory::query()->create([
                'code' => Str::slug($name),
                'name' => $name,
                'color' => $this->leastUsedColor(),
                'sort_order' => (int) BusinessCategory::query()->max('sort_order') + 1,
            ]);
            ActivityLog::record('business_category_created', $user->id);

            return $category;
        });
    }

    /** Neighbouring categories rarely share a color: the slot used by the fewest, lowest number first. */
    private function leastUsedColor(): int
    {
        $used = BusinessCategory::query()->selectRaw('color, count(*) as total')->groupBy('color')->pluck('total', 'color');

        return (int) collect(range(1, (int) config('cagayan.categories.colors')))
            ->sortBy(fn (int $slot): int => (int) ($used[$slot] ?? 0))
            ->first();
    }
}
