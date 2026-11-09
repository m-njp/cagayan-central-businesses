---
title: "Capitol Entry Redesign"
created: "26-11-09"
updated: "26-11-09"
status: in-progress
branch: feat/finance-report-redesign
base: staging
predecessor: ../archive/26-11-03-data-entry.md
---

# Capitol Entry Redesign

## Objective

The owner called Population and Data Entry poorly designed, too long and too padded, with unequal fonts and hand-rolled pieces. Rebuild them and Government Finances on shared pieces with one source of truth.

## Scope and Acceptance

- [x] One tab row (`components/capitol/tab-row.tsx`, the Businesses look, no underline) for Government Finances years, Businesses review states and Population census years.
- [x] Government Finances: compact Pending, Verified and Returned columns (`w-20`), chevron `w-8`.
- [x] Data Entry and the Dashboard share one Waiting for Review table (`review-queue-table.tsx`); Last Change and Since read `MM-DD-YY HH:mm` in Philippine time (`formatEntryTimestamp`; the server sends ISO instants).
- [x] One status cell (`FigureStatus`) and one entry cell (`EntryCell`, `h-7`, `text-sm`) shared by Population and the finance grid; `WorkspaceFilters` builds on `Surface`.
- [x] Population: table on the left, barangay map on the right in the shared solid style like the Dashboard, shaded by the barangay file (choosing either selects the other), sticky header with an inner scroll, one font size, Not available instead of 0 for a missing count, loading state, empty state, compact reviewer actions in the card header.
- [x] The word Municipality in every Capitol label (replacing Town and Town or City).
- [ ] Browser passes: light and dark, 375px, Population, Data Entry, Government Finances (not run: owner asked for no browser screenshot checks).

## Deferred or Excluded

No new packages (the row checkbox stays native). Public pages keep their wording ("Home town selector", wish form) until the owner decides whether Municipality applies there. Live shading from edited counts: the Population map uses the barangay file's baked shade, so a count changed in the database does not change it until the file is rebuilt.

## Decisions and Sources

"26-11-09": tab alignment follows Businesses (left); "centralized" means one shared primitive. Municipality only, never both words. Source order and rules: `design.md`, "Tabs and Municipality".

## Change History

| Date | Change | Reason / Reference |
| :- | :- | :- |
| "26-11-09" | Shared primitives, Government Finances, Data Entry timestamps, Population rework, Municipality wording | Owner review in this session |
| "26-11-09" | `population.tsx` split into `population-map.tsx` and `population-entry-fields.tsx`; page is 476 lines after formatting, not the sub 300 planned | Deep JSX nesting; further splitting would only move lines |

## Verification and Handoff

"26-11-09": Capitol tests 128 passed (new: Data Entry sends Philippine time instants), PHPStan clean, `npm run check`, `types:check` and `build` pass. No browser verification. Full `composer ci:check` not run. Pending sign-off test failure is unrelated (see the archived Data Entry plan).

"26-11-09" (sign-off gap): the committed-data import test required zero pending figures, but 12 Tuguegarao City fs_workbook revenue lines (grants, other, total revenue for 2023 to 2025) were extracted on 26-11-02 and never signed off, so `composer ci:check` failed and the pre-push hook refused the push. Owner said to fix or remove the test. It now requires that every pending figure is a parsed, unreviewed one (nothing half reviewed) and the reviewed figures still import verified. This weakens the old check; the 12 still need a human sign-off in the app, then `php artisan finances:export`, after which the zero-pending check should return. Pending figures stay withheld from every screen.

"26-11-09" (CI rerun): `composer ci:check` passed on `355e6fd`: frontend formatting/lint, TypeScript, all 29 frontend tests, Pint, PHPStan and all 377 PHP tests (2,714 assertions). The finance-import test fix was already present; no further code changes were needed. The outstanding human sign-offs and browser checks above remain unresolved.

