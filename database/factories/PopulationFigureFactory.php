<?php

namespace Database\Factories;

use App\Enums\FinanceFigureStatus;
use App\Models\Municipality;
use App\Models\PopulationFigure;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<PopulationFigure>
 */
class PopulationFigureFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'public_id' => (string) Str::uuid(),
            'municipality_id' => Municipality::factory(),
            'year' => 2024,
            'value' => fake()->numberBetween(1000, 200000),
            'source_url' => 'https://psa.gov.ph/statistics/population-and-housing',
            'status' => FinanceFigureStatus::Pending,
        ];
    }
}
