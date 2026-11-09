---
title: Single Source of Truth Cleanup
status: completed
created: "26-10-27"
updated: "26-10-27"
branch: chore/single-source-of-truth-cleanup
base: staging
---

# Single Source of Truth Cleanup

## Context

Consolidate redundant frontend and backend code across the public landing page, suggestions flow, economic performance view, and Capitol workspace while strictly following the repository's simplicity rules (`CLAUDE.md`, `docs/engineering/frontend.md`, `docs/engineering/architecture.md`, `.claude/rules/frontend.md`, `.claude/rules/backend.md`):
- Extract shared code on the third copy, not the first.
- Do not over-split: no one-line wrapper components, no pass-through props, no `index.ts` re-exports.
- Preserve module paths referenced by engineering docs (`resources/js/lib/workspace-map-navigation.ts`).
- Ensure the refactoring results in a net line reduction rather than abstraction bloat.
- Clean up obsolete and merged Git branches and stale worktrees.

## Agreed Scope

- [x] Prune stale Git worktree references and delete merged local and remote branches while preserving active/unmerged branch heads and untracked local data collection files.
- [x] Consolidate barangay census map loading and caching on both frontend (`resources/js/lib/map-data.ts`, `resources/js/components/barangay-atlas.tsx`) and backend (`app/Queries/WorkspaceMapQuery.php`, `app/Http/Requests/CapitolDashboardRequest.php`).
- [x] Centralize locale (`LOCALE`), timezone (`TIME_ZONE`), missing/hidden figure copy (`NOT_AVAILABLE`, `HIDDEN_FIGURE`), figure formatting (`formatFigure`), source agency naming (`sourceName`), and town figure row/year/source derivation (`townFigures`) in `resources/js/lib/format.ts`, and reuse across `resources/js/lib/performance-format.ts`, `resources/js/lib/workspace-format.ts`, `resources/js/components/town-atlas.tsx`, `resources/js/components/map/dashboard-atlas.tsx`, `resources/js/components/barangay-atlas.tsx`, `resources/js/pages/public/economic-performance.tsx`, and `resources/js/pages/public/wishes/index.tsx`.
- [x] Add shared URL path (`pagePath`), query-param parsing (`pageSearchParams`), and local query replacement (`replaceLocalQuery`) helpers in `resources/js/lib/navigation.ts` and reuse across `resources/js/pages/public/home.tsx`, `resources/js/components/town-atlas.tsx`, `resources/js/components/barangay-atlas.tsx`, `resources/js/components/map/dashboard-atlas.tsx`, `resources/js/layouts/capitol-layout.tsx`, and `resources/js/pages/public/wishes/index.tsx`.
- [x] Share client-only mount detection (`useInBrowser`) and visual error boundary (`VisualErrorBoundary`) in `resources/js/components/lazy-province-map.tsx` and reuse across `resources/js/components/charts/performance-visual.tsx` and `resources/js/components/map/dashboard-atlas.tsx`.
- [x] Deduplicate backend `TownFigure::fromNullable`, derive the `TownAtlasQuery` SQL `where measure in (...)` clause from `self::MEASURES`, and reference `config('cagayan.performance.demo_town')` in `BarangayAtlasQuery`.
- [x] Tighten and prune any extraction that increased line count or violated the three-copy rule so the final diff achieves a net reduction in lines of code.

## Out of Scope

- Changing Inertia prop contracts, route names, database schema, or user-visible privacy/disclosure behavior.
- Unrelated uncommitted COA source collection files under `data/towns/annual-audit-reports/` and `data/towns/executive-summaries/`, and the deferred plan `docs/plan/archive/26-10-24-government-finances-economic-context.md`.

## Decisions and Changes

### 26-10-27 — Git Worktree and Branch Cleanup

- Ran `git worktree prune -v` to remove the stale `cagayan-pr5` worktree entry.
- Fast-forwarded local `main` (`40ddd70`) and `staging` (`caa5881`) to `origin` and created `chore/single-source-of-truth-cleanup` from `65eb627` (which includes merged PR #16 Capitol Workspace Foundation and merged PR #17 Cagayan Economic Performance).
- Deleted 8 merged local branches: `chore/frontend-stack`, `chore/home-follow-ups`, `chore/project-setup`, `chore/remove-starter-kit`, `data/sanchez-mira-name`, `feat/home-halftone-map`, `feat/capitol-workspace-foundation`, `feat/cagayan-economic-performance`.
- Deleted 9 merged remote branches on `origin`: `chore/frontend-stack`, `chore/home-follow-ups`, `chore/map-libraries`, `chore/remove-starter-kit`, `data/sanchez-mira-name`, `docs/clean-urls-and-redirects`, `feat/F7-layouts`, `feat/home-halftone-map`, `feat/landing-login-wishes`.
- Preserved local `chore/security-runtime-updates` (`e0980d0`, unmerged) and remote `origin/feat/capitol-workspace-foundation` (`bc849cd`) + `origin/feat/cagayan-economic-performance` (`65eb627`) until `origin/staging` fast-forwards to `bc849cd`.

### 26-10-27 — First Pass: Initial SSOT Extraction

- Extracted shared frontend helpers in `resources/js/lib/format.ts`, `resources/js/lib/map-data.ts`, `resources/js/lib/navigation.ts`, and `resources/js/components/lazy-province-map.tsx`.
- Extracted backend helpers in `app/Data/TownFigure.php`, `app/Data/SelectOption.php`, `app/Queries/WorkspaceMapQuery.php`, `app/Queries/TownAtlasQuery.php`, `app/Queries/BarangayAtlasQuery.php`, `app/Http/Requests/CapitolDashboardRequest.php`, `app/Http/Requests/StoreWishRequest.php`, and `app/Http/Controllers/WishController.php`.

### 26-10-27 — Second Pass (Additionals): Line-Count Tightening and Three-Copy Rule Enforcement

- Audited why the initial pass showed a net line increase (`+434 / -365`) after Prettier formatting and eliminated abstraction overhead:
  1. **Single census loader & cache in `resources/js/lib/map-data.ts`:** Removed the redundant `LoadedTownCensus` / `loadTownCensus` / `getCachedTownCensus` wrapper layer and attached `raw: string` directly onto `CensusCollection` in `loadTownBoundaries(code)`. Inlined `censusBarangayProfiles` back into its sole frontend caller (`resources/js/lib/workspace-map-navigation.ts`).
  2. **Combined `townFigures` helper in `resources/js/lib/format.ts`:** Replaced the separate `FigureRowItem`, `townFigureRows`, `figureYears`, and `figureSources` exports with a single `townFigures(population, finances)` function that returns `{ rows, years, sources }` with pre-formatted `value` strings for `TownAtlas` and `DashboardAtlas`.
  3. **Removed 2-copy / non-shrinking backend extractions:** Reverted `WishController::redirectToIndex`, `StoreWishRequest::NO_LINKS_REGEX`, and `SelectOption::towns` / `SelectOption::businessTypes` (keeping Eloquent queries out of `app/Data/` and honoring `CLAUDE.md`'s three-copy rule).
  4. **Fixed nullable TypeScript narrowing in `resources/js/components/barangay-atlas.tsx`:** Updated `loaded && loaded.code === townCode ? loaded.census : null` so `tsc --noEmit` passes cleanly when `townCode` is `undefined`.
- Final code diff across `app/` and `resources/js/`: **19 files changed, 299 insertions(+), 332 deletions(-) (-33 lines net)**.

## Primary References

- [CLAUDE.md](../../../CLAUDE.md)
- [docs/engineering/frontend.md](../../engineering/frontend.md)
- [docs/engineering/architecture.md](../../engineering/architecture.md)
- [docs/engineering/design.md](../../engineering/design.md)
- [docs/engineering/metrics.md](../../engineering/metrics.md)

## Verification

- `vendor/bin/pint --dirty --format agent` — passed
- `vendor/bin/phpstan analyse --memory-limit=2G` — passed (0 errors)
- `php artisan test --compact` — passed (151 tests, 950 assertions)
- `npm run check` — passed (63 files formatted, 57 files linted with 0 warnings/errors)
- `npm run types:check` — passed (`tsc --noEmit` 0 errors)
- `npm run build` — passed
- `git diff --check` — passed
