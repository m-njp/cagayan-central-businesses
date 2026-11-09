<?php

namespace Database\Seeders;

use App\Models\Barangay;
use App\Models\Municipality;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

/**
 * Codes, names and shapes of Tuguegarao City's 49 barangays, read from the boundary
 * file so they match PSA's PSGC. Population comes from barangays:import.
 */
class BarangaySeeder extends Seeder
{
    public function run(): void
    {
        /** @var array{features: list<array{geometry: array<string, mixed>, properties: array{psgc_code: string, name: string, municipality_psgc_code: string}}>} $collection */
        $collection = File::json(base_path('data/boundaries/tuguegarao-barangays.geojson'));

        /** @var array<string, int> $towns */
        $towns = Municipality::query()->pluck('id', 'psgc_code')->all();

        foreach ($collection['features'] as $feature) {
            $properties = $feature['properties'];

            Barangay::query()->updateOrCreate(
                ['psgc_code' => $properties['psgc_code']],
                [
                    'municipality_id' => $towns[$properties['municipality_psgc_code']],
                    'name' => $properties['name'],
                ],
            );
            // Shapes let simulated business points fall inside their barangay (seeder requirement 9).
            DB::update('update barangays set boundary = ST_Multi(ST_MakeValid(ST_SetSRID(ST_GeomFromGeoJSON(?), 4326))) where psgc_code = ?', [
                json_encode($feature['geometry'], JSON_THROW_ON_ERROR), $properties['psgc_code'],
            ]);
        }
    }
}
