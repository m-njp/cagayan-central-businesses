---
title: "Data Entry"
created: "26-11-03"
updated: "26-11-09"
status: completed
branch: feat/finance-report-redesign
base: feat/data-ingestion-rebuild at 869cf87 (baseline 2b96ff4)
predecessor: 26-11-03-finance-report-redesign.md
---

# Data Entry

## Objective

Step 2 of 4 agreed on "26-11-03". Data entry is scattered and slow: finances take one amount per save, businesses and categories each use their own pattern, and population has no screen at all. Make entry follow the source being copied, use one consistent and robust pattern, and give one place to start and one review queue, without removing the existing pages (the rejected rebuild's mistake).

## Owner Decisions "26-11-03"

- Replace Enter a Figure with a report grid: rows grouped like the Executive Summary, columns the report's fiscal years, cells prefilled with what is on record.
- A Data Entry hub page plus the existing pages. The hub lists every dataset with its entry action, last change and pending count, and one Waiting for Review list. Existing pages keep their views and open the same entry forms.
- Population gets a page and moves to database storage so changes show without a rebuild.
- Status words everywhere: Pending, Verified, Returned (Returned is the label for the stored `rejected` status).
- Zero trust, all of these:
  1. Nothing published changes directly; every edit is a pending proposal and the published value stays live until a different person verifies the replacement. History is kept.
  2. The server checks every write: assigned towns for Staff, Admin-only verification, never your own entry.
  3. Stale-edit refusal: a save carries the values it showed and is refused if any changed since.
  4. Source required for every changed amount (report and page, or an official COA link).
  5. A reason is required to correct a verified amount and to return an entry.
  6. Server limits: valid amounts under the cap, allowed years and lines, a maximum number of changes per save, double-submit protection, rate limiting.
  7. Audit log of submit, verify and return, without amounts or private fields.

## Scope and Acceptance

**Phase 1 Finance report grid**
- [x] Enter Report opens a grid for the town and report year: every enterable line, grouped by section, with the report year and the year before as columns, prefilled with the current figure (verified, else newest pending) and its status.
- [x] One Save for Review sends only changed cells as pending manual figures, citing the chosen report and page (or a COA link). The old single-figure sheet is removed.
- [x] All seven safeguards enforced on the server, each with a failing-without-protection test where it is a security rule.

**Phase 2 Hub and review queue**
- [x] Data Entry page in the sidebar; one Waiting for Review list across datasets for Admins; Shows On column says where each dataset appears.
- [x] Status words Pending, Verified, Returned on every data page.

**Phase 3 Consistent forms**
- [x] Businesses and Business Categories use the same action placement, no field help icons, one toast saying where the data appears after sign-off. Every data page puts its primary action top right in the header with the hub's verbs: Enter Report and Upload Report, Add Business, Add Category or Add Business Type.

**Phase 4 Population**
- [x] Population stored in the database by place and census year, entered through the same grid with independent verification; maps read it without a rebuild (D43). Population page in the sidebar, hub row and review rows; PSA link prefilled from the year's record.

## Deferred or Excluded

New datasets, OCR, public data entry other than the existing wish form, Docker changes, new packages.

## Change History

| Date | Change | Reason / Reference |
| :- | :- | :- |
| "26-11-09" | Completed and archived at the owner's request; the Population, Data Entry and Government Finances redesign continues in [Capitol Entry Redesign](../active/26-11-09-capitol-entry-redesign.md) | Owner called Population and Data Entry poorly designed and asked to finish and archive this plan |
| "26-11-04" | Capitol polish: one `Surface` card and `PageHeader` on every page, neutral stone page background, finance table without scrolling or a Change column (arrow and percent per year cell), Population and finance grids lock verified cells behind a pen with Returned status, Business Categories as one table with icon search and pen edits, dashboard ledger, towns ranking and Waiting for Review, coverage line removed from the Capitol map panel | Owner review in this session; design.md "Surfaces and Page Headers" |
| "26-11-03" | Plan created | Owner answers in this session |
| "26-11-03" | Phases 3 and 4 built | Header actions with the hub's verbs, no help icon, plain save messages; `population_figures`, `SubmitPopulation`, `ReviewPopulation`, `PopulationQuery`, population page, maps read counts from the database (D43). Tests deferred to the end by the owner |
| "26-11-03" | Phase 2 built, plus owner corrections: clickable rows with chevrons instead of underlines, density tokens, report source as buttons, one-hue stacked charts, stripes only on finance tables | `DataEntryQuery`, `row-link.tsx`, `app.css` density tokens; design.md "Dense Data Rule" |
| "26-11-03" | Phase 1 built | `SubmitFinanceEntries`, `FinanceEntryCells`, `finance-report-grid.tsx`; `RecordFinanceFigure`, `StoreFinanceFigureRequest` and `finance-entry-form.tsx` removed |

## Verification and Handoff

Phase 1 "26-11-03": 7 new safeguard tests (stale-edit test proven to fail without its check), 33 finance review tests moved to the grid payload with every assertion kept; Capitol, Finances and Public suites 197 passed, 1 failing only for the pending human sign-off. PHPStan, lint, TypeScript and build pass. Browser: grid prefilled with formatted amounts, Executive Summary as default source, a verified correction without a reason refused with nothing written.

"26-11-04": Capitol, workspace and population tests 41 passed (new: Returned note, Waiting for Review by role), Capitol suite 135 passed before the final edits, test:analytics (percent label), test:trend and test:categories pass, PHPStan not yet rerun, npm check and types:check pass. Browser (Admin demo role, light): dashboard ledger arrows, ranking and no coverage line; Population verified cells locked, pen unlocks one cell; Business Categories toolbar controls present. Full `composer ci:check` and a dark-mode, 375px pass remain.

"26-11-09" (closing): Capitol tests 128 passed, PHPStan clean, `npm run check`, `types:check` and `build` pass after the redesign that follows this plan. Not run: the full `composer ci:check` (a known pending sign-off test fails for the unsigned committed figures), and the dark-mode and 375px browser passes listed on "26-11-04"; the owner asked for no browser screenshot checks in this session. Those two remain open limitations, carried into the successor plan.
