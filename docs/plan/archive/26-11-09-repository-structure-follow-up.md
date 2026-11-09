---
title: "Repository Structure Follow-Up"
created: "26-11-09"
updated: "26-11-10"
status: completed
branch: "chore/maintainability-refresh"
base: f55a81b3f95b4373fdfd691df7207acf60a2feae
predecessor: ../archive/26-11-09-maintainability-refresh.md
---

# Repository Structure Follow-Up

## Objective

Continue the [Maintainability Refresh](../archive/26-11-09-maintainability-refresh.md) in the same checkout and branch. Simplify verified call paths and make the developer and agent entry points agree.

## Scope and Acceptance

- [x] Codex and Claude share one project guide. Preserve the Boost-generated block and update developer entry points.
- [x] Keep Population route and form coordination in the page. Colocate its table and derive review eligibility once.
- [x] Preserve keyboard focus on the Business Categories sort headers through local sorting.
- [x] Use one recent-upload lookup in the full finance report and optional polling prop. Keep town/year authorization, response shape, parser messages and timestamps.
- [x] Verify the changed paths, run frontend formatting and lint, TypeScript, production build and full PostGIS CI, and record the results.

## Deferred or Excluded

The boundary loader, published observation mapping and assigned-town filters have separate behavior or permissions to verify before consolidation. No new packages, migrations, data definitions, layouts or deployment changes are part of this pass.

## Decisions and Sources

- "26-11-09". Keep shared instructions directly discoverable in root `AGENTS.md` and import them from `CLAUDE.md`. The installed Laravel Boost writer only replaces its tagged block in `CLAUDE.md`. [Codex instruction discovery](https://learn.chatgpt.com/docs/agent-configuration/agents-md), [Claude Code imports](https://code.claude.com/docs/en/memory#import-additional-files).
- "26-11-09". Two read-only audits traced candidates to live Inertia routes and callers. The Population and finance work build on the active [Capitol Entry Redesign](../active/26-11-09-capitol-entry-redesign.md) and completed [Finance Filters and Business Activity](26-11-09-finance-filters-business-activity.md).

## Change History

| Date | Change | Reason / Reference |
| :- | :- | :- |
| "26-11-09" | Bounded the cleanup after tracing frontend and backend call sites | Keep page contracts and write paths stable |
| "26-11-09" | Moved project instructions into `AGENTS.md`, split the Population table, stabilized category headers, and shared finance upload reads | Preserve one owner for each rule and keep the live entry points intact |
| "26-11-10" | Finished browser verification and archived the pass | Record phone and desktop results and the full CI count |

## Verification and Handoff

- Frontend formatting and lint, TypeScript, production build, Pint and PHPStan passed. The build reported the existing large map chunk warning.
- The targeted Capitol tests passed with 47 tests. Full `composer ci:check` passed with 30 frontend tests and 378 PostGIS tests (2,748 assertions). The finance regression verifies town and year scope, the newest-five order, and identical upload rows on the optional reload without returning the full report.
- In the built app, consecutive Enter presses kept focus on the Category header while changing sort direction. Back and Forward restored the sort direction. The sort still uses the existing local Inertia history call. Browser resource counts were unavailable.
- The Population table and map selected the same barangay. Both rendered at 375 and 1280 px in light and dark themes without page overflow. Review permissions and writes were covered by the Capitol tests.
- The Boost block matched the pre-change bytes. The root guide is 23,200 bytes and the Claude import resolves to it. Verified the updated local links and ran `git diff --check`.
