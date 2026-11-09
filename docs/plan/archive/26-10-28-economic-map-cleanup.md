---
title: "Economic Map Cleanup"
created: "26-10-28"
updated: "26-10-28"
status: completed
branch: "feat/analytics-clarity"
base: staging
predecessor: "archive/26-10-28-dynamic-economic-map.md"
---

# Economic Map Cleanup

## Objective

The user corrected the Home and Capitol economic maps on "26-10-28": remove the Measure selector because the right-hand panel already shows every measure, format Source Details properly, remove redundancy and repeated text, and mark Surplus or Deficit with a triangle. It also follows the Session 1 correction to stop duplicating sources and to link the PDF directly. Scope is the two map panels only. The Yearly Summary and Help redesign is separate.

## Scope and Acceptance

- [x] No Measure selector or `measure` URL parameter. The map always shades by Government Revenue, and the Capitol legend names it. An old `?measure=` is ignored.
- [x] The map panel ends with one Source block: one underlined link per distinct COA report page, opened at the cited page (`#page=N`), plus the Latest population link. No Source Details drawer, definitions, fiscal-year or sign-off text in the map.
- [x] Province total (Sum of Reporting Towns) shows one plain "COA Annual Audit Reports" line, not a list of per-town links.
- [x] Coverage prints once when all metrics share it. An empty year shows one status line. Barangay level shows only its message and population.
- [x] Surplus or Deficit shows a green up triangle for a surplus and a red down triangle for a deficit, with an accessible label. Zero or missing shows none.
- [x] `design.md`, `frontend.md`, `CLAUDE.md`, `.claude/rules/frontend.md`, and the `design-check` and `ui-builder` skills updated.

## Deferred or Excluded

- Shared Source Details drawer on the Analytics page, the Yearly Summary and the Help drawer. Session 1 owns that redesign.
- Dropping the unused expenses and surplus shades from the `FinanceMapQuery` payload. The backend and its tests are unchanged.
- A per-town source list or popover for the province total. Not requested.
- Not browser-verified: the Capitol dashboard map, 375px width, dark mode and application request counts for Year changes.

## Decisions and Sources

- "26-10-28", map shading: Government Revenue, chosen by the user over Surplus or Deficit and Expenses.
- "26-10-28", triangle: surplus green up, deficit red down, chosen by the user over the literal wording "if deficit add a triangle up". It marks the sign of the result, not a performance rating, so M26 is unaffected.
- "26-10-28", province sources: the first version deduplicated by report page and still printed 13 identical "COA Report p. N" links for the province, because only a single town had been checked. The aggregate now shows one line. The skills require checking aggregate, single, empty and drill-down states.
- Canonical rules: [design.md, "Economic Map Cleanup"](../../engineering/design.md) and [frontend.md, "Dynamic Economic Maps"](../../engineering/frontend.md).

## Change History

| Date | Change | Reason / Reference |
| :- | :- | :- |
| "26-10-28" | Removed Measure, replaced the Source Details drawer with a deduplicated, page-anchored Source list, removed repeated coverage and Not available rows, added the Surplus or Deficit triangle | User correction. Files: `finance-map-panel.tsx`, `finance-map.ts`, `performance-format.ts` (`sourceHref`, `financeMapSources`), `dashboard-atlas.tsx`, `town-atlas.tsx`, `tests/Frontend/finance-map.test.mjs` |
| "26-10-28" | Province total shows one plain source line | User screenshot showed 13 identical links |
| "26-10-28" | Documented the rules and added the all-states check to the skills and frontend rules | User asked for documentation and the skill, after a repeated correction |

## Verification and Handoff

- `npm run check:fix`, `npm run check`, `npm run types:check` and `npm run build` pass. `composer test:maps` passes (3 tests) and `FinanceMapTest` passes (4 tests, 22 assertions).
- Browser on Home: no Measure control. Santo Niño 2025 shows a green up triangle with one COA link at `#page=1` and the PSA link. Alcala 2025 shows a red down triangle. The province total shows one COA line and the PSA link.
- Not run: `composer ci:check` and the full PHP suite. The back end is unchanged. Limitations are listed under Deferred or Excluded.
- Committed on `feat/analytics-clarity` on "26-10-28" together with the rest of that day's work, as one commit (see `git log`). Not pushed, and no pull request is open yet.
