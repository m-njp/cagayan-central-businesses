---
title: "Home Focus View"
created: "26-11-09"
updated: "26-11-09"
status: in-progress
branch: feat/finance-report-redesign
base: staging
predecessor: 26-11-08-live-finance-parsing.md
---

# Home Focus View

## Objective

Reuse the finance upload blur on the public Home map: an expand icon opens a full-screen view over a Gaussian blur with a centered 2 by 2 layout and no visible grid lines. With a municipality chosen, the map shows that municipality alone.

## Scope and Acceptance

- [x] `components/ui/focus-dialog.tsx`: the one blur dialog (fade in slow, out quick, bare content, optional close icon), used by the parsing progress and the Home view.
- [x] Expand icon in the Home map controls (`ProvinceMap` `onExpand`, `icons.fullscreen`), only on the hero map.
- [x] Top left the map, top right the area's figures, bottom left the Income, Expenses and Net Income chart, bottom right the same figures as a table (latest three years).
- [x] A chosen municipality is drawn alone from its barangay file with its barangay boundaries and names, and no figure per barangay (`hooks/use-town-shape.ts`); the province map shows when none is chosen.
- [x] Previous and next chevrons step through the province and each municipality in a loop (same URL selection).
- [x] Same URL selection and cached data as the hero. Browser check on 26-11-08: opening and selecting a municipality inside the view made zero application requests, Escape closed it.
- [ ] Browser re-check of the municipality map with boundaries, the chevrons and the new fade (not run: owner asked for no browser screenshot checks).

## Deferred or Excluded

Other places for the overlay, ranked: the wish cloud and ranking, Economy and Capitol Analytics charts, the Capitol Dashboard map (prefer MapLibre's own fullscreen control). Not for tables, entry forms, Population, Data Entry, Businesses or the finance review and upload dialogs. Picking a municipality by clicking the map inside the view (use the chevrons, or close and choose on the hero). Mid-range phone blur performance is untested.

## Decisions and Sources

"26-11-09": expand icon rather than click on the map (a click selects a municipality, `design.md:50`); trend chart plus years table in the bottom cells; the view is a comparison view, not a "More Details" dialog. See `design.md`, "Focus Overlay".

## Change History

| Date | Change | Reason / Reference |
| :- | :- | :- |
| "26-11-08" | First version: 2 by 2 view with the province map | Owner request |
| "26-11-09" | Barangay boundaries and names returned to the municipality view (the owner wanted boundary information only, no per-barangay figure); previous and next chevrons; the `borderless` map option removed | Owner review |
| "26-11-09" | Reusable `FocusDialog` with fade in and out; municipality only map instead of the province map when one is chosen | Owner: better transition, reuse the blur, more focused view without barangay borders |

## Verification and Handoff

"26-11-09": `npm run check`, `types:check` and `build` pass; Capitol tests 128 passed. Earlier browser check as listed above. Existing issue noticed, not changed: the Source list repeats "COA Report p. 2" for a municipality (`FinanceMapFigures`).
