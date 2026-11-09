---
title: "Super Admin Dashboard Foundation"
created: "26-10-24"
updated: "26-10-25"
status: completed
branch: feat/capitol-workspace-foundation
base: staging
predecessor: ../archive/26-10-23-public-landing-page.md
---

# Super Admin Dashboard Foundation

## Objective and Authorization

Finish the user's approved compact, map-first workspace foundation and outstanding UI/data reviews. On 26-10-24 the user explicitly selected this scope over building the deferred management and publication workflows. This record consolidates the existing implementation and later corrections; completion requires the checks below, not merely creation of the files.

## Scope and Acceptance

- [x] Dashboard: cached MapLibre geography for 29 Cagayan towns and 820 available barangays, population shading, explicit drill-down, working URL restoration and selectors, accurate selected-area figures and sources, islands in province bounds, resilient loading/network/WebGL/basemap behavior. Area selections stay local and preserve the map instance.
- [x] Workspace: fixed labeled desktop sidebar without collapse or accent bars, usable mobile menu, real breadcrumbs, compact stored-name greeting without the role beneath it, Philippine clock isolated from map rendering, optional private birthday greeting, existing theme tokens and semantic icons.
- [x] Community Wishes: separate Admin/Super Admin-only aggregate page, filters/pagination, no raw company/reason/identifiers, no wishes data in Staff or dashboard responses.
- [x] Profile and Logs: strict Unicode display-name and valid optional month/day editing; protected role/email; accessible errors; Super Admin-only filterable paginated append-only security/account logs.
- [x] Security: server-enforced three-role matrix, assignment policy boundaries for future editing, UUID private public identifiers, explicit trusted initial promotion, existing-account migration, idle/absolute expiry and warning, logout/deactivation/role-change invalidation, no-store/anti-framing/CSRF/throttling/secure production cookies, safe error states.
- [x] UI/data review: remove unnecessary/repeated helper copy across the affected screens, preserve meaningful error/validation/source information, use supported geography and metric labels, publish only verified fiscal figures and keep unsourced values unavailable. Do not add an economic-status score or claim comprehensive investment analysis.
- [x] Verification: affected/full Pest tests, PHP formatting/static checks, frontend format/lint/TypeScript, production and SSR builds, independent privacy review, desktop/phone/theme/keyboard and map-selection checks; document actual outcomes and material limits.
- [x] Maintainable handoff: update canonical docs and roadmap, keep runtime updates separate from workspace work, preserve source documents and unrelated changes, add and validate the plan-history skill/register.

## Deferred or Excluded

Business entry/editing, business-year records/metrics, review/publication actions, account-management screens, role/assignment-management UIs, editable categories, business maps/directories, permits, exports, forecasts, OAuth, and the [deferred financial/economic history plan](26-10-24-government-finances-economic-context.md). No new landing-page PR: [PR #15](https://github.com/drSabine/cagayan-central-businesses/pull/15) is already merged into staging.

## Decisions and Sources

- 26-10-24: The original foundation plan is narrowed by later user corrections: omit nonfunctional approval placeholders and redundant paragraphs, remove selected-sidebar accent bars and the role under the greeting, and keep geographic clicks client-side.
- 26-10-24: Tuguegarao display aliases Carig Sur, Carig Norte, and Pengue-Ruyu preserve source PSGC codes and population joins. See [boundary provenance and recheck](../../../data/boundaries/README.md).
- 26-10-24: Government revenue must be identified as government finances, not business sales/GDP. Source inspection has begun; only checked originals can replace the unverified imported fiscal data. Further bulk downloads remain deferred.
- Canonical rules: [design](../../engineering/design.md), [privacy](../../engineering/privacy-rules.md), [security/schema](../../engineering/database.md), [workspace decisions](../../../CLAUDE.md#capitol-workspace-decisions).

## Change History

| Date | Change | Reason / Reference |
| :- | :- | :- |
| 26-10-25 | Completed CI/build, independent privacy re-review and final UI checks; archived the foundation | 131 tests, 667 assertions; future features remain excluded |
| 26-10-24 | Consolidated existing work and accepted corrections into this record | User requested durable active/archive plans and a reusable skill |
| 26-10-24 | Confirmed foundation/review scope; management workflows stay deferred | Explicit user reply to scope clarification |
| 26-10-24 | Standardized plan metadata and filenames to yy-mm-dd | User corrected the earlier colon format |
| 26-10-24 | Removed page-title descriptions and completed direct-input session renewal without map navigation | User copy correction and idle-session contract |
| 26-10-24 | Verified four original 2024 COA archives; withheld unchecked figures and prevented misleading province finance totals | See data/towns/verified-finances-2024.md |
| 26-10-24 | Kept runtime commit e0980d0 on chore/security-runtime-updates and resumed workspace from staging e923a96 | Independent branches; safety stash and file backup retained |
| 26-10-24 | Backed up 76 changed files before branch separation | Ignored storage/app/branch-safety-2026-10-24; original source documents retained |

## Verification and Handoff

Completed 26-10-25 (work began 26-10-24; date rolled over during final browser review).

- `composer ci:check`: passed, 131 Pest tests / 667 assertions, PHPStan zero errors, Pint, frontend lint/format and TypeScript clean. The affected data/session/workspace subset also passed before the full run.
- `npm run build:ssr`: production and SSR builds passed. Typed routes include existing form variants. Development SSR renders the dashboard and supporting pages without browser console errors.
- Skill Creator `quick_validate.py .claude/skills/plan-history`: passed. Plan index/local links and `git diff --check` passed before handoff. Skill reads matching active/archived work and preserves exclusions; CLAUDE and feature-builder/workflow require the register for future planned changes.
- Independent privacy review: all role/data boundaries clean. Review identified that JSON expiry redirects could consume the Inertia history-clear flag. Fixed with a no-store 401 JSON response, added the regression test and re-review confirmed resolution. Activity renewal is direct-input-only, throttled from server config, preserves the absolute deadline and cannot revive an expired session.
- Browser review: desktop light/dark and 375px phone layout; no page overflow; fixed desktop sidebar, mobile menu, no selected accent bar or role under greeting; no page-title descriptions. Town keyboard selection, explicit barangay drill-down, Carig Norte selection (PSGC 0201529050, population 3,266), breadcrumbs, single map click, deep-link refresh and source links work. Profile invalid-April-31 rejection preserves the display name and entered values with linked summary and inline feedback. Wishes/logs are separate filterable screens. Clock is HH:mm:ss PHT with aria-live off; its date/greeting rolled over correctly at Philippine midnight.
- Map reliability review: source/cache/lazy-loading paths reviewed; normal local selections keep the same rendered map, with only one canvas after deep-link restoration. Province islands visible in initial bounds; actual WebGL rendering and attribution verified. Plain-style fallback, renderer/context-loss boundary, missing figures and retry/data-selector paths reviewed in code and earlier spot checks; no new automated browser fault-injection harness was added. Reduced-motion safety uses zero-duration map fit and existing motion tokens, reviewed in code.
- Geography/population evidence: original PSA workbook reconciliation found no mismatches for 29 towns / 820 barangays; total 1,284,676. Source-coordinate/topology checks and their limits are in the boundary README. Local naming aliases preserve codes/joins; these checks are not cadastral certification.
- Fiscal evidence: four exact COA 2024 pairs replace rounded secondary figures, with cells, source archives, checksums and qualified-opinion caveats in data/towns/verified-finances-2024.md. The other 25 towns stay unavailable pending originals. No province-wide government finance or economic-performance score is invented.
- Branches: runtime-only commit e0980d0 remains on chore/security-runtime-updates. Foundation is independently based on staging e923a96. Original file backup and safety stash remain recoverable. No foundation merge or publication was performed; landing PR #15 was already merged.

Future work is limited to the exclusions above; see the separate deferred financial/economic plan. This archive records the completed foundation, not completion of the larger business-intelligence product.
