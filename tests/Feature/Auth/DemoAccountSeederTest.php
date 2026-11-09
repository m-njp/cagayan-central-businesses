<?php

use App\Enums\UserRole;
use App\Models\User;
use Database\Seeders\DemoAccountSeeder;
use Illuminate\Support\Facades\Hash;

// Demo accounts come from .env through config. No credential is written in code.

beforeEach(function () {
    config(['cagayan.demo_accounts' => [
        'super_admin' => ['name' => 'Demo Super', 'email' => 'super@demo.test', 'password' => 'super-secret'],
        'admin' => ['name' => 'Demo Admin', 'email' => 'admin@demo.test', 'password' => 'admin-secret'],
        'staff' => ['name' => 'Demo Staff', 'email' => 'staff@demo.test', 'password' => 'staff-secret'],
    ]]);
});

test('the seeder creates one account per role that can enter the workspace', function () {
    $this->seed(DemoAccountSeeder::class);

    foreach (['super@demo.test' => UserRole::SuperAdmin, 'admin@demo.test' => UserRole::Admin, 'staff@demo.test' => UserRole::Staff] as $email => $role) {
        $user = User::query()->where('email', $email)->firstOrFail();

        expect($user->role)->toBe($role)
            ->and($user->canEnterWorkspace())->toBeTrue()
            ->and(Hash::check(config("cagayan.demo_accounts.{$role->value}.password"), $user->password))->toBeTrue();
    }
});

test('a role with no email or password in the environment is skipped', function () {
    config(['cagayan.demo_accounts.staff.password' => null]);

    $this->seed(DemoAccountSeeder::class);

    expect(User::query()->count())->toBe(2)
        ->and(User::query()->where('email', 'staff@demo.test')->exists())->toBeFalse();
});

test('running the seeder again keeps existing accounts untouched', function () {
    $this->seed(DemoAccountSeeder::class);
    $before = User::query()->where('email', 'admin@demo.test')->firstOrFail();

    config(['cagayan.demo_accounts.admin.password' => 'changed-later']);
    $this->seed(DemoAccountSeeder::class);

    $after = User::query()->where('email', 'admin@demo.test')->firstOrFail();
    expect(User::query()->count())->toBe(3)
        ->and($after->password)->toBe($before->password)
        ->and($after->auth_version)->toBe($before->auth_version);
});
