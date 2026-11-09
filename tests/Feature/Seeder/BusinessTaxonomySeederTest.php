<?php

use App\Models\BusinessCategory;
use App\Models\BusinessType;
use Database\Seeders\BusinessCategorySeeder;
use Database\Seeders\BusinessTypeSeeder;

beforeEach(function () {
    $this->seed([BusinessCategorySeeder::class, BusinessTypeSeeder::class]);
});

it('puts every seeded Business Type under exactly one Category', function () {
    expect(BusinessType::query()->count())->toBe(32)
        ->and(BusinessType::query()->whereNull('business_category_id')->count())->toBe(0)
        ->and(BusinessCategory::query()->count())->toBe(13)
        // Every category has at least one type, so none is an empty starter label.
        ->and(BusinessCategory::query()->whereDoesntHave('businessTypes')->count())->toBe(0);
});

it('keeps a Boarding House out of Tourism', function () {
    $category = fn (string $type) => BusinessType::query()->where('code', $type)->firstOrFail()->category->code;

    expect($category('boarding-house'))->toBe('lodging-and-rentals')
        ->and($category('hotel'))->toBe('tourism')
        ->and($category('rice-mill'))->toBe('food-processing')
        ->and($category('auto-repair'))->toBe('vehicle-motor-parts');
});

it('leaves an Admin rename of a Category alone when seeded again', function () {
    BusinessCategory::query()->where('code', 'retail')->update(['name' => 'Retail Trade', 'color' => 9]);

    $this->seed(BusinessCategorySeeder::class);

    expect(BusinessCategory::query()->where('code', 'retail')->first())
        ->name->toBe('Retail Trade')->color->toBe(9);
});
