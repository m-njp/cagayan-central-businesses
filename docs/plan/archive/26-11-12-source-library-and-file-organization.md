---
title: "Source Library and File Organization"
created: "26-11-12"
updated: "26-11-12"
status: completed
branch: "chore/maintainability-refresh"
base: staging
predecessor: "../archive/26-11-12-coa-2025-source-completion.md"
---

# Source Library and File Organization

## Objective

On "26-11-12" the user requested putting the downloaded COA reports in the repository's appropriate folders, updating Sources, replacing the long page with a smarter bounded browser and clearing the collected COA files out of Downloads.

## Scope and Acceptance

- [x] Promote 190 newly collected PDFs into `data/towns/executive-summaries/<year>/<municipality>/`, retaining originals and provenance; the resulting source register covers all 348 municipality/year pairs for 2014 through 2025.
- [x] Reconcile Downloads against known source hashes and move only matching COA collection files into the repository, with per-file receipts and no unrelated-file changes.
- [x] Public Sources lists collected original reports independently from figure sign-off, while preserving the existing guard for finance figures and private uploads.
- [x] Use a searchable municipality browser, a year filter and a bounded report table with sticky headings; support phone layouts, keyboard and both appearances.
- [x] Loaded selections and Back/Forward use local Inertia history without application requests.
- [x] Individual file links and the complete ZIP use the same allowlisted source inventory and checksum verification.
- [x] Relevant tests, required frontend checks/build, full CI and design/privacy review pass.

## Decisions and Sources

Follow the [2025 collection](../archive/26-11-12-coa-2025-source-completion.md), [historical collection](../archive/26-11-11-coa-browser-collection.md) and [original Sources page](../archive/26-11-10-home-finance-sources.md). The user's latest request expands the document library to the collected originals; it does not approve any new financial figures. Only the registered official-source PDFs and already public verified-ledger attachments are downloadable. File integrity is separate from financial sign-off.

The primary visitor task is finding a municipality's report for a fiscal year. Keep all loaded metadata local, use a compact desktop municipality list and mobile select, and bound the report area instead of rendering all municipalities' histories vertically. Retain the existing secondary source attributions and complete-archive action.

## Deferred or Excluded

No finance extraction/import, sign-off, schema changes, new packages, business-data changes, publication/deployment, commits or pushes. Downloads cleanup is restricted to observed collection files whose SHA-256 matches a verified repository destination.

## Verification and Handoff

Completed on "26-11-12".

- Canonical source register: 348 unique municipality/year pairs, 29 each year from 2014 to 2025. All stored byte counts and SHA-256 values match. Promoted 174 historical and 16 remaining 2025 originals; original filenames and official URLs retained.
- Moved 190 collected PDFs, the excluded adjacent annual-audit ZIP and the older 162-report public ZIP out of Downloads. All 192 moves have local SHA-256 receipts in `storage/app/source-review/coa/browser-collection/relocation-receipt.json`. PDFs use the canonical source tree; ZIPs use ignored review `excluded/2019/` and `archives/` folders. Unrelated Downloads files remain untouched.
- Sources uses one municipality at a time, local search and year history, a bounded desktop list/table and a content-sized phone table with a height limit. Shared Select, Surface, Table and SourceLink retain the established visual system. Latest year comes first; the complete archive ignores view filters.
- New manifest-backed report route restricts files to registered official-source PDFs inside the canonical root, checks size and verifies SHA-256 before serving. Public catalog DTOs carry only source metadata. Existing figure citations, sign-off and private-upload guards remain intact.
- Frontend formatting/lint, TypeScript and production build passed after the final phone-layout change. Existing map chunk-size warnings remain. Three source-selection tests passed. Full `composer ci:check` passed, including PHPStan, Pint, frontend suites and 391 PostGIS/Pest tests with 2,928 assertions. The focused source suite passed 15 tests with 188 assertions.
- COA suite: 471 tests passed, including all 348 stored PDFs and regression checks for a temporary or persistent Windows manifest lock. Initial runs exposed intermittent atomic-replacement `PermissionError`s; a bounded six-attempt retry now preserves the prior manifest on persistent failure. Per-download journals remain durable.
- Built the real public ZIP and independently verified all 352 reports (348 PDFs and four verified workbooks). Browser download reached Download Started and Close returned focus to Download All. The older Downloads ZIP independently passed all 162 report hashes before relocation. Verification artifacts stay under ignored `storage/app/source-review/coa/`.
- Browser: desktop and 375px phone checked in light/dark appearances, without horizontal overflow. Municipality selection, multiword search, empty search, latest/oldest year, keyboard End/Enter, scrollable reports and sticky headings verified. Back/Forward restores municipality/year/search; repeated selections preserve the URL. A controlled search/selection/history run produced zero application requests in server logs. New content adds no animation; shared Select retains its reduced-motion rule. Theme and viewport restored after checks.
- Design Check: no blocking findings after shortening the phone archive action and removing forced empty phone height. Privacy review: P1, P8, P10, P11 and P14 hold for the changed source/catalog/archive paths; no protected data, raw models, private uploads or internal paths are serialized. P2-P7, P9, P12, P13 and P15 do not apply to this government-document-only change. Privacy and architecture suites passed within full CI. No finance figures were imported or signed off.

The files and interface are prepared locally; this task does not include committing, pushing or deployment. Continue any financial extraction/review separately from source publication.
