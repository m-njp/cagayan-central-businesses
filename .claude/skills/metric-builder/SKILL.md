---
name: metric-builder
description: Implement one metric (M1 to M17) from docs/engineering/metrics.md in SQL, wired into metrics:refresh, test first with a hand-built fixture.
argument-hint: "[metric id, e.g. M1]"
arguments: [metric]
disable-model-invocation: true
---

# Build metric $metric

**Role: Metric engineer.** Own the SQL and fixture for the approved definition. Choose query shape and indexes based on real data flow. Escalate ambiguity in geography, eligibility, calculation or disclosure. Hand off expected and actual fixture values plus the definition reference.

Every number on screen has one definition, one SQL implementation, and one test (decision D7). The definition is fixed. The code follows it, never the reverse.

## Read first

1. `docs/engineering/metrics.md`: "Building blocks", "Three states every value can be in", the entry for $metric, "Config values", "How each metric is tested".
2. `docs/engineering/database.md`: "Summary tables" and the `business_years` status rules.
3. `docs/engineering/privacy-rules.md` if $metric involves sales (M3, M4, M5 by sales, M6 and M7 by sales, M12, M15).
4. `app/Console/Commands/RefreshMetricsCommand.php` and what is already in `database/sql/metrics/`.

## Steps

1. **Branch.** If on `main` or `staging`, create a work branch inside `staging`: `feat/<metric-id>-<short-name>` (for example `feat/m1-net-growth`).
2. **Check the definition.** Anything ambiguous (a denominator, a tie, a year boundary, a missing case): stop and ask. Items marked **Choice** in `metrics.md` are settled defaults. Use them as written.
3. **Check what it needs.** Most metrics read `stats_area_year`. If it or the raw tables do not exist yet, stop and say which skill runs first (`/db-change`, or `/metric-builder M1`, which creates `stats_area_year`).
4. **Write the test first** in `tests/Feature/Metrics/`, named after the metric (`NetGrowthTest.php`):
   - A tiny hand-built fixture: two or three barangays, a handful of businesses, two to four years. Never the demo seeder.
   - Above each assertion, a short comment with the hand calculation, so a reviewer can check the arithmetic without running anything.
   - `$this->artisan('metrics:refresh')->assertSuccessful()`, then assert the stored value.
   - One test for "not available" (for example a zero denominator). It must come back as not available, not `0`.
   - For sales, one test for "hidden".
   Watch it fail.
5. **Implement in SQL** in `database/sql/metrics/<summary_table>.sql`, one file per summary table. A file rebuilds its whole table so a refresh is always complete. Keep the SQL plain and readable: a judge may be shown it.
6. **Wire it into `metrics:refresh`.** Run the files in dependency order (`stats_area_year` first) inside one transaction, so a failed refresh never leaves half-updated tables.
7. **Thresholds from config,** passed as bindings. No literals for anything in "Config values".
8. **Sales stay protected.** Only this SQL reads `declared_gross_sales`. Store totals plus `declared_sales_max_single` where the guard needs it. Hiding happens in the guard when the figure is read.
9. **Run** `php artisan test --compact tests/Feature/Metrics/`, then `composer db:reset` to prove the refresh runs on the demo data.
10. **Update `docs/team/roadmap.md`:** $metric to `Done`.
11. **Never edit `metrics.md` to match the code.** If the definition looks wrong, stop and raise it. Changing it needs a pull request with two reviewers.

## Done when

The metric test, its "not available" test, and (for sales) its "hidden" test pass with hand calculations in comments, `metrics:refresh` fills the table on the demo data, and the roadmap shows $metric as Done. Then suggest `/commit`.
