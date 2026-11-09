<?php

use App\Enums\UserRole;
use App\Models\Business;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

it('seeds demo accounts with UUIDs while model events are muted', function () {
    foreach (UserRole::cases() as $role) {
        config(["cagayan.demo_accounts.{$role->value}" => [
            'name' => $role->label(),
            'email' => "{$role->value}@seeder.example.test",
            'password' => 'seeder-test-password',
        ]]);
    }

    $this->seed(DatabaseSeeder::class);

    expect(DB::connection()->getDatabaseName())->toBe('cagayan_business_test');
    // Simulated businesses are off by default (D37); they appear only when CAGAYAN_SIMULATED is true.
    expect(Business::query()->count())->toBe(0);
    expect(DB::scalar('SELECT PostGIS_Version()'))->toBeString()->toStartWith('3.');
    foreach (UserRole::cases() as $role) {
        $user = User::query()->where('email', "{$role->value}@seeder.example.test")->firstOrFail();

        expect(Str::isUuid($user->public_id))->toBeTrue();
        expect($user->role)->toBe($role);
        expect($user->email_verified_at)->not->toBeNull();
        expect(Hash::check('seeder-test-password', $user->password))->toBeTrue();
    }
});

it('preserves an existing demo account when the full database is reseeded', function () {
    config(['cagayan.demo_accounts' => [
        'admin' => [
            'name' => 'Seeder Demo Admin',
            'email' => 'admin@seeder.example.test',
            'password' => 'replacement-password',
        ],
        'super_admin' => ['name' => 'Super Admin', 'email' => null, 'password' => null],
        'staff' => ['name' => 'Staff', 'email' => null, 'password' => null],
    ]]);
    $admin = User::factory()->create([
        'name' => 'Existing Admin',
        'email' => 'admin@seeder.example.test',
        'role' => UserRole::SuperAdmin,
    ]);
    $original = $admin->only(['public_id', 'name', 'role', 'password', 'auth_version']);

    $this->seed(DatabaseSeeder::class);

    $this->assertDatabaseCount('users', 1);
    expect($admin->refresh()->only(array_keys($original)))->toBe($original);
});
