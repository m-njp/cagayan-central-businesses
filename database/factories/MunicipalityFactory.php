<?php

namespace Database\Factories;

use App\Enums\MunicipalityKind;
use App\Models\Municipality;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Municipality>
 */
class MunicipalityFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            // 01 to 99: 00 would equal the province code 0201500000 and collide with the all-towns row.
            'psgc_code' => '02015'.sprintf('%02d', fake()->unique()->numberBetween(1, 99)).'000',
            'name' => fake()->unique()->city(),
            'kind' => MunicipalityKind::Municipality,
            'is_connected' => false,
        ];
    }

    public function connected(): static
    {
        return $this->state(fn () => ['is_connected' => true]);
    }
}
