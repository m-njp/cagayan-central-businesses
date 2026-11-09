<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DemoAccountSeeder extends Seeder
{
    /**
     * One demo account per role. Name, email, and password come from .env
     * (config/cagayan.php, demo_accounts). Existing accounts are left alone,
     * so this is safe to run on its own: php artisan db:seed --class=DemoAccountSeeder
     */
    public function run(): void
    {
        foreach (UserRole::cases() as $role) {
            $account = config("cagayan.demo_accounts.{$role->value}");

            if (blank($account['email']) || blank($account['password'])) {
                $this->command->warn("The {$role->label()} demo account email or password is empty in .env. It was not created.");

                continue;
            }

            $user = User::query()->firstOrNew(['email' => $account['email']]);
            if ($user->exists) {
                continue;
            }
            $user->fill(['name' => $account['name'], 'password' => $account['password']]);
            // role is never fillable (D9), so it is forced here.
            $user->forceFill([
                'public_id' => (string) Str::uuid(),
                'email_verified_at' => now(),
                'role' => $role,
            ])->save();
        }
    }
}
