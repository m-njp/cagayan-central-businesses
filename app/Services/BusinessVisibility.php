<?php

namespace App\Services;

use App\Models\Business;
use Illuminate\Database\Query\Builder;

/** Simulated records appear only while CAGAYAN_SIMULATED is on (D37); registry records entered by staff always do (D40). */
final class BusinessVisibility
{
    public static function apply(Builder $query, string $alias = 'b'): Builder
    {
        return $query->when(! config('cagayan.simulated'), fn (Builder $q) => $q->where("{$alias}.is_simulated", false));
    }

    public static function allows(Business $business): bool
    {
        return config('cagayan.simulated') || ! $business->is_simulated;
    }

    /** Public routes also see approved records only (P15). */
    public static function public(Builder $query, string $alias = 'b'): Builder
    {
        return self::apply($query, $alias)->where("{$alias}.record_status", 'approved');
    }
}
