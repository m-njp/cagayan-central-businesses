---
title: "Client Facing Polishing"
created: "26-10-27"
updated: "26-10-28"
status: completed
branch: "feat/client-facing-polishing (merged into feat/business-analytics, 26-10-28)"
base: staging
predecessor: null
---

# Client Facing Polishing

## Objective

Make local review of the Capitol workspace faster without putting credentials in the repository. The first slice is a local-only quick sign in with one demo account per role (Super Admin, Admin, Staff). Requested 26-10-27.

## Scope and Acceptance

- [x] The seeder creates a demo Super Admin, Admin, and Staff. Each account's name, email, and password come from `.env` only; a role with no values is skipped.
- [x] `php artisan db:seed --class=DemoAccountSeeder` adds missing demo accounts without resetting the database.
- [x] The hidden login page shows one button per seeded demo role, and pressing it signs in as that account and lands on `/capitol`.
- [x] The buttons and the route that serves them exist only when `APP_ENV=local`. Any other environment gets a plain 404 and no buttons.
- [x] A quick sign in goes through the same session setup and Activity Logs entry as a password sign in.
- [x] The login path still does not appear in the built JavaScript (`LoginPathOutOfBundleTest`).
- [x] No credentials in the repository: only `.env.example` defaults.
- [x] Decision recorded in `docs/engineering/decisions.md`, startup docs updated.

### Slice 2: Wish cloud sized by relative demand (requested 26-10-27)

- [x] Word size is the count's position between the smallest and largest displayed count on a log scale (the visx `scaleLog` approach, computed in `lib/wish-cloud-size.ts` without adding `@visx/scale`), so 8-84, 100-180 and 100000-180000 render the same ordering and range, and one outlier (1000 against 10 to 50) leaves the other words distinguishable.
- [x] No per-count or per-label size cap; one shared maximum is lowered so every label fits, so a long label is never smaller than a lower-demand short one.
- [x] A layout that drops a displayed word is retried smaller; at the floor, the cloud shows only the words ranked above the first unplaced one, and the rest become count chips.
- [x] Color follows demand (upper half of the range uses the chart accent) instead of layout position.
- [x] Resize no longer relays out on every pixel; per-word `find` removed.

## Deferred or Excluded

- Assigning towns to the demo Staff account (Staff editing is future work, D28).
- Any production or staging sign in shortcut, and any Microsoft or Google sign in (D28).
- Further client facing polishing items, which get their own entries here when requested.

### Local Finance Preview Integration, 26-10-28

Requested by the user so the finance review and quick sign in are available together on port 8000. The `feat/local-finance-polishing` branch combines polishing commit `2a08ba7`, finance ledger commit `4b864f0`, and a snapshot of the 28 staged finance review files from the finance checkout. The finance checkout itself is unchanged. This is a local integration, with no push or shared-branch merge.

- [x] Retain the three local quick sign-in buttons and wish-cloud polishing.
- [x] Include Government Finances list, report, manual entry, and review routes from the finance implementation.
- [x] Use the existing `cagayan_business_finance` database for port 8000; only add missing demo users with `DemoAccountSeeder`. No migration or reset of the shared development database.
- [x] Restore the saved seeder fix in `DemoAccountSeeder`, assigning public UUIDs explicitly while the parent `DatabaseSeeder` suppresses model events.
- [x] Add full-seeder PostGIS UUID and existing-account regression tests and same-app quick-sign-in-to-finance coverage for all roles.
- [x] Complete full CI verification of the combined branch.

## Decisions and Sources

- 26-10-27: Gate by `app()->isLocal()` checked on each request, not at route registration, so tests can exercise both states and a cached route list cannot enable it elsewhere. Reason: the shortcut bypasses the password, so it must fail closed.
- 26-10-27: Quick sign in is not rate limited. It takes no guessable input and is unreachable outside local.
- Related: D20 (discreet login), D28 (roles and sessions).

## Change History

| Date | Change | Reason / Reference |
| :- | :- | :- |
| 26-10-27 | Plan created on `feat/client-facing-polishing` from `staging` | User request |
| 26-10-27 | Wish cloud sizing rebuilt: `lib/wish-cloud-size.ts`, `wish-word-cloud.tsx`, frontend.md sizing contract | Old formula capped at 54px from 100 wishes, per-word width cap inverted order, dropped words ranked above shown ones, color followed layout index |
| 26-10-27 | Quick sign in built: `DemoAccountSeeder`, `DemoLoginQuery`, `SignInAsDemoAccount`, `DemoLoginController`, login page buttons, decision D33 | Acceptance checks above |

## Verification and Handoff

- Automated: `php artisan test --compact` passed (164 tests), including `DemoAccountSeederTest`, `DemoLoginTest` (each role signs in locally, 404 in production, staging, and testing, 404 for a missing, inactive, or unknown role, buttons listed only locally) and `LoginPathOutOfBundleTest` after `npm run build`.
- Frontend: `npm run check:fix`, `npm run types:check`, and `npm run build` passed.
- Manual in the built-in browser on `localhost:8000`: Staff and Super Admin buttons signed in and landed on `/capitol`; the login page rendered at 375px and desktop width. Admin was not clicked. Dark mode only; light mode and reduced motion were not checked. A console error about server rendering and Suspense appeared after sign in and was not traced to this change.
- Local setup: the existing `admin@cagayan.test` account already held Super Admin, so the local `.env` maps Super Admin to it and Admin to a new `demoadmin@cagayan.test`. This lives only in the gitignored `.env`.
- Wish cloud slice: a scratchpad script ran `wishCloudSizes` on the demo counts, the same counts scaled to 100-180 and to x1000, all equal, one word, two words and a 343px width, plus 5,000 random datasets. Zero violations of: higher count never smaller, equal counts equal, sizes within 12 to 56px. `npm run check:fix`, `npm run check`, `npm run types:check`, `npm run build` passed; `EconomicPerformanceTest` passed (20 tests); `/design-check` found nothing.
- Wish cloud slice, not run by Claude (user chose to test manually): Economy page at desktop and 375px in light and dark; top words read Pharmacy, Grocery, Coffee Shop, Water Refilling Station in size order and no count chip has more wishes than a cloud word; temporary fixture edit to 100-180 and to all-equal counts, then reverted; window resize without flashing; zero application requests when switching Cloud and Bars or town.
- Sizing switched from linear to log on 26-10-27 after the user supplied the visx Wordcloud sample (`scaleLog`, domain min to max); the same scratchpad checks were rerun with zero violations, plus an outlier case. `check:fix`, `types:check` and `build` passed again.
- Phone correction, 26-10-27 (user screenshot: three words in the cloud at about 300px): the retry only shrank the base maximum while the width limit was already lower, so retries repeated the same layout; the 280px canvas and 7px padding were too tight for 20 words; the width fit ignored padding, so a long label (rank 4) could not place and the rank rule cut the cloud off at three words. Fixed in `lib/wish-cloud-size.ts` and `wish-word-cloud.tsx`: the shrink now scales the final maximum, the width fit reserves padding, and height (280 to 420px) and padding (3 to 7px) follow the card width. A scratchpad script confirmed that at 280 to 1070px every label fits the canvas and each retry strictly shrinks sizes; estimated fill at 300px is about 50% (typical d3-cloud limit), so a few of the lowest-demand words can still become chips on very narrow phones. Not run in a browser; `check:fix`, `check`, `types:check`, `build` and `EconomicPerformanceTest` passed.
- Known limits: min-max scaling makes small gaps look large when counts are nearly equal (84 against 72 alone is 53px against 12px); long labels occupy more area than short ones at equal size, so Bars is the exact ranking.
- `composer ci:check` not run as a whole; its parts ran through the commit hooks and the checks above. No pull request opened.
- 26-10-28: The polishing `composer ci:check` and production build completed successfully before integration (164 tests, 1,013 assertions). Committed wish-cloud work as `2a08ba7` with hooks enabled.
- 26-10-28: Combined preview build passed. Focused seeder, quick sign-in and finance-review tests passed (30 tests, 173 assertions), explicitly on `cagayan_business_test`. Browser verification on port 8000: all three quick sign-in buttons are visible, Super Admin signs in, and Government Finances opens the populated review list. Other roles are covered automatically; manual review/sign-off writes were not performed.
- 26-10-28: Combined `composer ci:check` passed (201 tests, 1,211 assertions; frontend format/lint, TypeScript, Pint and PHPStan clean). Corrected the imported `FinanceReviewListQuery` callback to accept the query builder's `stdClass` rows. Saved browser evidence of the populated Government Finances list on port 8000. The saved standalone seeder stash remains available as a backup; its UUID fix is now adapted to the three-role seeder in this integration.
- 26-10-28: Completed. Both slices and the local finance preview integration travel in the combined `feat/business-analytics` pull request with the [Finance Ledger and Analytics Program](26-10-27-finance-ledger-analytics-program.md). The `feat/client-facing-polishing` and `feat/local-finance-polishing` branches were deleted after their commits were confirmed in that branch.
