<?php

use App\Models\User;

// D20. The login lives only at the secret path, is never indexed, and only Capitol administrators get in.

test('the login page renders at the configured path and is not indexed', function () {
    $this->get(route('admin.login'))
        ->assertOk()
        ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
        ->assertHeader('Referrer-Policy', 'no-referrer');

    expect(route('admin.login', absolute: false))->toBe('/'.config('cagayan.admin.login_path'));
});

test('the usual login paths do not exist', function (string $uri) {
    $this->get($uri)->assertNotFound();
    $this->post($uri, ['email' => 'a@b.test', 'password' => 'x'])->assertNotFound();
})->with(['/login', '/admin', '/capitol/login']);

test('the login path is not listed in robots.txt', function () {
    expect(file_get_contents(public_path('robots.txt')))->not->toContain(config('cagayan.admin.login_path'));
});

test('administrators sign in at the secret path and land on the Capitol side', function () {
    $user = User::factory()->create();

    $this->post(route('admin.login.store'), ['email' => $user->email, 'password' => 'password'])
        ->assertRedirect(route('capitol.dashboard', absolute: false));

    $this->assertAuthenticatedAs($user);
});

test('an account without the Capitol role cannot sign in, with the same message as a wrong password', function () {
    $user = User::factory()->withoutRole()->create();

    $this->from(route('admin.login'))
        ->post(route('admin.login.store'), ['email' => $user->email, 'password' => 'password'])
        ->assertSessionHasErrors(['email' => __('auth.failed')]);

    $this->assertGuest();
});

test('a wrong password does not sign in and does not say which part was wrong', function () {
    $user = User::factory()->create();

    $this->from(route('admin.login'))
        ->post(route('admin.login.store'), ['email' => $user->email, 'password' => 'wrong-password'])
        ->assertSessionHasErrors(['email' => __('auth.failed')]);

    $this->assertGuest();
});

test('repeated failed logins from one address are throttled', function () {
    $user = User::factory()->create();

    foreach (range(1, config('cagayan.admin.login_per_minute')) as $attempt) {
        $this->post(route('admin.login.store'), ['email' => $user->email, 'password' => 'wrong-password']);
    }

    $this->post(route('admin.login.store'), ['email' => $user->email, 'password' => 'wrong-password'])
        ->assertTooManyRequests();
});

test('an account is locked after repeated failures from many addresses', function () {
    config(['cagayan.admin.login_per_hour_per_account' => 3]);
    $user = User::factory()->create();

    foreach (range(1, 3) as $attempt) {
        $this->withServerVariables(['REMOTE_ADDR' => "10.0.0.{$attempt}"])
            ->post(route('admin.login.store'), ['email' => $user->email, 'password' => 'wrong-password']);
    }

    $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.99'])
        ->post(route('admin.login.store'), ['email' => $user->email, 'password' => 'password'])
        ->assertTooManyRequests();

    $this->assertGuest();
});

test('signed-in administrators are sent away from the login page', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('admin.login'))
        ->assertRedirect();
});

test('administrators can sign out', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('logout'))
        ->assertRedirect(route('home'));

    $this->assertGuest();
});

test('public pages never reveal the login path', function (string $route) {
    $this->get(route($route))
        ->assertOk()
        ->assertDontSee(config('cagayan.admin.login_path'), escape: false);
})->with(['home', 'public.wishes.index']);
