<?php

use App\Enums\BusinessStatus;
use App\Enums\UserRole;
use App\Models\Barangay;
use App\Models\Business;
use App\Models\BusinessCategory;
use App\Models\BusinessType;
use App\Models\BusinessYear;
use App\Models\Municipality;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->agriculture = BusinessCategory::factory()->create(['code' => 'agriculture', 'name' => 'Agriculture', 'sort_order' => 1]);
    $this->retail = BusinessCategory::factory()->create(['code' => 'retail', 'name' => 'Retail', 'sort_order' => 2]);
    $this->farm = BusinessType::factory()->for($this->agriculture, 'category')->create(['code' => 'crop-farm', 'name' => 'Rice and Corn Farm', 'psic_section' => 'A', 'psic_section_name' => 'Agriculture, forestry and fishing']);
    $this->shop = BusinessType::factory()->for($this->retail, 'category')->create(['code' => 'shop', 'name' => 'Shop', 'psic_section' => 'G', 'psic_section_name' => 'Wholesale and retail trade']);
    $this->admin = User::factory()->create();
    config(['cagayan.simulated' => true]);
});

function registerBusinesses(BusinessType $type, BusinessStatus $status, int $year, int $count, string $sales = '1000.00'): void
{
    $barangay = Barangay::query()->first() ?? Barangay::factory()->for(Municipality::factory()->create())->create();
    foreach (range(1, $count) as $ignored) {
        $business = Business::factory()->inBarangay($barangay)->ofType($type)->create();
        // Sales are only allowed on an active year (a database constraint).
        $row = BusinessYear::factory()->for($business)->status($status, $year);
        ($status === BusinessStatus::Closed ? $row : $row->sales($sales))->create();
    }
}

it('counts registered businesses per type and category for the latest data year', function () {
    $second = BusinessType::factory()->for($this->agriculture, 'category')->create(['code' => 'livestock', 'name' => 'Livestock Farm', 'psic_section' => 'A', 'psic_section_name' => 'Agriculture, forestry and fishing']);
    registerBusinesses($this->farm, BusinessStatus::Renewed, 2024, 2);
    registerBusinesses($second, BusinessStatus::New, 2024, 1);
    registerBusinesses($this->farm, BusinessStatus::Closed, 2024, 1);
    registerBusinesses($this->shop, BusinessStatus::Renewed, 2023, 4);
    $this->artisan('metrics:refresh')->assertSuccessful();

    $this->actingAs($this->admin)->get(route('capitol.categories.index'))
        ->assertInertia(fn (Assert $page) => $page->component('capitol/business-categories')
            ->where('categories.year', 2024)
            ->where('categories.types', fn ($types) => collect($types)->contains(fn ($row) => $row['code'] === 'crop-farm'
                && $row['registered'] === 2 && $row['industryCode'] === 'A' && $row['category'] === 'agriculture')
                && collect($types)->contains(fn ($row) => $row['code'] === 'shop' && $row['registered'] === 0))
            // Agriculture: two renewed farms and one new livestock farm, never the closed one. The shop traded in 2023 only.
            ->where('categories.categories.0.registered', 3)
            ->where('categories.categories.0.types', 2)
            ->where('categories.categories.1.registered', 0));
});

it('keeps registered counts null while simulated records are off or none exist', function () {
    $this->actingAs($this->admin)->get(route('capitol.categories.index'))
        ->assertInertia(fn (Assert $page) => $page->where('categories.year', null)
            ->where('categories.types.0.registered', null)
            ->where('categories.categories.0.registered', null));

    registerBusinesses($this->farm, BusinessStatus::Renewed, 2024, 2);
    $this->artisan('metrics:refresh')->assertSuccessful();
    config(['cagayan.simulated' => false]);

    $this->actingAs($this->admin)->get(route('capitol.categories.index'))
        ->assertInertia(fn (Assert $page) => $page->where('categories.year', null)
            ->where('categories.categories.0.registered', null));
});

it('never sends sales to the page', function () {
    registerBusinesses($this->farm, BusinessStatus::Renewed, 2024, 1, '7654321.98');
    $this->artisan('metrics:refresh')->assertSuccessful();

    $this->actingAs($this->admin)->get(route('capitol.categories.index'))->assertDontSee('7654321');
});

it('keeps staff out of Business Categories', function () {
    $staff = User::factory()->create(['role' => UserRole::Staff]);

    $this->actingAs($staff)->get(route('capitol.categories.index'))->assertForbidden();
    $this->actingAs($staff)->post(route('capitol.categories.types.store'), ['name' => 'Cafe', 'industry' => 'G', 'category' => 'retail'])->assertForbidden();
    $this->actingAs($staff)->put(route('capitol.categories.types.update', 'crop-farm'), ['name' => 'Farm', 'industry' => 'A', 'category' => 'agriculture'])->assertForbidden();
    $this->actingAs($staff)->post(route('capitol.categories.store'), ['name' => 'Fishery'])->assertForbidden();
    $this->actingAs($staff)->put(route('capitol.categories.update', 'agriculture'), ['name' => 'Farming', 'color' => 2])->assertForbidden();

    expect(BusinessCategory::query()->count())->toBe(2)->and(BusinessType::query()->count())->toBe(2);
});

it('adds a Business Type under one Category and logs it', function () {
    $this->actingAs($this->admin)->post(route('capitol.categories.types.store'), [
        'name' => 'LPG Dealer', 'industry' => 'G', 'category' => 'retail',
    ])->assertRedirect();

    $type = BusinessType::query()->where('code', 'lpg-dealer')->firstOrFail();
    expect($type->name)->toBe('LPG Dealer')
        ->and($type->psic_section_name)->toBe('Wholesale and retail trade')
        ->and($type->category->code)->toBe('retail')
        ->and(DB::table('activity_logs')->where('event', 'business_type_created')->where('actor_id', $this->admin->id)->count())->toBe(1);
});

it('refuses a bad Business Type', function (array $input, string $field) {
    $this->actingAs($this->admin)->post(route('capitol.categories.types.store'), $input + ['industry' => 'G', 'category' => 'retail'])
        ->assertSessionHasErrors($field);

    expect(BusinessType::query()->count())->toBe(2);
})->with([
    'same name ignoring case' => [['name' => 'rice AND corn farm'], 'name'],
    'same code' => [['name' => 'Shop!'], 'name'],
    'markup' => [['name' => '<b>Cafe</b>'], 'name'],
    'punctuation only' => [['name' => '???'], 'name'],
    'too long' => [['name' => str_repeat('a', 61)], 'name'],
    'unknown industry' => [['name' => 'Cafe', 'industry' => 'Z'], 'industry'],
    'unknown category' => [['name' => 'Cafe', 'category' => 'mining'], 'category'],
    'no category' => [['name' => 'Cafe', 'category' => ''], 'category'],
]);

it('stops adding types at the limit', function () {
    config(['cagayan.categories.max_types' => 2]);

    $this->actingAs($this->admin)->post(route('capitol.categories.types.store'), ['name' => 'Cafe', 'industry' => 'G', 'category' => 'retail'])
        ->assertSessionHasErrors('name');
});

it('renames a Business Type and moves it to another Category without changing its code', function () {
    $this->actingAs($this->admin)->put(route('capitol.categories.types.update', 'crop-farm'), [
        'name' => 'Rice and Corn Farms', 'industry' => 'G', 'category' => 'retail',
    ])->assertRedirect();

    $this->farm->refresh();
    expect($this->farm->code)->toBe('crop-farm')
        ->and($this->farm->name)->toBe('Rice and Corn Farms')
        ->and($this->farm->psic_section)->toBe('G')
        ->and($this->farm->category->code)->toBe('retail')
        ->and(DB::table('activity_logs')->where('event', 'business_type_updated')->count())->toBe(1);
});

it('lets a Business Type keep its own name when saving', function () {
    $this->actingAs($this->admin)->put(route('capitol.categories.types.update', 'crop-farm'), [
        'name' => 'Rice and Corn Farm', 'industry' => 'A', 'category' => 'agriculture',
    ])->assertSessionHasNoErrors();
});

it('refuses a Business Type without a Category at the database', function () {
    expect(fn () => DB::table('business_types')->insert([
        'code' => 'orphan', 'name' => 'Orphan', 'psic_section' => 'G', 'psic_section_name' => 'Trade', 'sort_order' => 9,
        'created_at' => now(), 'updated_at' => now(),
    ]))->toThrow(QueryException::class);
});

it('adds a Category with the least used color and logs it', function () {
    BusinessCategory::query()->where('code', 'retail')->update(['color' => 2]);

    $this->actingAs($this->admin)->post(route('capitol.categories.store'), ['name' => 'Tourism'])->assertRedirect();

    // Colors 1 and 2 are taken, so the next category takes 3.
    expect(BusinessCategory::query()->where('code', 'tourism')->value('color'))->toBe(3)
        ->and(DB::table('activity_logs')->where('event', 'business_category_created')->where('actor_id', $this->admin->id)->count())->toBe(1);
});

it('refuses a Category that repeats an existing one or has no letters', function (string $name) {
    $this->actingAs($this->admin)->post(route('capitol.categories.store'), ['name' => $name])->assertSessionHasErrors('name');

    expect(BusinessCategory::query()->count())->toBe(2);
})->with(['same name' => 'AGRICULTURE', 'same code' => 'agriculture!', 'punctuation only' => '???', 'markup' => '<b>Tag</b>']);

it('renames and recolors a Category without changing its code', function () {
    $this->actingAs($this->admin)->put(route('capitol.categories.update', 'agriculture'), ['name' => 'Farming', 'color' => 7])
        ->assertRedirect();

    $this->agriculture->refresh();
    expect($this->agriculture->code)->toBe('agriculture')->and($this->agriculture->name)->toBe('Farming')->and($this->agriculture->color)->toBe(7)
        ->and(DB::table('activity_logs')->where('event', 'business_category_updated')->count())->toBe(1);
});

it('refuses a color outside the palette', function (mixed $color) {
    $this->actingAs($this->admin)->put(route('capitol.categories.update', 'agriculture'), ['name' => 'Agriculture', 'color' => $color])
        ->assertSessionHasErrors('color');
})->with([0, 11, 'red', null]);
