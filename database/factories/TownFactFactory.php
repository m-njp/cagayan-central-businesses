<?php

namespace Database\Factories;

use App\Enums\TownMeasure;
use App\Models\Municipality;
use App\Models\TownFact;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TownFact>
 */
class TownFactFactory extends Factory
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
            'measure' => TownMeasure::Population,
            'year' => 2024,
            'value' => fake()->numberBetween(5_000, 170_000),
            'source_url' => 'https://psa.gov.ph/',
            'checked_on' => null,
        ];
    }

    public function verified(): static
    {
        return $this->state(fn () => ['checked_on' => '2026-10-24']);
    }

    public function measure(TownMeasure $measure, int $year, float|int $value): static
    {
        return $this->state(fn () => ['measure' => $measure, 'year' => $year, 'value' => $value]);
    }
}
