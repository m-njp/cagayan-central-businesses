<?php

namespace App\Data;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Http\Request;

final readonly class Workspace
{
    public function __construct(
        public string $displayName,
        public string $role,
        public bool $canViewWishes,
        public bool $canViewLogs,
        public bool $canManageCategories,
        public bool $isBirthday,
        public int $idleExpiresAt,
        public int $absoluteExpiresAt,
        public int $warningSeconds,
        public int $serverNow,
        public int $activityIntervalSeconds,
    ) {}

    public static function from(User $user, Request $request): self
    {
        $today = now()->timezone(config('cagayan.display_timezone'));

        return new self(
            $user->name, $user->role?->label() ?? '', $user->isCapitolAdmin(),
            $user->role === UserRole::SuperAdmin,
            $user->can('manage-categories'),
            $user->birthday_month === $today->month && $user->birthday_day === $today->day,
            (int) $request->session()->get('workspace.last_activity', now()->getTimestamp()) + (int) config('cagayan.admin.idle_seconds'),
            (int) $request->session()->get('workspace.started_at', now()->getTimestamp()) + (int) config('cagayan.admin.absolute_seconds'),
            (int) config('cagayan.admin.warning_seconds'), now()->getTimestamp(),
            (int) config('cagayan.admin.activity_interval_seconds'),
        );
    }
}
