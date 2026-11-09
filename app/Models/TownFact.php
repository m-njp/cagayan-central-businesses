<?php

namespace App\Models;

use App\Enums\TownMeasure;
use Database\Factories\TownFactFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $municipality_id
 * @property TownMeasure $measure
 * @property int $year
 * @property string $value
 * @property string $source_url
 * @property Carbon|null $checked_on
 */
#[Fillable(['municipality_id', 'measure', 'year', 'value', 'source_url', 'checked_on'])]
class TownFact extends Model
{
    /** @use HasFactory<TownFactFactory> */
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
            'measure' => TownMeasure::class,
            'year' => 'integer',
            'value' => 'decimal:2',
            'checked_on' => 'date',
        ];
    }
}
