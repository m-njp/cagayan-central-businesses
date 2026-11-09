<?php

namespace App\Models;

use App\Enums\MunicipalityKind;
use Database\Factories\MunicipalityFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $psgc_code
 * @property string $name
 * @property MunicipalityKind $kind
 * @property bool $is_connected
 */
#[Fillable(['psgc_code', 'name', 'kind', 'is_connected'])]
#[Hidden(['boundary'])]
class Municipality extends Model
{
    /** @use HasFactory<MunicipalityFactory> */
    use HasFactory;

    /**
     * @return HasMany<Wish, $this>
     */
    public function wishes(): HasMany
    {
        return $this->hasMany(Wish::class);
    }

    /**
     * @return HasMany<Barangay, $this>
     */
    public function barangays(): HasMany
    {
        return $this->hasMany(Barangay::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'kind' => MunicipalityKind::class,
            'is_connected' => 'boolean',
        ];
    }
}
