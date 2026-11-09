<?php

namespace App\Models;

use Database\Factories\WishFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $municipality_id
 * @property int|null $business_type_id
 * @property string|null $specific_business
 * @property string|null $reason
 * @property string $submitter_hash
 * @property string|null $network_hash
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
#[Fillable(['municipality_id', 'business_type_id', 'specific_business', 'reason', 'submitter_hash', 'network_hash'])]
#[Hidden(['specific_business', 'reason', 'submitter_hash', 'network_hash'])]
class Wish extends Model
{
    /** @use HasFactory<WishFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Municipality, $this>
     */
    public function municipality(): BelongsTo
    {
        return $this->belongsTo(Municipality::class);
    }

    /**
     * @return BelongsTo<BusinessType, $this>
     */
    public function businessType(): BelongsTo
    {
        return $this->belongsTo(BusinessType::class);
    }

    /**
     * @return HasMany<WishEdit, $this>
     */
    public function edits(): HasMany
    {
        return $this->hasMany(WishEdit::class);
    }
}
