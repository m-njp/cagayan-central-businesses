<?php

use App\Models\User;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia as Assert;

// D9 and D20. Every Capitol route needs a login and the capitol role.

test('guests get a not found page instead of a redirect to the login', function () {
    $this->get(route('capitol.dashboard'))->assertNotFound();
});

test('a signed-in account without the Capitol role is refused', function () {
    $this->actingAs(User::factory()->withoutRole()->create())
        ->get(route('capitol.dashboard'))
        ->assertForbidden();
});

test('administrators can open the Capitol side', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('capitol.dashboard'))
        ->assertOk();
});

test('every Capitol route checks the login and the role', function () {
    $routes = collect(Route::getRoutes()->getRoutes())
        ->filter(fn ($route) => str_starts_with((string) $route->getName(), 'capitol.'));

    expect($routes)->not->toBeEmpty();

    $routes->each(fn ($route) => expect($route->gatherMiddleware())->toContain('auth', 'can:capitol'));
});

test('only the account name reaches the browser', function () {
    $this->actingAs(User::factory()->create(['name' => 'Ana Reyes']))
        ->get(route('capitol.dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('auth.user', ['name' => 'Ana Reyes']));
});
