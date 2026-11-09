---
title: "Dynamic Economic Map"
status: completed
created: "26-10-28"
branch: "feat/analytics-clarity"
base: "staging"
---

# Dynamic Economic Map

Approved by the user on "26-10-28". Follows the Finance Ledger and Analytics Program and integrates with the existing Analytics Clarity work without replacing it.

## Scope and Decisions

- Public Home and Capitol maps answer: what did the selected local governments report for this fiscal year?
- Latest is one absolute newest fiscal year with a signed-off revenue/expense pair for any town. Missing towns never fall back to older years. New calendar years alone do not advance it.
- Year selector lists verified fiscal years newest first. Measure selects Government Revenue, Government Expenses or Surplus or Deficit. Default shade is revenue.
- Finance SQL computes shades and surplus; maps and Analytics read stats_finance_year. Sum of Reporting Towns retains per-metric coverage and never mixes years.
- Only Latest displays census population context. Explicit fiscal years remove population values, sources and shading. The separate public census explorer is unchanged.
- Imports and completed review batches refresh finance results transactionally. Independent sign-off, decimal values, source evidence and restatement precedence remain enforced.
- URL-backed loaded-data selections use local Inertia history. Preserve layouts, dotted/solid styles and cached geography. Barangays never inherit municipal finances.
- No packages, schema migration, database reset, COA download automation, merge or deployment.

## Verification

- [x] Focused PostGIS tests cover latest-year discovery, missing years, shades, deficits/zero, coverage, privacy, restatement and publication rollback.
- [x] Public and Capitol data agree with Analytics; sources contain public evidence only.
- [x] Frontend selection/navigation tests, format/lint, TypeScript and production build pass.
- [x] PHP formatting/static analysis and required CI checks pass.
- [x] Browser checks cover exact-year gaps, controls, history, source details and layouts; record actual limits.
- [x] Canonical docs and roadmap updated; git diff --check passes.

## Changes

- "26-10-28": read-only source and database audit confirmed 158 parsed Executive Summaries, 374 verified revenue/expense records (29 towns in 2019 through 2024 and 13 in 2025), zero ledger/Analytics differences. Every collected report has both current-year lines. Implemented shared map contract/query and transactional finance rebuild; map UI and regression verification in progress.

- "26-10-28": found fixed 2025 caps in finance review and Analytics. Added shared record-derived year bounds so a newly imported annual report can be reviewed and its published results appear across maps and Analytics without changing a year constant. New-year publication regression exercises the actual HTTP review flow and public Analytics URL.

- "26-10-28": 78 focused finance/Analytics tests passed (707 assertions), followed by 21 focused import/map tests (92 assertions) including failed-import rollback and exclusion of census evidence. Three new frontend selection tests passed. HTTP new-year publication verifies 2026 review, both map payloads and public Analytics without changing configured 2025 defaults.
- "26-10-28": browser verification covered public and Capitol at 375px and 1440px, light/dark, dotted/solid public styles, keyboard measure selection, explicit-year population removal, Abulug 2025 unavailability versus 2024 values, aggregate coverage, source evidence, local history Back/Forward and barangay drill-down. DOM measurements found no horizontal overflow. Preview server logs recorded zero application requests during loaded year/measure/town/history/source selections; initial page, asset, optional development-log and unrelated session-activity requests were excluded. Viewport, light appearance and dotted style were restored.
- "26-10-28": browser review found React Compiler memoizing CSS map colors across appearances. The color-conversion cache now keys by appearance, and compiled map callbacks depend on it. Stable numeric bounds prevent finance filter changes from resetting map zoom. Live dark/light switching was rechecked against the legend.
- "26-10-28": production build passed. Initial full CI had 268/269 passing tests; its asset-scan failure was caused by a concurrent production build replacing files. Build and final CI are now run sequentially. Screenshots are local ignored artifacts under storage/app/map-review/.

## Final Verification "26-10-28"

- Final `composer ci:check` passed: 270 PHP tests, 1,827 assertions, nine frontend tests, frontend formatting/lint, TypeScript, Pint and PHPStan (zero errors).
- Final `npm run build` passed. Existing large MapLibre/chart chunk warnings remain; no package or bundling configuration changed.
- Design check found no prohibited colors, punctuation, gradients, missing-value zero substitutions or scope regressions in the changed map UI. Live browser console had no errors in the final preview.
- Public and Capitol screenshots were saved locally under `storage/app/map-review/`. Temporary preview server/tab were closed and viewport/style/appearance test changes restored. The user's existing development server was left running.
- Readme/design/metric/frontend/database guidance, roadmap and plan index updated. No schema migration, database reset, source download, commit, push, merge or deployment performed. Unfinished Analytics changes were preserved.
