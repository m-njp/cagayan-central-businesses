<?php

namespace App\Models;

use Database\Factories\BarangayFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $municipality_id
 * @property string $psgc_code
 * @property string $name
 * @property string|null $urban_rural
 * @property int|null $population
 * @property int|null $population_year
 * @property string|null $population_source
 */
#[Fillable(['municipality_id', 'psgc_code', 'name', 'urban_rural', 'population', 'population_year', 'population_source'])]
#[Hidden(['boundary'])]
class Barangay extends Model
{
    /** @use HasFactory<BarangayFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Municipality, $this>
     */
    public function municipality(): BelongsTo
    {
        return $this->belongsTo(Municipality::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'population' => 'integer',
            'population_year' => 'integer',
        ];
    }
}
