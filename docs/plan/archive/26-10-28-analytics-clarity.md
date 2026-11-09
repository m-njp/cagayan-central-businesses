---
title: "Make Analytics Understandable"
status: superseded
created: "26-10-28"
branch: "feat/analytics-clarity"
base: "staging"
---

# Make Analytics Understandable

> Superseded on "26-10-28" by [Finance Ledger Table Redesign and No Simulated Data](26-10-28-finance-ledger-redesign.md). Periods, local history and metrics stayed; the layout, sources, Help and Compare were replaced. Its one open item, instrumented request counts, was measured in the successor (zero requests for view, year and Back/Forward). Kept as history.

Approved by the user on "26-10-28". Follow-up to [Finance Ledger and Analytics Program](../archive/26-10-27-finance-ledger-analytics-program.md).

The first screen answers: What did the selected government report for this year, and where did these figures come from?

## Scope and Decisions

- Shared public Economy and Capitol Analytics: Yearly Summary by default, optional Trends and previous-year comparison.
- Exact signed-off government amounts remain public. Business sales retain the existing privacy guard and simulation label.
- All Years and Last 2, 3, 5 Years end at each section latest shown observation. Preserve legacy recent boundary and gaps.
- Shared help content, repository learning guide, General Fund scope and observation source details. No metric redefinitions, packages, source collection or database rebuilds.
- Every selection uses already loaded catalog data and local Inertia history: expected zero application requests for selections, repeated selections and Back/Forward.
- New URL state: presentation, year, compare; period supports all/recent/2y/3y/5y. Explicit year persists across category changes.

## Acceptance and Verification

- [x] Annual values, source lineage, derived inputs and public exact government amounts verified by focused tests.
- [x] Periods, optional comparisons, notes, gaps and protected states verified.
- [x] Public and Capitol desktop/375px layout checks, light/dark appearance, keyboard navigation and click activation.
- [x] Instrumented application-request counts (closed in the successor plan) during browser selections. Code and Node tests verify local history; direct browser network measurement remains unverified.
- [x] Frontend format/lint, TypeScript and production build pass.
- [x] Affected PostGIS feature/privacy tests and PHP checks pass.
- [x] Documentation and roadmap updated; record actual evidence here.

## Changes

- "26-10-28": the user rejected the layout, the repeated sources and the Help. [Finance Ledger Table Redesign and No Simulated Data](26-10-28-finance-ledger-redesign.md) replaces Source Details, Year Details, Compare and the Help drawer, and measured the request counts left open below. Periods, local history, comparisons data and metrics are unchanged. Archive this record when that plan completes.

- "26-10-28": inspected code, source CSVs and source records. Repository is on a work branch from local staging. Remote refresh failed because the host could not resolve GitHub; no unrelated files changed.

- "26-10-28": Economic Map Cleanup, a user correction to the map panels, is recorded and archived in [Economic Map Cleanup](../archive/26-10-28-economic-map-cleanup.md).


## Verification Evidence "26-10-28"

Implementation is finished on the work branch. This record stays active only for the outstanding instrumented browser request-count check.

- `composer ci:check`: passed, including Pint, PHPStan with zero errors and all 263 PHP tests (1,751 assertions). The initial Node group had five passing tests; the final follow-up adds unavailable-category default-year coverage and all six pass via `composer test:analytics`.
- Final frontend follow-ups: `npm run check:fix`, `npm run types:check`, `npm run build` passed. The build retains the existing large-chunk advisory for the map bundle.
- Focused public/finance/privacy tests: 40 passed (584 assertions). No architecture test directory exists in this checkout; the full suite also passed.
- Browser on port 8000: public default summary matches the committed 2025 Tuguegarao amounts; exact COA report/page links and both supporting inputs appear for surplus. Help topic selection works with the keyboard and explains every General Fund line. Optional comparisons show green increases and red decreases without assigning a performance rating.
- Public Trends shows finance 2023 to 2025 and simulated business 2024 to 2026 for Last 3 Years. Capitol Last 2 Years shows General Fund 2024 to 2025 and business 2025 to 2026. Back/Forward restores period state. An explicitly selected 2024 survives changing categories. Missing Alcala workbook figures retain a meaningful 2025 reference and explain that the workbook is not loaded.
- Public and Capitol 375px checks found and fixed an implicit-grid minimum causing 14px horizontal overflow in Capitol; both measured document width and scroll width at 360px inside a 375px viewport with scrollbar. Desktop light/dark, source drawers, Help, chart keyboard tooltips and click activation checked. Viewport overrides reset after testing.
- A source/year shared by multiple summary rows displays its common note once. All individual notes remain in Source Details.
- The browser exposes viewport and console inspection, but no direct network recorder. A temporary loopback-only request-count proxy could not be reached from the browser; it was stopped and no proxy files/settings were left behind. Therefore no measured zero-request claim is made. Node tests assert local `router.push`, preserve other props, serialize comparisons correctly, retain explicit years and skip unchanged selections; source inspection finds no request call in these interactions.
- One development navigation logged the existing renderToString/Suspense client-render fallback. Fresh loads did not produce a new occurrence. Public console error log was empty.
- Design review: no added forbidden punctuation, raw palette classes, gradients or raw component type sizes; the type-data role and trend tokens live in app.css. Controls and missing/protected states are labeled; source links remain underlined. The overflow found in browser review is fixed.
- Privacy review P1 to P8 and P10/P13/P14 hold: unchanged guarded business datasets, exact public government figures, explicit DTOs, no pending amounts, reviewer identities or internal notes in evidence, simulation labels retained, no new protected logging. P9 and P11 add no record view or export; P12 wish handling is unchanged.
- No packages, metric definitions, SQL formulas, source datasets, accounts, production data, migrations, commits or pushes were added/changed. Existing `.codex/` remains untouched. The only login used the existing local Admin demo button.
