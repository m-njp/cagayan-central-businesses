---
title: "Maintainability Refresh"
created: "26-11-09"
updated: "26-11-09"
status: completed
branch: chore/maintainability-refresh
base: f55a81b3f95b4373fdfd691df7207acf60a2feae
predecessor: 26-10-27-single-source-of-truth-cleanup.md
---

# Maintainability Refresh

## Objective

Make the Home comparison view readable and quick to operate. Give page code, reusable controls, assets and project guidance clear owners and fewer copies.

## Scope and Acceptance

- [x] Fast-forward local staging from GitHub and branch from it in the same checkout. Safely remove the merged local parser-fix branch.
- [x] Continue the [Home Focus View](26-11-09-home-focus-view.md). Use one blur dialog for Home and parsing, theme-aware controls and opposite-edge navigation. Reuse compact search across the existing toolbars.
- [x] Move the Inertia entries and their private sections into page folders. Preserve named URLs, Data objects, roles and local selection behavior. Simplify the registry documents and wish forms.
- [x] Trace consumers before removing code and dependencies. Check React symbol references, Laravel relationship and registration paths, and published asset references. Preserve Kita assets and font licenses.
- [x] Add `composer test:frontend` to `ci:check`, reconcile project guidance and revise the repo-owned skills.
- [x] Record final file checks, design and privacy review, and browser verification after implementation and documentation edits.

## Deferred or Excluded

Production and shared branches, new dependencies, new metrics and changes to disclosure rules are outside this pass. Additional uses of the blur are proposals. Human finance sign-offs remain with the existing finance plans.

| Rank | Proposed script | Benefit and prerequisite | Effort |
| :- | :- | :- | :- |
| 1 | `composer check:quick` | Give contributors static feedback without PostGIS. Compose the existing frontend and PHP checks. Verify it runs without touching the database. | Small |
| 2 | `composer test:privacy` | Run privacy and architecture coverage directly on the configured PostGIS test database. Compare the tests selected with the current suite. | Small |
| 3 | `composer data:boundaries:check` | Detect invalid, duplicate or missing geographic identifiers and files before publish. Reuse the existing geometry checks and agree a PSGC inventory. | Medium |

| Rank | Proposed feature | Benefit and prerequisite | Effort |
| :- | :- | :- | :- |
| 1 | Expanded public wish visualization | Make more labels and ranks readable at full size. Reuse `FocusDialog` and the loaded aggregate snapshot. | Small |
| 2 | Expanded finance charts | Give trends more room on Economy and Capitol Analytics. Reuse the chart and table contracts and check narrow phones and reduced motion. | Medium |
| 3 | Wishes beside registered supply | Give authorized Capitol reviewers a comparison by type. Requires approved registry coverage, the wishes access gate, and a definition that preserves both periods and geographies. | Medium |
| 4 | Additional business checklist paths | Give more applicants verified steps. Requires official sources and a verification date from a team member for each requirement. | Medium |

## Decisions and Sources

"26-11-09": The owner selected local-only staging refresh, existing toolbar search, page folders with renamed internal Inertia components, a single frontend test entry point, and final-only verification. The shipped finance-map and simulated-data rules take precedence over stale agent text. The public directory keeps its town prerequisite. The parser keeps its existing status overlay.

## Change History

| Date | Change | Reason / Reference |
| :- | :- | :- |
| "26-11-09" | Work branch created and merged local parser branch removed | Verified both local and remote staging at `f55a81b` before branching |
| "26-11-09" | Theme-aware Home overlay, page folders, shared search, project skills and test alias | Owner-approved cleanup and redesign |
| "26-11-09" | Quiet map controls and selectors, actual fiscal year, coverage with the source, compact mobile Businesses filters, and top-edge phone navigation over a lighter blur | Owner reviewed both screens. [Apple pop-up buttons](https://developer.apple.com/design/human-interface-guidelines/pop-up-buttons) and [materials](https://developer.apple.com/design/human-interface-guidelines/materials) informed the revision. |

## Verification and Handoff

"26-11-09": `composer ci:check` passed with 29 frontend tests and 377 PostGIS tests (2,724 assertions), plus lint, TypeScript, Pint and PHPStan. The production build passed and reported the large map chunk warning. The reduced-motion override was confirmed in the built stylesheet.

The browser pass covered Home at 375 and 1280 px in both themes, actual fiscal year and Latest selection, borderless map controls, 44 px edge navigation, cached municipality boundaries, Escape and focus return, and Back/Forward. The public Businesses toolbar grouped its filters at phone width and its query stayed active after blur, cleared, and collapsed with focus return. Capitol sign-in, dashboard, Business Categories search, finance navigation and an existing parser run rendered. The parser reopened to Report Ready with its activity list. No new upload was needed. The server log showed zero application requests for loaded Home selections and public name filtering.

Design review found and corrected the initial map tooltip intercepting Escape. Privacy review covered P1 to P15. Changed Inertia names resolve to all 20 entries, and the page moves preserve the explicit Data props, authorization, registry view logs, and simulated-data label. The frontend source count fell by 551 lines. Six unused direct npm dependencies and two unused inverse model relationships were removed. Official artwork stayed at its existing URLs.
