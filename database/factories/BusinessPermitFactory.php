<?php

namespace Database\Factories;

use App\Models\Business;
use App\Models\BusinessPermit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BusinessPermit>
 */
class BusinessPermitFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $year = (int) now()->year;

        return [
            'business_id' => Business::factory(),
            'permit_number' => 'BP-'.$year.'-'.fake()->numerify('#####'),
            'year' => $year,
            'valid_from' => "{$year}-01-01",
            'valid_until' => "{$year}-12-31",
        ];
    }

    public function expiringInDays(int $days): static
    {
        return $this->state(fn () => ['valid_until' => now()->addDays($days)->toDateString()]);
    }
}
