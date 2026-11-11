---
title: "Capitol Dashboard and Right Panel Refresh"
created: "26-11-10"
updated: "26-11-10"
status: in-progress
branch: chore/maintainability-refresh
predecessor: ../archive/26-10-24-super-admin-dashboard-foundation.md
---

# Capitol Dashboard and Right Panel Refresh

The Dashboard answers what changed in the selected area and which work needs review. Prioritize desktop by pairing the map with selected-area figures and finance history, then ranking with the authorized review queue. Keep the mobile tiles in reading order.

- [x] Restore the compact welcome with display name, Philippine date and clock. Align the desktop logo and breadcrumb bars.
- [x] Standardize the Capitol filter panels, shorten permit labels and bound category chips. Keep the Businesses review tabs above the table.
- [x] Use a short appearance transition on shell and panel colors and honor reduced motion.
- [x] Verify both themes, desktop and phone layouts, review access, loaded selection behavior, server-backed filters, the production build and PostGIS CI.
- [ ] Capture a browser request count for loaded map and year selection. Verify all four permit states and a 60-character category fixture visually. The browser checks covered Valid and Expired and confirmed the chip's 12 rem width and wrap styles.

## Changes

- 26-11-10. After reviewing the desktop layout, the user removed the visible Economic Map heading and asked for Waiting for Review to use the ranking's eight-row pagination. Each table pages its loaded rows independently. The shared review table in Data Entry keeps its existing behavior.
- 26-11-10. Documented the [Capitol Dashboard pattern](../../engineering/design.md#capitol-dashboard) in the design guide. Group figures that readers compare, let the map fill the height of its supporting stack, and keep role-gated work in its own tile.
- 26-11-10. Added a width-aware Municipality and Year row and a shared native scrollbar style. Extended the income table with year-over-year rank movement and consecutive first-place streaks using loaded verified figures. See the [rank display rules](../../engineering/metrics.md#m26-year-over-year-change).

- 26-11-10. Moved rank movement into the Rank cell and put the comparison detail in a tooltip. Increased the review queue to ten rows per page and reused the category table's pagination logic for both Dashboard tables.
- 26-11-10. Removed the first-place streak label and calculation. The Rank cell uses a semibold rank number with a smaller movement indicator.
- 26-11-10. Reduced the visual weight of filter rows, reused the expandable search control for Businesses, and separated the two Capitol Analytics classification controls. Community Wishes pairs its existing table with a compact word cloud of the top ten requested business types under the same filters.
- 26-11-10. Moved the Community Wishes word cloud below the results and pagination, centered it within the results column, and restored the standard filter rail width.

## Verification

- 26-11-10. The original refresh passed frontend checks, the production build, and PostGIS CI with 380 passing PHP tests. Browser checks covered 1280 and 1440 px with and without the review queue, 375 px stacking, both themes, clock and header alignment, and the server-backed Businesses status filter. The browser verified loaded map selection, year and Back navigation. The local navigation path was inspected in source. The browser did not expose a request count.
- 26-11-10. Confirmed the Economic Map heading is absent. The demo review queue showed eight rows on page one and four on page two. Keyboard Next and pointer Previous worked independently of the ranking. The footer fit the phone viewport.
- 26-11-10. Municipality and Year shared a row at 1280 and 1440 px and stacked at 375 px. The table scrollbar used the thin native style and changed colors with the theme. The verified income ranking showed Tuguegarao City first for seven consecutive years through 2025 and six through 2024. Browser Back restored 2025. The ranking regression covers movement, ties, unchanged ranks, first appearances, hidden values and gaps.
- 26-11-10. The final production build and `composer ci:check` passed after the ranking and scrollbar changes. CI included formatting, TypeScript, frontend tests, Pint, PHPStan and 380 passing PostGIS PHP tests.
- 26-11-10. The compact rank tooltip showed “Up 3 places from last fiscal year” on keyboard focus. Waiting for Review showed ten rows on page one and two on page two while the ranking stayed on page one.
- 26-11-10. The local browser rendered the Wishes cloud below the table beside a 16 rem filter rail. The Businesses search icon opened the input, and Capitol Analytics showed separate Business Category and Industry controls. The Wishes feature tests cover the municipality aggregate across pages, exclusion of private text, and the Staff access restriction.
- 26-11-10. The production build and full CI passed after the Wishes and Population changes, including 382 PostGIS tests.
