<?php

namespace Database\Factories;

use App\Models\Barangay;
use App\Models\Municipality;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Barangay>
 */
class BarangayFactory extends Factory
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
            'psgc_code' => '0201529'.fake()->unique()->numerify('###'),
            'name' => fake()->unique()->streetName(),
            'urban_rural' => null,
            'population' => null,
            'population_year' => null,
            'population_source' => null,
        ];
    }

    public function withPopulation(int $population, int $year = 2024): static
    {
        return $this->state(fn () => [
            'population' => $population,
            'population_year' => $year,
            'population_source' => 'https://psa.gov.ph/',
        ]);
    }
}
