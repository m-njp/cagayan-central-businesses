<?php

namespace Database\Factories;

use App\Models\BusinessCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BusinessCategory>
 */
class BusinessCategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = ucfirst(fake()->unique()->word());

        return [
            'code' => strtolower($name),
            'name' => $name,
            'color' => 1,
            'sort_order' => fake()->numberBetween(1, 50),
        ];
    }
}
