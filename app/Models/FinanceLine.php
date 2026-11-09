<?php

namespace App\Models;

use Database\Factories\FinanceLineFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * One line of the finance catalog, loaded from data/towns/finances/lines.csv (D35).
 *
 * @property int $id
 * @property string $code
 * @property string|null $parent_code
 * @property string $label
 * @property string $group
 * @property string $unit
 * @property bool $displayed
 * @property string $source
 * @property int $sort
 */
#[Fillable(['code', 'parent_code', 'label', 'group', 'unit', 'displayed', 'source', 'sort'])]
class FinanceLine extends Model
{
    /** @use HasFactory<FinanceLineFactory> */
    use HasFactory;

    /**
     * Lines a person can enter or review. Derived lines are computed in SQL and never entered.
     *
     * @param  Builder<FinanceLine>  $query
     */
    #[Scope]
    protected function enterable(Builder $query): void
    {
        $query->where('displayed', true)->where('source', '!=', 'derived');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'displayed' => 'boolean',
            'sort' => 'integer',
        ];
    }
}
