<?php

namespace Database\Seeders;

use App\Models\BusinessCategory;
use Illuminate\Database\Seeder;

/**
 * Starter Business Categories (D39). Admins rename, recolor and add categories on the Business Categories page,
 * so existing rows are left alone. The data member reviews this list.
 */
class BusinessCategorySeeder extends Seeder
{
    /**
     * @var list<array{0: string, 1: string}>
     */
    private const array CATEGORIES = [
        ['retail', 'Retail'],
        ['vehicle-motor-parts', 'Vehicle and Motor Parts'],
        ['food-processing', 'Food Processing'],
        ['food-and-beverage', 'Food and Beverage'],
        ['tourism', 'Tourism'],
        ['lodging-and-rentals', 'Lodging and Rentals'],
        ['agriculture', 'Agriculture'],
        ['fishery', 'Fishery'],
        ['finance', 'Finance'],
        ['health-services', 'Health Services'],
        ['education', 'Education'],
        ['personal-and-repair-services', 'Personal and Repair Services'],
        ['other', 'Other Services'],
    ];

    public function run(): void
    {
        $colors = (int) config('cagayan.categories.colors');

        foreach (self::CATEGORIES as $order => [$code, $name]) {
            BusinessCategory::query()->firstOrCreate(
                ['code' => $code],
                ['name' => $name, 'color' => ($order % $colors) + 1, 'sort_order' => $order + 1],
            );
        }
    }
}
