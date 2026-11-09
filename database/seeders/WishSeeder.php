<?php

namespace Database\Seeders;

use App\Models\BusinessType;
use App\Models\Municipality;
use App\Models\Wish;
use Illuminate\Database\Seeder;

/**
 * Demo wishes for Tuguegarao City, one row per wish, counted from the same sample file the Economy page shows
 * (data/economic-performance/demo.php) so both agree. Fixed counts, so every laptop shows the same.
 */
class WishSeeder extends Seeder
{
    public function run(): void
    {
        $town = Municipality::query()->where('psgc_code', '0201529000')->firstOrFail();
        $counts = array_column((require base_path('data/economic-performance/demo.php'))['wishes']['types'], 'count', 'id');
        $types = BusinessType::query()->whereIn('code', array_keys($counts))->pluck('id', 'code');

        Wish::query()->where('submitter_hash', 'like', 'seed-%')->delete();

        foreach ($counts as $code => $count) {
            foreach (range(1, $count) as $index) {
                Wish::query()->create([
                    'municipality_id' => $town->id,
                    'business_type_id' => $types[$code],
                    'reason' => null,
                    'submitter_hash' => "seed-{$code}-{$index}",
                ]);
            }
        }
    }
}
