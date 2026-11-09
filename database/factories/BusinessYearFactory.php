<?php

namespace Database\Factories;

use App\Enums\BusinessStatus;
use App\Models\Business;
use App\Models\BusinessYear;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BusinessYear>
 */
class BusinessYearFactory extends Factory
{
    /**
     * Define the model's default state. A renewal with declared sales.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'business_id' => Business::factory(),
            'year' => 2024,
            'status' => BusinessStatus::Renewed,
            'declared_gross_sales' => fake()->numberBetween(300_000, 5_000_000).'.00',
        ];
    }

    public function status(BusinessStatus $status, int $year): static
    {
        return $this->state(fn () => [
            'status' => $status,
            'year' => $year,
            'declared_gross_sales' => $status->isActive() ? fake()->numberBetween(300_000, 5_000_000).'.00' : null,
        ]);
    }

    public function sales(string $sales): static
    {
        return $this->state(fn () => ['declared_gross_sales' => $sales]);
    }
}
