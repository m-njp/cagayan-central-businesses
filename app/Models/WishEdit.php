<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Insert only. One row per edit, so the weekly edit limit can be counted.
 *
 * @property int $id
 * @property int $wish_id
 * @property Carbon $edited_at
 */
#[Fillable(['wish_id', 'edited_at'])]
class WishEdit extends Model
{
    public $timestamps = false;

    /**
     * @return BelongsTo<Wish, $this>
     */
    public function wish(): BelongsTo
    {
        return $this->belongsTo(Wish::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'edited_at' => 'datetime',
        ];
    }
}
