<?php

use App\Enums\UserRole;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

it('expires an idle or absolute session before returning private data', function (string $kind) {
    $user = User::factory()->create();
    $now = now()->timestamp;
    $this->actingAs($user)->withSession(['workspace' => [
        'started_at' => $kind === 'absolute' ? $now - config('cagayan.admin.absolute_seconds') : $now - 2000,
        'last_activity' => $kind === 'idle' ? $now - config('cagayan.admin.idle_seconds') : $now,
        'version' => $user->auth_version,
    ]])->get(route('capitol.dashboard'))->assertRedirect(route('admin.login'));
    $this->assertGuest();
})->with(['idle', 'absolute']);

it('rejects a session after a role change', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->get(route('capitol.dashboard'))->assertOk();
    $user->forceFill(['role' => UserRole::Staff])->save();
    $this->get(route('capitol.dashboard'))->assertRedirect(route('admin.login'));
    $this->assertGuest();
});

it('does not renew idle time for background requests', function () {
    $user = User::factory()->create();
    $lastActivity = now()->timestamp - 100;
    $this->actingAs($user)->withSession(['workspace' => [
        'started_at' => now()->timestamp - 200, 'last_activity' => $lastActivity, 'version' => $user->auth_version,
    ]])->withHeader('X-Workspace-Background', 'true')->get(route('capitol.dashboard'))
        ->assertOk()->assertSessionHas('workspace.last_activity', $lastActivity);
});

it('rejects deactivated accounts with the same login feedback', function () {
    $user = User::factory()->create(['is_active' => false]);
    $this->post(route('admin.login.store'), ['email' => $user->email, 'password' => 'password'])->assertSessionHasErrors('email');
    $this->assertGuest();
});

it('does not issue a remember cookie even if it is requested', function () {
    $user = User::factory()->create();
    $this->post(route('admin.login.store'), ['email' => $user->email, 'password' => 'password', 'remember' => true])->assertRedirect();
    $this->assertAuthenticatedAs($user);
    $this->assertDatabaseHas('activity_logs', ['event' => 'signed_in', 'actor_id' => $user->id]);
    expect(auth()->viaRemember())->toBeFalse();
});

it('renders safe themed errors without debug details', function (int $status) {
    $response = $status === 404 ? $this->get('/not-a-real-page') : $this->actingAs(User::factory()->create(['role' => UserRole::Staff]))->get(route('capitol.logs.index'));
    $response->assertStatus($status)->assertInertia(fn (Assert $page) => $page->component('errors/status')->where('status', $status))
        ->assertDontSee('Stack trace');
})->with([403, 404]);

it('encrypts private history and clears it after logout', function () {
    $this->actingAs(User::factory()->create())->get(route('capitol.dashboard'))
        ->assertViewHas('page', fn (array $page) => ($page['encryptHistory'] ?? false) === true);
    $this->post(route('logout'))->assertRedirect(route('home'));
    $this->get(route('home'))->assertViewHas('page', fn (array $page) => ($page['clearHistory'] ?? false) === true);
    $this->get(route('capitol.dashboard'))->assertNotFound();
});

it('rejects an existing session after deactivation', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->get(route('capitol.dashboard'))->assertOk();
    $user->forceFill(['is_active' => false])->save();
    $this->get(route('capitol.dashboard'))->assertRedirect(route('admin.login'));
    $this->assertGuest();
});

it('rechecks a stale tab against the current server idle deadline', function () {
    $user = User::factory()->create();
    $lastActivity = now()->getTimestamp() - 30;
    $this->actingAs($user)->withSession(['workspace' => [
        'started_at' => now()->getTimestamp() - 2000, 'last_activity' => $lastActivity, 'version' => $user->auth_version,
    ]])->withHeader('X-Workspace-Background', 'true')->get(route('capitol.dashboard'))
        ->assertOk()->assertSessionHas('workspace.last_activity', $lastActivity)
        ->assertInertia(fn (Assert $page) => $page->where('workspace.idleExpiresAt', $lastActivity + config('cagayan.admin.idle_seconds')));
});

it('records direct workspace activity without changing the absolute session deadline', function (UserRole $role) {
    $user = User::factory()->create(['role' => $role]);
    $now = now()->timestamp;
    $this->actingAs($user)->withSession(['workspace' => [
        'started_at' => $now - 600, 'last_activity' => $now - 100, 'version' => $user->auth_version,
    ]])->postJson(route('capitol.session.activity'))
        ->assertJsonPath('idleExpiresAt', $now + config('cagayan.admin.idle_seconds'))
        ->assertJsonPath('absoluteExpiresAt', $now - 600 + config('cagayan.admin.absolute_seconds'))
        ->assertSessionHas('workspace.last_activity', $now)
        ->assertHeader('Cache-Control', 'max-age=0, no-store, private');
})->with(UserRole::cases());

it('does not renew activity marked as background', function () {
    $user = User::factory()->create();
    $lastActivity = now()->timestamp - 100;
    $this->actingAs($user)->withSession(['workspace' => [
        'started_at' => now()->timestamp - 200, 'last_activity' => $lastActivity, 'version' => $user->auth_version,
    ]])->withHeader('X-Workspace-Background', 'true')->postJson(route('capitol.session.activity'))
        ->assertSessionHas('workspace.last_activity', $lastActivity)
        ->assertJsonPath('idleExpiresAt', $lastActivity + config('cagayan.admin.idle_seconds'));
});

it('cannot revive an expired session through the activity endpoint', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->withSession(['workspace' => [
        'started_at' => now()->timestamp - 2000, 'last_activity' => now()->timestamp - config('cagayan.admin.idle_seconds'), 'version' => $user->auth_version,
    ]])->postJson(route('capitol.session.activity'))->assertUnauthorized();
    $this->assertGuest();
    $this->get(route('admin.login'))->assertViewHas('page', fn (array $page) => ($page['clearHistory'] ?? false) === true);
});

it('conceals the workspace activity endpoint from guests', function () {
    $this->postJson(route('capitol.session.activity'))->assertNotFound();
});

it('throttles repeated activity requests', function () {
    config(['cagayan.admin.activity_requests_per_minute' => 2]);
    $this->actingAs(User::factory()->create());
    $this->postJson(route('capitol.session.activity'))->assertOk();
    $this->postJson(route('capitol.session.activity'))->assertOk();
    $this->postJson(route('capitol.session.activity'))->assertTooManyRequests();
});
