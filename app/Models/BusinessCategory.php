<?php

namespace App\Models;

use Database\Factories\BusinessCategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * An admin-managed sector such as Agriculture or Retail. Every Business Type belongs to exactly one (D39).
 *
 * @property int $id
 * @property string $code
 * @property string $name
 * @property int $color
 * @property int $sort_order
 */
#[Fillable(['code', 'name', 'color', 'sort_order'])]
class BusinessCategory extends Model
{
    /** @use HasFactory<BusinessCategoryFactory> */
    use HasFactory;

    /**
     * @return HasMany<BusinessType, $this>
     */
    public function businessTypes(): HasMany
    {
        return $this->hasMany(BusinessType::class);
    }
}
