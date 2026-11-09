<?php

namespace Database\Factories;

use App\Models\BusinessCategory;
use App\Models\BusinessType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BusinessType>
 */
class BusinessTypeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->lexify('type ?????');

        return [
            'code' => str($name)->slug()->toString(),
            'name' => ucfirst($name),
            'psic_section' => 'G',
            'psic_section_name' => 'Wholesale and retail trade',
            'business_category_id' => BusinessCategory::factory(),
            'sort_order' => fake()->numberBetween(1, 100),
        ];
    }
}
