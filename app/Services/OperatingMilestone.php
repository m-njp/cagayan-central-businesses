<?php

namespace App\Services;

/**
 * M34 Operating Milestone: the highest of `cagayan.registry.milestones` reached by the years since the business was
 * established, counted to the latest data year. Null below the first milestone, or when the business is not active that year.
 */
final class OperatingMilestone
{
    /** A scalar subquery over the business aliased `b`; bind self::bindings(). */
    public const string SQL = "(select max(m) from unnest(?::int[]) as m where ? - b.year_established >= m
        and exists (select 1 from business_years y where y.business_id = b.id and y.year = ? and y.status in ('new', 'renewed')))";

    /** @return list<int|string> */
    public static function bindings(): array
    {
        $latest = (int) config('cagayan.data.latest_year');

        return ['{'.implode(',', config('cagayan.registry.milestones')).'}', $latest, $latest];
    }
}
