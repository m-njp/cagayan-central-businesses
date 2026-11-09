<?php

use App\Enums\UserRole;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

// D33. Quick sign in is a password bypass, so it exists on a local machine only.

// CSRF checking is skipped only while the environment is "testing", so these posts carry a token.
function postDemoLogin(string $uri)
{
    return test()->withSession(['_token' => 'token'])->post($uri, ['_token' => 'token']);
}

beforeEach(function () {
    config(['cagayan.demo_accounts' => [
        'super_admin' => ['name' => 'Demo Super', 'email' => 'super@demo.test', 'password' => 'unused'],
        'admin' => ['name' => 'Demo Admin', 'email' => 'admin@demo.test', 'password' => 'unused'],
        'staff' => ['name' => 'Demo Staff', 'email' => 'staff@demo.test', 'password' => 'unused'],
    ]]);

    foreach ([['super@demo.test', UserRole::SuperAdmin], ['admin@demo.test', UserRole::Admin], ['staff@demo.test', UserRole::Staff]] as [$email, $role]) {
        User::factory()->create(['email' => $email, 'role' => $role]);
    }
});

test('each demo role signs in on a local machine and lands in the workspace', function (UserRole $role, string $email) {
    $this->app['env'] = 'local';

    postDemoLogin(route('admin.login.demo', $role->value))
        ->assertRedirect(route('capitol.dashboard', absolute: false));

    $user = User::query()->where('email', $email)->firstOrFail();
    $this->assertAuthenticatedAs($user);
    expect(session('workspace.version'))->toBe($user->auth_version);
    $this->assertDatabaseHas('activity_logs', ['event' => 'signed_in', 'actor_id' => $user->id]);
})->with([
    'super admin' => [UserRole::SuperAdmin, 'super@demo.test'],
    'admin' => [UserRole::Admin, 'admin@demo.test'],
    'staff' => [UserRole::Staff, 'staff@demo.test'],
]);

test('quick sign in opens finance review on the same local app', function (UserRole $role) {
    $this->app['env'] = 'local';

    postDemoLogin(route('admin.login.demo', $role->value))
        ->assertRedirect(route('capitol.dashboard', absolute: false));

    $this->get(route('capitol.finances.index'))->assertInertia(fn (AssertableInertia $page) => $page
        ->component('capitol/finances/index'));
})->with(UserRole::cases());

test('quick sign in answers 404 outside a local machine', function (string $environment) {
    $this->app['env'] = $environment;

    postDemoLogin(route('admin.login.demo', UserRole::Admin->value))->assertNotFound();

    $this->assertGuest();
})->with(['production', 'staging', 'testing']);

test('a role without a seeded demo account answers 404', function () {
    $this->app['env'] = 'local';
    User::query()->where('email', 'staff@demo.test')->delete();

    postDemoLogin(route('admin.login.demo', UserRole::Staff->value))->assertNotFound();

    $this->assertGuest();
});

test('an account that can no longer enter the workspace cannot be used', function () {
    $this->app['env'] = 'local';
    User::query()->where('email', 'admin@demo.test')->update(['is_active' => false]);

    postDemoLogin(route('admin.login.demo', UserRole::Admin->value))->assertNotFound();

    $this->assertGuest();
});

test('an unknown role answers 404', function () {
    $this->app['env'] = 'local';

    postDemoLogin('/'.config('cagayan.admin.login_path').'/demo/owner')->assertNotFound();

    $this->assertGuest();
});

test('the login page lists the seeded demo roles on a local machine only', function () {
    $this->app['env'] = 'local';

    $this->get(route('admin.login'))->assertInertia(fn ($page) => $page
        ->where('demoLogins', [
            ['role' => 'super_admin', 'label' => 'Super Admin'],
            ['role' => 'admin', 'label' => 'Admin'],
            ['role' => 'staff', 'label' => 'Staff'],
        ]));

    $this->app['env'] = 'production';

    $this->get(route('admin.login'))->assertInertia(fn ($page) => $page->where('demoLogins', []));
});
