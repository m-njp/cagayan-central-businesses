<?php

namespace App\Models;

use App\Enums\BusinessStatus;
use Database\Factories\BusinessYearFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A business's status in one year. Declared sales are never displayed; only database/sql/metrics reads them (P1).
 *
 * @property int $id
 * @property int $business_id
 * @property int $year
 * @property BusinessStatus $status
 * @property string|null $declared_gross_sales
 */
#[Fillable(['business_id', 'year', 'status', 'declared_gross_sales'])]
#[Hidden(['declared_gross_sales'])]
class BusinessYear extends Model
{
    /** @use HasFactory<BusinessYearFactory> */
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
        return [
            'year' => 'integer',
            'status' => BusinessStatus::class,
            'declared_gross_sales' => 'decimal:2',
        ];
    }
}
