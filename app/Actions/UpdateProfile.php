<?php

namespace App\Actions;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class UpdateProfile
{
    public function handle(User $user, string $name, ?int $month, ?int $day): void
    {
        DB::transaction(function () use ($user, $name, $month, $day): void {
            $user->forceFill(['name' => $name, 'birthday_month' => $month, 'birthday_day' => $day])->save();
            ActivityLog::record('profile_updated', $user->id);
        });
    }
}
