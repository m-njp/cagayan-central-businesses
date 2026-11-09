<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

final class SetWorkspaceRole extends Command
{
    protected $signature = 'workspace:role {email : Existing account email} {role : super_admin, admin, or staff}';

    protected $description = 'Assign a workspace role to an existing account from a trusted server console';

    public function handle(): int
    {
        $role = UserRole::tryFrom((string) $this->argument('role'));
        $user = User::query()->where('email', mb_strtolower((string) $this->argument('email')))->first();
        if (! $role || ! $user) {
            $this->error('Supply an existing account and a valid workspace role.');

            return self::FAILURE;
        }
        DB::transaction(function () use ($role, $user): void {
            $user->forceFill(['role' => $role])->save();
            ActivityLog::record('role_assigned_from_console', $user->id);
        });
        $this->info('Role assigned. Existing sessions will require sign-in again.');

        return self::SUCCESS;
    }
}
