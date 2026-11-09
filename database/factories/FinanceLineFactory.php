<?php

namespace Database\Factories;

use App\Models\FinanceLine;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FinanceLine>
 */
class FinanceLineFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => 'performance.'.fake()->unique()->word(),
            'parent_code' => null,
            'label' => fake()->words(2, true),
            'group' => 'totals',
            'unit' => 'php',
            'displayed' => true,
            'source' => 'executive_summary',
            'sort' => fake()->numberBetween(1, 100),
        ];
    }
}
