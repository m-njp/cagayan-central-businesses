<?php

namespace Database\Seeders;

use App\Models\Municipality;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

/**
 * Codes, names, and kinds of Cagayan's 29 towns, read from the boundary file
 * so they match PSA's PSGC. Only Tuguegarao City is connected in the MVP.
 * The shapes themselves are loaded with roadmap F2.
 */
class MunicipalitySeeder extends Seeder
{
    private const string TUGUEGARAO = '0201529000';

    public function run(): void
    {
        /** @var array{features: list<array{properties: array{psgc_code: string, name: string, kind: string}}>} $collection */
        $collection = File::json(base_path('data/boundaries/cagayan-municipalities.geojson'));

        foreach ($collection['features'] as $feature) {
            $properties = $feature['properties'];

            Municipality::query()->updateOrCreate(
                ['psgc_code' => $properties['psgc_code']],
                [
                    'name' => $properties['name'],
                    'kind' => $properties['kind'],
                    'is_connected' => $properties['psgc_code'] === self::TUGUEGARAO,
                ],
            );
        }
    }
}
