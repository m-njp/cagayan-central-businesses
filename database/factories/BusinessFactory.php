<?php

namespace Database\Factories;

use App\Enums\OwnershipType;
use App\Enums\RecordStatus;
use App\Enums\SizeCategory;
use App\Models\Barangay;
use App\Models\Business;
use App\Models\BusinessType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * @extends Factory<Business>
 */
class BusinessFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'public_id' => (string) Str::uuid(),
            'business_name' => fake()->company(),
            'business_type_id' => BusinessType::factory(),
            'description' => fake()->sentence(),
            'year_established' => 2018,
            'barangay_id' => Barangay::factory(),
            // Central Tuguegarao; the demo seeder places points inside each barangay instead.
            'location' => DB::raw('ST_SetSRID(ST_MakePoint(121.7270, 17.6132), 4326)'),
            'owner_name' => fake()->name(),
            'contact_number' => '09'.fake()->numerify('#########'),
            'ownership_type' => OwnershipType::SoleProprietorship,
            'size_category' => SizeCategory::Micro,
            'employee_count' => fake()->numberBetween(1, 9),
            'is_simulated' => true,
            'record_status' => RecordStatus::Approved,
        ];
    }

    public function inBarangay(Barangay $barangay): static
    {
        return $this->state(fn () => ['barangay_id' => $barangay->id]);
    }

    public function ofType(BusinessType $type): static
    {
        return $this->state(fn () => ['business_type_id' => $type->id]);
    }

    public function status(RecordStatus $status): static
    {
        return $this->state(fn () => ['record_status' => $status]);
    }

    public function size(SizeCategory $size): static
    {
        return $this->state(fn () => ['size_category' => $size]);
    }
}
