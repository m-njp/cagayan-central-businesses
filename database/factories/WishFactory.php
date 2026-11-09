<?php

namespace Database\Factories;

use App\Models\BusinessType;
use App\Models\Municipality;
use App\Models\Wish;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Wish>
 */
class WishFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'municipality_id' => Municipality::factory(),
            'business_type_id' => BusinessType::factory(),
            'reason' => fake()->optional()->sentence(),
            'submitter_hash' => hash('sha256', fake()->unique()->uuid()),
        ];
    }
}
