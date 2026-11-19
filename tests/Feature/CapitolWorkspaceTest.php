<?php

use App\Enums\UserRole;
use App\Models\ActivityLog;
use App\Models\BusinessType;
use App\Models\Municipality;
use App\Models\User;
use App\Models\Wish;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Testing\AssertableInertia as Assert;

it('allows all three roles to see the dashboard without wishes or protected fields', function (UserRole $role) {
    $user = User::factory()->create(['role' => $role]);
    Wish::factory()->create(['reason' => 'Private reason sentinel', 'specific_business' => 'Private company sentinel']);
    $this->actingAs($user)->get(route('capitol.dashboard'))->assertOk()
        ->assertHeader('X-Frame-Options', 'DENY')
        ->assertHeader('Cache-Control', 'max-age=0, no-store, private')
        ->assertInertia(fn (Assert $page) => $page->component('capitol/dashboard/index')
            ->where('workspace.role', $role->label())->missing('wishes')->missing('table')->missing('workspace.birthdayMonth'))
        ->assertDontSee('Private reason sentinel')->assertDontSee('Private company sentinel');
})->with(UserRole::cases());

it('sends the Waiting for Review table to reviewers only', function (UserRole $role) {
    $this->actingAs(User::factory()->create(['role' => $role]))->get(route('capitol.dashboard'))
        ->assertInertia(fn (Assert $page) => $role === UserRole::Staff
            ? $page->where('waiting', null)
            : $page->has('waiting', 0));
})->with(UserRole::cases());

it('enforces the wishes and logs permission matrix on direct requests', function (UserRole $role) {
    $this->actingAs(User::factory()->create(['role' => $role]));
    $this->get(route('capitol.wishes.index'))->assertStatus($role === UserRole::Staff ? 403 : 200);
    $this->get(route('capitol.logs.index'))->assertStatus($role === UserRole::SuperAdmin ? 200 : 403);
})->with(UserRole::cases());

it('returns safe aggregates without raw wish text or hashes', function () {
    $town = Municipality::factory()->create();
    $type = BusinessType::factory()->create();
    Wish::factory()->count(2)->create(['municipality_id' => $town->id, 'business_type_id' => $type->id,
        'reason' => 'Secret reason', 'specific_business' => 'Secret company', 'network_hash' => 'Secret network']);
    $this->actingAs(User::factory()->create())->get(route('capitol.wishes.index', ['town' => $town->psgc_code, 'category' => $type->code]))
        ->assertOk()->assertInertia(fn (Assert $page) => $page->has('table.rows', 1)
        ->where('table.rows.0', ['town' => $town->name, 'category' => $type->name, 'count' => 2]))
        ->assertDontSee('Secret reason')->assertDontSee('Secret company')->assertDontSee('Secret network');
});

it('keeps private workspace capabilities off public pages', function () {
    $this->actingAs(User::factory()->create(['role' => UserRole::SuperAdmin]))->get(route('home'))
        ->assertInertia(fn (Assert $page) => $page->missing('workspace'));
});

it('scopes future staff editing permissions to assigned towns', function () {
    $staff = User::factory()->create(['role' => UserRole::Staff]);
    [$assigned, $other] = Municipality::factory()->count(2)->create();
    $staff->assignedTowns()->attach($assigned);
    expect(Gate::forUser($staff)->allows('edit-town', $assigned))->toBeTrue()
        ->and(Gate::forUser($staff)->allows('edit-town', $other))->toBeFalse();
    $staff->forceFill(['is_active' => false])->save();
    expect(Gate::forUser($staff)->allows('edit-town', $assigned))->toBeFalse();
});

it('keeps audit records append only at the database boundary', function () {
    ActivityLog::record('sign_in_failed', null);
    expect(fn () => DB::transaction(fn () => DB::table('activity_logs')->update(['event' => 'signed_in'])))
        ->toThrow(QueryException::class);
    expect(fn () => DB::transaction(fn () => DB::table('activity_logs')->delete()))->toThrow(QueryException::class);
    $this->assertDatabaseHas('activity_logs', ['event' => 'sign_in_failed']);
});

it('updates a unicode display name and private birthday without exposing the birth date in shared props', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->patch(route('capitol.profile.update'), ['display_name' => 'Niño O’Connor', 'birthday_month' => 2, 'birthday_day' => 29])
        ->assertRedirect(route('capitol.profile.show'));
    expect($user->fresh()->name)->toBe('Niño O’Connor')->and($user->fresh()->birthday_day)->toBe(29);
    $this->assertDatabaseHas('activity_logs', ['actor_id' => $user->id, 'event' => 'profile_updated']);
    $this->get(route('capitol.dashboard'))->assertInertia(fn (Assert $page) => $page->missing('workspace.birthdayDay')->missing('workspace.email'));
});

it('rejects invalid profile values with field feedback', function (array $payload, string $field) {
    $this->actingAs(User::factory()->create())->from(route('capitol.profile.show'))
        ->patch(route('capitol.profile.update'), ['display_name' => 'Valid Name', ...$payload])->assertSessionHasErrors($field);
})->with([
    'too long' => [['display_name' => str_repeat('a', 101)], 'display_name'],
    'markup' => [['display_name' => '<script>alert(1)</script>'], 'display_name'],
    'control' => [['display_name' => "Name\x01"], 'display_name'],
    'invalid day' => [['birthday_month' => 4, 'birthday_day' => 31], 'birthday_day'],
    'incomplete birthday' => [['birthday_month' => 1], 'birthday_day'],
    'role escalation' => [['role' => 'super_admin'], 'role'],
    'identity overwrite' => [['email' => 'other@example.test'], 'email'],
]);

it('shows birthday greetings only on the matching Philippine date', function () {
    $this->travelTo(now()->setDateTime(2026, 10, 23, 17, 0));
    $this->actingAs(User::factory()->create(['birthday_month' => 10, 'birthday_day' => 24]))->get(route('capitol.dashboard'))
        ->assertInertia(fn (Assert $page) => $page->where('workspace.isBirthday', true));
});

it('uses an explicit console command to grant super admin and records the change', function () {
    $user = User::factory()->create();
    $version = $user->auth_version;
    $this->artisan('workspace:role', ['email' => $user->email, 'role' => 'super_admin'])->assertSuccessful();
    expect($user->fresh()->role)->toBe(UserRole::SuperAdmin)->and($user->fresh()->auth_version)->toBeGreaterThan($version);
    $this->assertDatabaseHas('activity_logs', ['actor_id' => $user->id, 'event' => 'role_assigned_from_console']);
});

it('keeps profile validation feedback on its own page without a Referer header', function () {
    $this->actingAs(User::factory()->create())->patch(route('capitol.profile.update'), ['display_name' => '<invalid>'])
        ->assertRedirect(route('capitol.profile.show'))->assertSessionHasErrors('display_name');
});

it('loads the selected towns real census barangays without business or wish data', function () {
    Municipality::factory()->create(['psgc_code' => '0201529000']);
    $this->actingAs(User::factory()->create(['role' => UserRole::Staff]))
        ->get(route('capitol.dashboard', ['town' => '0201529000', 'level' => 'barangays', 'barangay' => '0201529001']))
        ->assertOk()->assertInertia(fn (Assert $page) => $page->has('map.barangays', 49)->missing('table')->missing('wishes'));
});

it('restores locally recognized barangay names and census values from a dashboard deep link', function (string $code, string $name, string $population) {
    Municipality::factory()->create(['psgc_code' => '0201529000', 'name' => 'Tuguegarao City']);
    $this->actingAs(User::factory()->create())->get(route('capitol.dashboard', [
        'town' => '0201529000', 'level' => 'barangays', 'barangay' => $code,
    ]))->assertOk()->assertInertia(fn (Assert $page) => $page
        ->has('map.barangays', 49)
        ->where('map.barangays', fn ($rows) => collect($rows)->contains(fn ($row) => $row['code'] === $code && $row['name'] === $name && $row['population']['value'] === $population)));
})->with([
    ['0201529018', 'Carig Sur', '5085'],
    ['0201529050', 'Carig Norte', '3266'],
    ['0201529031', 'Pengue-Ruyu', '6605'],
]);
