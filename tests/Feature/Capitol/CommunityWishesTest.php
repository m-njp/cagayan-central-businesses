<?php

use App\Enums\UserRole;
use App\Models\BusinessType;
use App\Models\Municipality;
use App\Models\User;
use App\Models\Wish;
use Inertia\Testing\AssertableInertia as Assert;

test('the word cloud uses the selected municipality and aggregate types across table pages', function () {
    $town = Municipality::factory()->create();
    $otherTown = Municipality::factory()->create();
    $type = BusinessType::factory()->create();
    Wish::factory()->count(2)->for($town)->for($type)->create(['reason' => 'PRIVATE WISH SENTINEL']);
    Wish::factory()->for($otherTown)->for($type)->create();
    Wish::factory()->for($town)->create([
        'business_type_id' => null,
        'specific_business' => 'PRIVATE BUSINESS SENTINEL',
    ]);

    $this->actingAs(User::factory()->create())
        ->get(route('capitol.wishes.index', ['town' => $town->psgc_code, 'page' => 2]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('summary.townName', $town->name)
            ->where('summary.items', [['id' => $type->code, 'label' => $type->name, 'count' => 2]])
            ->has('table.rows', 0))
        ->assertDontSee('PRIVATE WISH SENTINEL')
        ->assertDontSee('PRIVATE BUSINESS SENTINEL');

    $this->get(route('capitol.wishes.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('summary.townName', 'All of Cagayan')
            ->where('summary.items.0.count', 3));
});

test('staff cannot access community wishes aggregates', function () {
    $this->actingAs(User::factory()->create(['role' => UserRole::Staff]))
        ->get(route('capitol.wishes.index'))
        ->assertForbidden();
});
