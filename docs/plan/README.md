# Plan History

Read the matching plan before changing a feature. This index separates unfinished work from completed or superseded decisions so improvements can refer to the original reasoning and verification.

Dates inside plans use the requested `yy-mm-dd` format in Asia/Manila. Filenames use `yy-mm-dd-short-name.md`, using the same date in metadata and filenames.

## Active and Deferred

| Plan | Status | Scope |
| :- | :- | :- |
| [Capitol Dashboard and Right Panel Refresh](active/26-11-10-capitol-dashboard-right-panels.md) | In verification | Dashboard tiles, rank movement, shared filters, review pagination and themed scrollbars |
| [Live Finance Parsing](active/26-11-08-live-finance-parsing.md) | In progress | Faster shared Python readers, blur overlay live upload task, durable progress and failed-upload retry |
| [Capitol Entry Redesign](active/26-11-09-capitol-entry-redesign.md) | In verification | Paginated review queue, full-screen Data Entry guide, shared entry controls, and Population barangay map |
| [Kita Promo Production](active/26-11-05-kita-promo-production.md) | In review | Four cuts and stage mixes with the replacement drums and bass score verified 26-11-06; captions, stills and editable music supplied; strict native 60 fps source capture remains unresolved |
| [Business Registry Records](active/26-10-28-business-registry-records.md) | In progress | Staff prepare and Admins review, record profile, form with map pin and documents, BIR and permit badges, public directory, presentation trend seeder |

## Archive

[Executive Summary Only and Faster Tests](archive/26-11-14-executive-summary-only.md): completed "26-11-14". Production build and full `composer ci:check` passed through the normal pre-push hook for `0d573dd`; fresh standalone runs passed 497 PHP tests / 3,678 assertions and 694 parser tests. All 520 workbook originals and six frozen-history files match checksums. Final visual comparison and screenshots remain PR-review limitations; no browser pass is claimed. [Current handoff](../engineering/executive-summary-handoff.md), [timings and verification](../engineering/executive-summary-verification.md).

[Government Finances Report Redesign](archive/26-11-03-finance-report-redesign.md) and [Executive Summary View and Net Income](archive/26-10-29-executive-summary-view.md): superseded "26-11-14" by the current finance design, D47 and D49. Their retained implementation and unfinished historical checks are recorded without restoring retired workbook or Tuguegarao-only scope. Current verification belongs to the Executive Summary Only plan.

[Workbook Data Trim, All-Town Executive Summary and Parser Upkeep](archive/26-11-13-workbook-trim-all-town-summary.md): superseded 26-11-14 by [Executive Summary Only and Faster Tests](archive/26-11-14-executive-summary-only.md). Its Executive Summary work stays: lines for all 29 governments, N/A with reasons, the Government Finances Guide and per-town loading. Its three-field workbook reduction was implemented and then retired the same day with all workbook reading (D49).

[COA Workbook Cleanup and Handoff](archive/26-11-13-coa-workbook-cleanup-handoff.md): completed 26-11-13. Final intake is nine Tuguegarao fields and 2,177 new Pending observations across all 29 governments. Organized 482 source copies by municipality, removed 284 empty numeric folders and 6,253 disposable fixture files, and archived 3,586 generated evidence files with verified member checksums. Originals, sign-offs, Executive Summary data and published statistics remain unchanged. The [developer/AI handoff](../engineering/coa-workbook-handoff.md) records UI exclusions, layout inconsistencies, four reconciliations and remaining integration work.

[Workbook Ingestion for All Governments](archive/26-11-13-workbook-ingestion-all-governments.md): completed 26-11-13, including the owner's narrower field correction. The initial import added 2,385 Pending observations; 208 newly inserted, unreviewed Other Shares from National Taxes observations were removed to match Tuguegarao's nine extracted fields. Final new intake: 2,177. All 2,670 pre-existing observations/review decisions and 1,702 published statistic rows preserved; full CI passed (481 tests, 3,519 assertions).

[Historical Expense Source Differences](archive/26-11-13-historical-expense-differences.md): completed 26-11-13. Original XLSX/PDF inspection reconciles all four 2014 differences to exact component-scope bridges; 24 equations and all 915 source/canonical/parser preservation hashes pass. Claveria and Santa Praxedes retain comparative-coverage limitations. No source edits, parser changes or imports in this investigation.

[Historical XLSX Staged Parsing](archive/26-11-13-historical-xlsx-staged-parsing.md): completed 26-11-13. Version 1.5.1 stages all 221 historical originals: 744 eligible per-file observations (732 unique-content), 122 zero-eligible outcomes fully inspected, and four source-total differences against Executive Summaries. All 40 no-XLSX municipality/year pairs are listed with alternate formats explicitly unconfirmed. All 521 originals and canonical/source registers remain unchanged; 802 Python tests and full CI passed. No imports or sign-offs. PDF extraction logic is unchanged; shared version metadata is disclosed.

[COA Excel Collection 2014 to 2019](archive/26-11-13-coa-excel-collection-2014-2019.md): completed 26-11-13. Downloaded and processed all 174 official Annual Audit Report ZIPs, retaining 221 readable XLSX originals with source URLs, member paths and checksums. All 29 governments per year reconciled; 40 readable no-XLSX outcomes recorded and processed ZIPs deleted. Independent source-preservation and all-174 checkpoint-reuse checks passed. No historical financial parsing or imports.

[XLSX Parser Hardening](archive/26-11-13-xlsx-parser-hardening.md): completed 26-11-13. Extractor 1.5.0 stages all 300 original workbooks with immutable-source checks and cell evidence. Six baseline zero-result files now yield candidates; 110 unsupported/withheld outcomes and three AAPSI files remain explicit. All 459 changed observations inspected; 789 Python tests and full repository CI passed. No financial imports, sign-offs or publication. Includes the owner's all-sheet layout review and automatic test-fixture cleanup.

[COA Excel Collection](archive/26-11-13-coa-excel-collection.md): completed 26-11-13. Downloaded and checked all 174 Annual Audit Report ZIPs for 2025 through 2020, keeping 300 XLSX workbooks and provenance. ZIP batches use 15; PDFs remain at 12. Successful ZIPs were deleted after durable checkpoints, seven no-XLSX outcomes recorded, and every retained checksum and all 29 geographic identities per year reconciled. All 743 COA Python tests and full repository CI passed, including 481 Laravel tests. No financial imports or sign-offs.

[COA 2025 Verification](archive/26-11-12-coa-2025-verification.md): completed 26-11-12. Checked the remaining 32 Income and Expenses amounts against rendered COA PDF pages at the owner's direction. Exported sign-offs and rebuilt finance statistics: 2025 Income, Expenses and derived Net Income each cover 29/29 municipalities/city. Existing values, citations and unrelated reviews are unchanged; source-change warnings remain. Full CI passed, including 481 Laravel tests.

[COA Accuracy and Reporting Differences](archive/26-11-12-coa-accuracy-and-differences.md): completed 26-11-12. Extractor 1.4.0 fixes the parser regressions found by checking every changed figure against its PDF page, writes the committed CSVs with a printed diff, and labels each finding Within the Report, Between Reports or Extraction Limits in formal wording. `finances:corroborate` signed off 1,142 figures two COA reports print identically (D45), so maps and Analytics reach back to 2014. The Capitol Report Differences page and its Guide list every finding with its PDF page. Open items for the next agent are in section 10.

[COA Parser Evidence Hardening](archive/26-11-12-coa-parser-evidence-hardening.md): completed 26-11-12. Strict financial evidence and safe reparse/import/review checks, with a simple automated confidence score in the existing blur overlay. No new package or approval note. The 348-PDF audit remains staged; ledger values, reviewed metadata and published statistics are unchanged. Python tests, browser verification, build and full CI (461 Laravel tests) passed.

Follow-up: [COA Confidence Eligibility Fix](archive/26-11-12-coa-confidence-eligibility-fix.md), completed 26-11-12. The upload job's omission guard now compares only currently eligible lines, so check-only totals no longer force a false 0 while genuine omissions still do. The live reparse moved confidence from 0 to 100 with ledger rows, sign-offs and reviewed citations unchanged. 463 Laravel and 686 COA tests passed.

[Historical Finance Extraction](archive/26-11-12-historical-finance-extraction.md): completed 26-11-12. Extracted 2,919 observations from 190 reports and queued 1,497 eligible figures as Pending with PDF page links. Preserved existing ledger values, sign-offs and published statistics; 392 backend and 478 COA tests passed. Additional detail remains extracted under the existing Tuguegarao-only rule.

[Source Library and File Organization](archive/26-11-12-source-library-and-file-organization.md): completed 26-11-12. All 348 originals registered for 2014 through 2025; 192 related Downloads files moved with checksums and receipts. Sources now uses a bounded municipality/year browser; the complete 352-report archive, frontend/build, 391 backend tests and 471 COA tests passed.

[COA 2025 Source Completion](archive/26-11-12-coa-2025-source-completion.md): completed 26-11-12. Downloaded and validated the 16 missing summaries with automatic checkpoints and 12-file checks; all 29 sources are archived with provenance and verified bundle checksums.

[COA Browser Collection](archive/26-11-11-coa-browser-collection.md): completed 26-11-12. Automatic browser-folder checkpoints and batches of 12; all 174 Executive Summary PDFs for 2014 through 2019 downloaded, validated and archived with provenance. Collection tests and repository CI passed.

[COA Collection Batches](archive/26-11-11-coa-collection-batches.md): completed 26-11-11. Durable per-download checkpoints, checks every 12 PDFs, isolated failures, bounded local workers and cached resume. All 158 stored PDFs accepted; full repository CI passed.

[Home Navigation and Data Labels](archive/26-10-30-home-navigation-data-labels.md): completed 26-10-30. Frontend checks and build passed; full CI stopped at an unreadable Python cache directory.

| Plan | Status | Reference |
| :- | :- | :- |
| [Home Finance Chart and Public Sources](archive/26-11-10-home-finance-sources.md) | Completed 26-11-10 | Fullscreen chart contrast, public About the Data catalog, direct citations and verified ZIP download. PostGIS CI, production build and browser checks passed. |
| [Audience Startup Bundles](archive/26-11-10-audience-startup-bundles.md) | Cancelled 26-11-10 | Layout split trial reduced bytes but increased first-visit requests. Reverted. |
| [Registry and Finance Code Structure](archive/26-11-10-registry-finance-code-structure.md) | Completed 26-11-10 | Page entries, map and chart lazy loading, registry table and finance evidence. Production and SSR builds, browser request trace and PostGIS CI passed. |
| [Repository Structure Follow-Up](archive/26-11-09-repository-structure-follow-up.md) | Completed 26-11-10 | Shared agent instructions, page-local Population table, sorting focus and finance upload reuse. Full PostGIS CI, build and browser checks passed. |
| [Finance Filters and Business Activity](archive/26-11-09-finance-filters-business-activity.md) | Completed 26-11-09 | Shared dropdown design, finance sidebar, grouped citations and one business activity picker. Final CI, build and browser checks passed. |
| [Maintainability Refresh](archive/26-11-09-maintainability-refresh.md) | Completed 26-11-09 | Home and Businesses controls, page folders, dead-code cleanup, project skills and one frontend test entry point. Final CI, build and browser checks passed. |
| [Home Focus View](archive/26-11-09-home-focus-view.md) | Completed 26-11-09 | Shared blur, theme-aware Home comparison, keyboard focus, and viewport-edge navigation verified on phone and desktop |
| [Data Entry](archive/26-11-03-data-entry.md) | Completed 26-11-09 | Finance report grid with zero-trust safeguards, Data Entry hub and review queue, consistent forms, population in the database; Capitol tests 128 passed; full CI, dark mode and 375px not run |
| [Kita Showcase Fast Cut](archive/26-11-05-showcase-fast-cut.md) | Completed 26-11-05 | New 72 second 1080p60 cut with two original illustrations, new full-screen motion areas, fresh interactions, glyph morphing, state feedback and 140 BPM sound; decoded media checked |
| [Kita Showcase Motion Rebuild](archive/26-11-05-showcase-motion-rebuild.md) | Superseded 26-11-05 | Rejected for slow pacing, arbitrary zooming and reused compositions; replacement continues in Showcase Fast Cut |
| [Kita Showcase Reel](archive/26-11-05-showcase-reel.md) | Superseded 26-11-05 | Technically verified export rejected as a screenshot slideshow with boring music; replacement continues in Showcase Motion Rebuild |
| [Data Ingestion Rebuild](archive/26-11-02-data-ingestion-rebuild.md) | Abandoned 26-11-03 | Owner rejected the redesign and scope; reverted to `2b96ff4`, work kept in a stash |
| [Finance Trends People Can Understand](archive/26-10-29-finance-trends.md) | Superseded 26-10-29 | Chart-led Trends default; Trends stays a view under the Executive Summary View plan |
| [COA Parser Hardening](archive/26-10-29-coa-parser-hardening.md) | Completed 26-10-29 | Two PDF engines with fallback and digit confirmation, crash isolation and run log, wrapped Appropriations label fix, all 201 Tuguegarao cells tested, camelCase Python; 77 parser tests passed |
| [Business Categories Revamp](archive/26-10-28-business-categories.md) | Completed 26-10-28, Focus Tag model superseded in part by D39 | Editable Business Types and Focus Tags, registered counts, local sort, search and paging, ten-color tag palette without a package (D38); full suite not run, light mode and edit forms not browser-checked |
| [Finance Ledger Table Redesign and No Simulated Data](archive/26-10-28-finance-ledger-redesign.md) | Completed 26-10-28 | Simulated data off by default, one evidence store, stored PDF links, ledger table, short Help, and the polish: About the Data fix, year order, shared surplus and deficit display, exact amounts on hover, M26 percent over the prior magnitude; Capitol Analytics checked; 306 PHP tests passed |
| [Make Analytics Understandable](archive/26-10-28-analytics-clarity.md) | Superseded 26-10-28 | Periods, local history and metrics stayed; layout, sources, Help and Compare replaced by the ledger redesign |
| [Category Hierarchy and Business Directory](archive/26-10-28-category-hierarchy.md) | Completed 26-10-28 | One Category per Business Type replacing Focus Tags (D39), starter taxonomy, categories page, read-only server-paged Businesses directory, registry groundwork; 304 PHP tests passed; directory rows and light mode not browser-checked |
| [Economic Map Cleanup](archive/26-10-28-economic-map-cleanup.md) | Completed 26-10-28 | Removed the map Measure selector, one page-anchored Source list, no repeated rows, Surplus or Deficit triangle; Capitol map, 375px and dark mode not browser-checked |
| [Dynamic Economic Map](archive/26-10-28-dynamic-economic-map.md) | Completed 26-10-28 | Public/Capitol annual finance maps, global Latest, coverage and shades, transactional refresh, dynamic review/Analytics year bounds; 270 PHP and nine frontend tests passed |
| [Finance Ledger and Analytics Program](archive/26-10-27-finance-ledger-analytics-program.md) | Completed 26-10-28 | COA parser, finance ledger with sign-off, Capitol review, workbook breakdowns, business records and privacy guard, analytics pages, reliability review, every figure signed off, single-folder consolidation; follow-ups listed |
| [Client Facing Polishing](archive/26-10-27-client-facing-polishing.md) | Completed 26-10-28 | Local-only quick sign in for the three demo roles and wish cloud sized by relative demand; shipped in the combined finance pull request |
| [Government Finances and Economic Context](archive/26-10-24-government-finances-economic-context.md) | Superseded 26-10-27 | Direction, Executive Summary collection and source rules; remainder continues in the Finance Ledger and Analytics Program |
| [Census Atlas Follow-Up](archive/26-10-27-census-atlas-follow-up.md) | Completed 26-10-27 | Barangay atlas cache revisit and missing-population tooltip fixes, plus corrections to the cleanup plan's reuse claims |
| [Single Source of Truth Cleanup](archive/26-10-27-single-source-of-truth-cleanup.md) | Completed 26-10-27 | Shared frontend/backend SSOT helpers, census loader/cache consolidation, three-copy line-count tightening, and obsolete branch cleanup |
| [Compact Home and Session Handoff](archive/26-10-25-compact-home-session-handoff.md) | Completed 26-10-25 | Compact cards, UTF-8 correction, session verification and PRs #16/#17 |
| [Home Economy and Wishes](archive/26-10-25-home-economy-wishes.md) | Completed 26-10-25 | Home actions, combined wishes, scalable ranked bars, town tooltips and copy rules |
| [Local Economy Selections](archive/26-10-25-local-economy-selections.md) | Completed 26-10-25 | Zero-request local filters/maps, bounded public catalog and durable interaction rules |
| [Cagayan Economy Polish](archive/26-10-25-cagayan-economy-polish.md) | Completed 26-10-25 | Compact stacked sections, icon views, stronger grid, town map and obsolete-code cleanup |
| [Cagayan Economic Performance](archive/26-10-25-cagayan-economic-performance.md) | Completed 26-10-25 | Tuguegarao finance and curated-wishes demo; final-only tests, CI, builds and browser verification |
| [Super Admin Dashboard Foundation](archive/26-10-24-super-admin-dashboard-foundation.md) | Completed 26-10-25 | Foundation, later corrections, source verification and final checks |
| [Public Landing Page](archive/26-10-23-public-landing-page.md) | Completed before this register was created | Merged PR #15 |

## Lifecycle

- Create a record for an agreed implementation plan using [the plan-history skill](../../.claude/skills/plan-history/SKILL.md). Resume an existing matching plan when possible.
- Keep accepted scope, dated changes, primary references, and actual verification evidence together. Keep deferred work visibly deferred.
- Archive the same file when completed, cancelled, or superseded; preserve the reason and link its successor. Future improvements get a new plan referring back to it.
- Plan updates do not authorize new scope or external actions. Credentials and protected records never belong in these documents.
