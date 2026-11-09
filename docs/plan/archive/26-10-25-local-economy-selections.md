---
title: "Local Economy Selections"
created: "26-10-25"
updated: "26-10-25"
status: completed
branch: feat/cagayan-economic-performance
base: staging
predecessor: ../archive/26-10-25-cagayan-economy-polish.md
---

# Local Economy Selections

## Objective

Show the selected town's finished finance history and curated wishes immediately without repeating backend queries for already-loaded data. The user reported recurring requests on every filter/map selection and requested a fix and durable documentation. Keep the approved layout unchanged. Apply plan-history, ui-builder, Inertia and testing guidance; final verification begins only after code and documentation are ready.

## Acceptance

- [x] Initial payload contains full public finance history, server period boundaries and both curated wish modes, regardless of initial selection; other towns stay unavailable.
- [x] Town dropdown/map, period, wish kind and view switches produce zero application requests, preserve independent URL state and restore correctly with Back/Forward and refresh.
- [x] Remove obsolete selected-only payload, request/loading/retry code; preserve lazy visual error/loading states and financial/privacy rules.
- [x] Audit existing Home/Capitol map selection and cache paths; document the shared no-refetch boundary in CLAUDE, engineering docs and UI skills.
- [x] Finish implementation/documentation before automated checks. Pass affected PostGIS tests, lint, types, production/SSR build and browser request-count verification.

## Decisions and Scope

- 26-10-25: The original fix only made icon view changes local. Period, kind and town still used `router.get`; each request reran Form Request exists validation and the Query. This successor replaces that decision, preserving both archived plans.
- 26-10-25: One bounded public catalog avoids duplicate selected-data payloads. Annual deltas, counts, sorting and provenance remain backend-owned. React selects existing rows only. Empty catalogs/town entries mean unavailable; no private or raw wish records are loaded.
- 26-10-25: Home already uses preloaded atlas/local history. Capitol uses workspace-map-navigation and cached census geometry with failed-promise eviction. Preserve those working paths; no unrelated redesign or background refresh changes.
- Future unbounded verified datasets require explicit missing-data loading, identity-based cache/deduplication and the same authorization boundaries. No global backend response cache or dependency is needed for this small dataset.
- Deferred: source collection, persistent history, new metrics, live wish classification and forecasts. No commit, push or deployment requested.

## Verification

- Final verification began after feature code, contract tests and implementation documentation were complete. TypeScript first caught a leftover required `disabled` prop after removal of selection loading; removed that obsolete switcher prop and reran CI.
- `composer ci:check` passed: **151 tests, 948 assertions**, frontend format/lint, TypeScript, Pint and PHPStan (zero errors), on PostGIS. Coverage now verifies the complete catalog regardless of initial town/mode/period, full-history precision and changes, both curated wish modes, unavailable isolation, invalid deep links and exclusion of private sentinels.
- `npm run build:ssr` passed client production and SSR builds. Existing large-map chunk and Inertia SSR sourcemap warnings remain non-blocking. No dependencies added.
- Browser verification used a temporary Laravel server on port 8001 with its access log read between steps. Initial navigation produced one `/economic-performance` request. Period change, both view changes, wish-kind change, map selection to Abulug, keyboard return to Tuguegarao, repeated selected-town click, dropdown selection to Alcala, Back and Forward produced **zero subsequent server requests**. The access-log output for the entire interaction interval was empty.
- Refreshing the Alcala URL correctly made one new page request and preserved unavailable state plus independent controls. Returning to Tuguegarao from that freshly loaded unavailable town and changing to ranked bars again produced zero server requests. Financial table showed only 2023–2025 with exact supplied amounts and the correct supplied 2022 comparison base. Wishes modes retain their own complete aggregates.
- Source review confirmed Home uses local history/preloaded atlas and Capitol uses local geography history plus the existing deduplicated boundary cache with failure eviction. Those paths were unchanged. No authenticated browser was used for Capitol in this correction.
- ui-builder/design-check review: approved layout, tokens, source labels, map accessibility, public privacy boundary and lazy visualization states retained. Selection-specific loading/retry state and selected-only payload fields removed. Reduced-motion/layout styles unchanged. Existing predecessor phone/dark review remains historical evidence; this behavior correction was verified in desktop browser, without claiming another full phone/theme audit.
- Durable rules updated in CLAUDE.md, frontend.md, architecture.md, decisions.md and repository ui-builder/design-check skills. A URL change alone never authorizes a fetch. Future large datasets must explicitly scope/cache missing-data requests rather than copying blanket server visits.
- No commit, push or deployment. Original archived plans and deferred finance-source collection preserved. `git diff --check` passed.
