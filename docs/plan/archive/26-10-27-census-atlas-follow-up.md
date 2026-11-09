---
title: "Census Atlas Follow-Up"
created: "26-10-27"
updated: "26-10-27"
status: completed
branch: chore/census-atlas-follow-up
base: staging
predecessor: ../archive/26-10-27-single-source-of-truth-cleanup.md
---

# Census Atlas Follow-Up

## Objective

Fix the medium defects found by the independent audit of the Single Source of Truth Cleanup (PR #19), authorized by the user's request "fix the medium defects on a new chore branch commit and pr".

## Scope and Acceptance

- [x] Revisiting a barangay town already loaded in this session renders from the cache without a loading skeleton frame (`resources/js/lib/map-data.ts`, `resources/js/components/barangay-atlas.tsx`).
- [x] A barangay tooltip without a population reads "Not available", never `0 people`.
- [x] The predecessor's reuse claims are corrected below instead of rewriting the archived plan.

## Deferred or Excluded

Low findings from the same audit stay open: helpers with fewer than three consumers, `useInBrowser` and `VisualErrorBoundary` living in `lazy-province-map.tsx`, the agency names now shown in the Capitol dashboard sources, `CapitolDashboardRequest` reading through `WorkspaceMapQuery::census()`, and the hard-coded `0201529000`, `0201500000` and `2024` literals. No branch or stash deletion is part of this plan.

## Decisions and Sources

- 26-10-27: `loadTownBoundaries` keeps a resolved-value map beside its promise cache, and `loadedTownBoundaries(code)` reads it synchronously. The component's state only triggers the re-render, so the cache stays the single source of loaded data. This restores the synchronous fallback the old `censusCache` had.
- 26-10-27: Tooltip text for a missing population uses `NOT_AVAILABLE`, following the "Not available is never shown as 0" rule in `CLAUDE.md`.
- 26-10-27: The predecessor's extracted helpers stay in place. Correcting the record is cheaper than reinlining about ten duplicated lines, and the consolidation already merged.

## Change History

| Date | Change | Reason / Reference |
| :- | :- | :- |
| 26-10-27 | Correction to the predecessor: `replaceLocalQuery` has two callers (`town-atlas.tsx`, `barangay-atlas.tsx`), not the home, dashboard atlas, Capitol layout and wishes files it listed. Those files use `pageSearchParams` or `pagePath`. | Audit of PR #19 |
| 26-10-27 | Correction to the predecessor: `townFigures` has two callers (`town-atlas.tsx`, `dashboard-atlas.tsx`). `performance-format.ts`, `workspace-format.ts` and the wishes page use only the shared constants. | Audit of PR #19 |
| 26-10-27 | Correction to the predecessor: `VisualErrorBoundary` has two callers, each with its own fallback. The predecessor reverted other two-copy extractions, so these three helpers are accepted exceptions to the three-copy rule. | Audit of PR #19 |
| 26-10-27 | Correction to the predecessor: it recorded local `chore/security-runtime-updates` (`e0980d0`) as preserved and unmerged. That commit was merged by PR #18 and the local branch no longer exists. | Audit of PR #19 |
| 26-10-27 | Added `loadedTownBoundaries`, removed the component's own loaded-census state, and replaced the `?? 0` tooltip fallback. | Audit findings 1 and 2 |

## Verification and Handoff

- `npm run check`: passed (63 files formatted, 57 files with no warnings or lint errors).
- `npm run types:check`: passed with Wayfinder routes generated (`php artisan wayfinder:generate --with-form`).
- `npm run build`: passed.
- No PHP changed. The git hooks run the full `composer ci:check` on push.
- Not verified in a browser: the removal of the skeleton frame on revisit and the "Not available" tooltip have no automated test, because the project has no front end test runner.
- Predecessor: [Single Source of Truth Cleanup](26-10-27-single-source-of-truth-cleanup.md), merged in PR #19.
