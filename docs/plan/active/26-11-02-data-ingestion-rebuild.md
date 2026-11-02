---
title: "Kita Data Flow and Finance Report Rebuild"
created: "26-11-02"
updated: "26-11-02"
status: in-progress
branch: feat/data-ingestion-rebuild
base: feat/business-registry
predecessor: 26-10-29-executive-summary-view.md
---

# Kita Data Flow and Finance Report Rebuild

## Objective

Answer: What am I updating, and where will it appear? Build one clear path from source entry to public and Capitol publication: Choose Data, Enter or Upload, Check, Submit, Admin Review, Publish. The user approved this complete plan on "26-11-02". Later explicit decisions here supersede earlier finance and registry presentation decisions.

## Financial Decisions

| Item | Decision | Reason |
| :- | :- | :- |
| Per Resident | Remove | Explicit user exclusion |
| Development Utilization | Remove | Workbook ratio and COA's reported utilization have different inputs |
| Locally Generated Share | Remove | Consolidated COA income does not establish BLGF's Total Regular Income denominator |
| Budget Utilization | Remove | Current formula divides obligations by appropriations, unlike the cited obligation rate |
| Current Obligations | Keep prominently | Reported and already extracted across six Tuguegarao reports |
| Final Budget and Actual Amounts | Keep together | Source workbook columns |
| Net Income and annual changes | Keep | Reproducible arithmetic with comparable inputs |

Use accounting terms and source wording. No performance ratings or replacement percentages with different coverage. The 2025 workbook gives Development Fund actual 54,562,727.33 and final budget 224,537,355.00 (24.30 percent); the Executive Summary page 7 reports 147.464 million / 340.148 million (43.35 percent). This discrepancy is the reason to retire the ratio, not to substitute an audit finding into the dashboard.

Sources inspected: all six stored Tuguegarao Executive Summaries (2020 to 2025), and both Financial Statements and Annex workbooks for 2024 and 2025. The 2025 Financial Performance source is `8 - Financial Performance 2025`; budget comparisons are `11 - SCBAA`; General Fund taxes and fees are `ANNEX-B`. The budget comparison parser currently cites heading rows instead of contributing ranges and must preserve exact input cells.

Primary methodological references: [DBM national-government guide](https://www.dbm.gov.ph/images/pdffiles/2016-BUDGET-NG-BAYANTagalog.pdf) and [BLGF definitions](https://blgf.gov.ph/wp-content/uploads/2020/01/MC-004-2019-DO-No-075-2018-Establishing-the-LGU-Fiscal-Sustainability-Scorecard-FSS-in-the-BLGF.pdf). Neither establishes the app's withdrawn ratios as the official LGU indicator.

## Phase One Plan Record and Branch History

- [x] Generate this complete Markdown plan before implementation.
- [x] Review and commit existing `feat/business-registry` implementation as the parent baseline, excluding this new plan.
- [x] Record its commit and create `feat/data-ingestion-rebuild` directly from it, in this repository only.
- [ ] Add this plan to the index and commit the plan on the child branch.
- [ ] Commit the completed rebuild on the child branch after final verification.

No worktree, second clone, push, merge, or hook bypass. Review named files before staging. The user explicitly defers behavioral test execution until all implementation and documentation phases finish, including for the baseline commit. Pre-commit formatting, lint and static checks remain enabled. Never describe deferred tests as passed.

## Phase Two Source Definitions and Contracts

- [ ] Extend the existing authoritative catalog, without a second metric registry. Each retained metric identifies its accounting name, meaning, reported/summed/calculated method, exact input IDs, source document, fiscal year, fund, basis, source ranges, missing-input and restatement rules, comparability and methodological reference.
- [ ] Remove all four retired indicator groups from controls, public payloads, SQL calculation output, Help and active definitions. Preserve retirement history.
- [ ] Fix workbook provenance to the actual contributing cells. The 2025 Economic Services example uses final budget G61:G64 and actual amounts K61:K64. Preserve every summed cell, including offsets such as Real Property Tax discounts.
- [ ] Use one catalog for entry, parser keys, review, tables, charts and explanations. Money remains decimal strings. Calculations remain SQL.
- [ ] Introduce explicit readonly submission, review, preview and population Data objects mirrored in TypeScript. Staged private payloads must never be serialized directly.

## Phase Three Consolidated Finance Report

- [ ] One Government Finances report with Table / Charts, default Table, and six source sections: Financial Condition; Results of Operation; Budget Execution; Revenue Sources; Business Taxes and Fees; Budget and Actual Amounts (Economic Services and 20% Development Fund).
- [ ] Keep current and continuing appropriations, allotments and obligations distinct. Replace calculated Other Revenue remainder with source-reported income lines where available, using accounting labels and reconciling with the source total.
- [ ] Town is page context. One Period control offers individual available fiscal years, Last 3 Years, Last 5 Years and All Years (default). Table and charts select identical observations.
- [ ] Remove Business Trends, Industry, Group By, View/Show fragmentation and the shared analytics filter rail. Preserve stored annual business and sales records.
- [ ] Share compact chart dimensions, axes, grid, legend, loading/error behavior, figure rendering and formatting. Plot reported Assets directly, not an assumed reconstructed total. Missing observations stay gaps.
- [ ] Use consistent neutral unavailable treatment across tables/charts/maps, and one section-level state if a whole section is unavailable. Never substitute zero.
- [ ] One reusable question-mark control opens through hover, focus or tap. Show meaning, method, exact input amounts, source document/ranges and necessary scope. Reuse the installed tooltip for read-only content; source links stay in the report source block. No long Help drawer, input descriptors, subtitles, decorative counts or new package.
- [ ] URL state: town, view=table|charts, period; support useful legacy finance links and remove retired parameters. All loaded interactions, repeated selections and Back/Forward use local Inertia history with zero application requests.

## Phase Four Data Workspace and Finance Intake

- [ ] One Data workspace: Government Finances, Population, Businesses, Business Categories, Wishes and authorized Review queue. Existing entry-page bookmarks lead to the corresponding Data view. Keep read-only business browsing distinct from maintenance.
- [ ] Shared lifecycle Draft, For Review, Published, Returned. Staff prepare assigned-town data. Admin/Super Admin review. Authors cannot approve their own submission, including category edits.
- [ ] Stage changes separately; retain the last published version during corrections. Submitted versions are immutable. Reject stale target revisions. Publish domain writes and affected statistics transactionally; failure leaves previous publication intact. Increment publication versions for data caches.
- [ ] Use domain-specific Form Requests, Actions, Queries and readonly DTOs. Share mechanics, not a universal form/workflow/ETL framework. No speculative interfaces or new packages.
- [ ] Finance workflow: report context and sources; upload or structured manual entry; grouped figures with explicit fiscal-year columns; Changes and Preview; Submit. Drafts allow incomplete values. Submission validates applicable fields and source. Derived values are never editable fields.
- [ ] Expose the existing parser with a file-scoped queued job, never the repository-wide extraction command in a request. Support PDF, XLSX and recognized workbook ZIP members. Preserve immutable files, checksums, version, issues, page/ranges. Deduplicate uploads/retries, enforce configured file/archive/runtime/retry limits, and permit manual entry for unsupported/image-only files. No OCR service.
- [ ] Verify existing Current Obligations mapping; change Python only where missing/incorrect handling is found. Python becomes a documented worker prerequisite checked by the existing health command. No Docker changes.
- [ ] Authorized previews use the actual destination components with the appropriate audience DTO, never private fields in public-shaped props. Log private record and draft-preview access.

## Phase Five Remaining Inputs and Documentation

- [ ] Population: one published year-based dataset backfilled from existing values/sources across all currently displayed places. Keep static geometry separate. Scoped imports/submissions do not erase other towns or years. Changes reach Home/census/dashboard without a frontend build. Removing Per Resident removes the finance dependency.
- [ ] Businesses: functional sections Business, Location, Registration, Permits, Documents, Check. Preserve drafts between sections. Permit history has year, number, validity dates and evidence. Renewal Streak uses consecutive permit years ending in configured registry reference year. Operating Milestone uses establishment year and a permit covering that year. Gaps break streaks without implying closure. Keep thresholds, original medal design and BIR eligibility. Replace annual-status UI dependencies with permits and publication state; retain historical records.
- [ ] Categories/types: move existing forms into Data, preserve stable codes/relationships, independent approval, remove Registered count columns.
- [ ] Wishes: unchanged public form; labeled samples for demo, separate sample provider and live Business Type totals. No aliases, fuzzy matching or additional exposure of resident text.
- [ ] Complete architecture, schema, metrics, privacy, design, frontend, operations, roadmap and plan docs before tests. Every retained calculation records method, basis, source and inputs. Trace callers before removing obsolete code.
- [ ] Preserve existing data/UUIDs/citations and unrelated branch work. No individual sales in responses; private owner/document fields remain authorized; TIN masked; home-based pins protected; simulated business records labeled. No fabricated data or performance claims.

## Final Verification and Commit

Execute tests only after Phases One to Five, including documentation, are complete. Write/update necessary regression tests during implementation but do not execute them early.

- [ ] Manual and extracted inputs produce equivalent validated figures; source values and every summed range reconcile; Net Income and comparable changes reproduce.
- [ ] Withdrawn indicators are absent; contextual help works with hover/focus/touch; missing values are neutral and never zero.
- [ ] Review publishes every affected destination together; returned/failed/duplicate/concurrent/stale submissions preserve correct publication; no self-approval.
- [ ] Population changes require no build; permit gaps give factual medals; taxonomy changes wait for independent approval.
- [ ] Privacy, assigned-town authorization, protected file delivery and record/draft access logging pass. Public wish form unchanged; samples remain labeled.
- [ ] Local filters/toggles/Back/Forward make zero app requests. Desktop/375px, light/dark, keyboard/focus/reduced-motion checks pass.
- [ ] Run affected PostGIS, parser and frontend tests, frontend check, TypeScript, build, full CI, design review and privacy review. Resolve failures before completion.
- [ ] Record actual results and limitations; commit completed rebuild on child branch, preserving parent and plan commits. No push/merge.

## Deferred or Excluded

New economic indicators, official utilization scoring, geography editing, business-document OCR, spelling normalization, new annual-status entry, packages and Docker changes. Audit findings and project lists stay excluded. Real source data stays distinct from demonstration fixtures.

## Change History

| Date | Change | Reason |
| :- | :- | :- |
| "26-11-02" | User approved complete rebuild and source-grounded finance simplification | This chat; supersedes earlier keep-ratios proposal |

## Verification and Handoff

Parent baseline: `2b96ff4` on `feat/business-registry`. Read-only privacy review checked P1 to P15 and found a cross-town home-based pin leak; corrected the DTO/query and added a regression case without running it. All pre-commit formatting/lint/static checks passed. Behavioral tests are deferred by explicit user request. Child branch created directly from this baseline, without another checkout.

Source inspection completed during planning. Implementation, automated verification and browser acceptance are pending. No test execution during implementation phases, by explicit user request.
