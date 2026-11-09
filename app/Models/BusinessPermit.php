<?php

namespace App\Models;

use Database\Factories\BusinessPermitFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One year's business permit (Mayor's permit). The number is an admin field; the public sees only M32 Permit Status.
 *
 * @property int $id
 * @property int $business_id
 * @property string $permit_number
 * @property int $year
 * @property Carbon $valid_from
 * @property Carbon $valid_until
 */
#[Fillable(['business_id', 'permit_number', 'year', 'valid_from', 'valid_until'])]
class BusinessPermit extends Model
{
    /** @use HasFactory<BusinessPermitFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Business, $this>
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['year' => 'integer', 'valid_from' => 'date', 'valid_until' => 'date'];
    }
}
