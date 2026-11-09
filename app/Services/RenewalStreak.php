<?php

namespace App\Services;

/**
 * M33 Renewal Streak: consecutive active years ending in the latest data year. A business's history stops at its
 * first non-renewal or closure (database.md, status rules), so the streak is its row count while it is still active.
 */
final class RenewalStreak
{
    /** A scalar subquery over the business aliased `b`; bind self::bindings(). */
    public const string SQL = "(select case when count(*) filter (where y.status in ('not_renewed', 'closed')) = 0
        and max(y.year) = ? then count(*) else 0 end from business_years y where y.business_id = b.id)";

    /** @return list<int> */
    public static function bindings(): array
    {
        return [(int) config('cagayan.data.latest_year')];
    }
}
