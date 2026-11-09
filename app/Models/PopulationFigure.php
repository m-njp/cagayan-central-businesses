<?php

namespace App\Models;

use App\Enums\FinanceFigureStatus;
use Database\Factories\PopulationFigureFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A proposed census population for a town (no barangay) or one barangay. Nothing here is read by a map: a verified
 * figure is published into town_facts or barangays, and the proposal stays as history (Data Entry, Phase 4).
 *
 * @property int $id
 * @property string $public_id
 * @property int $municipality_id
 * @property int|null $barangay_id
 * @property int $year
 * @property int $value
 * @property string $source_url
 * @property FinanceFigureStatus $status
 * @property int|null $entered_by
 * @property int|null $reviewed_by
 * @property Carbon|null $reviewed_on
 * @property string|null $note
 */
#[Fillable(['public_id', 'municipality_id', 'barangay_id', 'year', 'value', 'source_url', 'status', 'entered_by', 'reviewed_by', 'reviewed_on', 'note'])]
class PopulationFigure extends Model
{
    /** @use HasFactory<PopulationFigureFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Municipality, $this>
     */
    public function municipality(): BelongsTo
    {
        return $this->belongsTo(Municipality::class);
    }

    /**
     * @return BelongsTo<Barangay, $this>
     */
    public function barangay(): BelongsTo
    {
        return $this->belongsTo(Barangay::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'value' => 'integer',
            'status' => FinanceFigureStatus::class,
            'reviewed_on' => 'date',
        ];
    }
}
