---
title: "Finance Ledger and Analytics Program"
created: "26-10-27"
updated: "26-10-28"
status: completed
branch: "feat/business-analytics (Phases 3, 5 and 6 combined, 26-10-28)"
base: staging
predecessor: "archive/26-10-24-government-finances-economic-context.md"
---

# Finance Ledger and Analytics Program

## Objective

On 26-10-27 the user asked for a methodical, full program covering six things:

- a reliable, repeatable parser for the COA Executive Summary PDFs and audit-report workbooks
- honest handling of towns and years with no report
- government finance fields beyond total revenue and expenses: revenue sources, expense types and spending by sector
- Capitol forms where admins enter and correct COA figures
- business industries plus Focus Tags, such as Agriculture and Fishery
- a centralized, transparent analytics engine on a dedicated page for the public and the Capitol

There is no deadline pressure: "we can finish this one". This program supersedes the deferred remainder of [Government Finances and Economic Context](../archive/26-10-24-government-finances-economic-context.md).

## Phases

Each phase gets its own branch from `staging` and its own pull request.

| Phase | Branch | Status |
| :- | :- | :- |
| 0 Docs and decisions | `docs/finance-ledger-decisions` | Merged, [PR #22](https://github.com/m-njp/cagayan-central-businesses/pull/22) |
| 1 Python parser for Executive Summaries | `data/coa-parser` | Merged, [PR #23](https://github.com/m-njp/cagayan-central-businesses/pull/23) |
| 2 Finance ledger schema and import | `feat/finance-ledger` | Merged, [PR #24](https://github.com/m-njp/cagayan-central-businesses/pull/24) |
| 3 Manual entry and sign-off in the Capitol | `feat/business-analytics` (was `bug/finance-review-ui`) | Done; combined pull request |
| 4 Workbook collection and workbook parser | `data/coa-workbooks` | Merged, [PR #25](https://github.com/m-njp/cagayan-central-businesses/pull/25); remaining collection deferred |
| 5 Industries, Focus Tags, simulated businesses, privacy guard | `feat/business-analytics` | Done; combined pull request |
| 6 Analytics engine and pages | `feat/business-analytics` | Done; combined pull request |

On 26-10-28 the user asked to combine the unmerged work into one branch and one pull request, worked from the single `cagayan-business-kickoff` folder. Phases 3, 5 and 6, the quick sign in and wish cloud work from [Client Facing Polishing](26-10-27-client-facing-polishing.md), and the reliability review below travel together.

## Scope and Acceptance

### Phase 0

- [x] Record this program and supersede the deferred finance plan.
- [x] Add decisions D34 (Python source tooling), D35 (Finance ledger) and D36 (Industries and Focus Tags).
- [x] Draft metric definitions M19 to M31 and the missing-data rules in `metrics.md`.
- [x] User approved the metric definitions and the per capita census rule (26-10-28).
- [x] Fix the contradictions found during research: finance labels, year range, and the audit opinion and project wording.

### Phase 1: Python Parser for Executive Summaries

- [x] `tools/coa/` holds a pinned `requirements.txt` (pdfplumber, openpyxl, pytest), the `coa` package and its tests. Composer runs it with `data:finances:extract` and `data:finances:test`.
- [x] `data/towns/finances/lines.csv` is the single source of truth for line codes and source-label aliases, shared by Python and PHP.
- [x] Committed outputs: `extracted/<year>.csv`, `documents.csv` and `issues/<year>.csv`, each stamped with an extractor version.
- [x] Automatic checks:
  - Assets = Liabilities + Equity.
  - Revenue minus Expenses = Net Income.
  - The stated change column matches the computed change.
  - The prior-year column matches the previous report (otherwise flagged as restated).
  - The SHA-256 matches `sources.csv`.
- [x] Reproduces the four verified 2024 pairs to the cent and passes golden fixtures for 12 layouts.
- [x] A full run over all 158 PDFs, with its counts recorded below.

### Phase 2: Finance Ledger

- [x] Create the `finance_lines`, `finance_documents` and `finance_figures` tables. A partial unique index allows only one verified figure per town, year, line and basis.
- [x] `finances:import`:
  - never deletes or overwrites manual rows
  - resets a figure to pending when its value changes
- [x] `finances:export` writes sign-offs and manual entries to committed CSVs, so they survive `composer db:reset`.
- [x] Move revenue and expenditure out of `town_facts`. Home and the workspace map read the newest verified ledger figures, with the Data shapes unchanged.

### Phase 3: Capitol Review

#### UI and Input Corrections Requested 26-10-28

Work only in `cagayan-business-kickoff`, on `bug/finance-review-ui` from the integrated preview branch. The finance folder is a preserved earlier working copy, not the active implementation.

The index answers which town/report needs review; its first screen should show a compact report list, explicit pagination and a right filter panel. The report answers which figures belong to each fiscal year and which are ready for review.

- [x] Shared pagination with previous/next, numbered pages, total pages and record range, reused by finance, Community Wishes and Activity Logs.
- [x] Central breadcrumbs in the workspace layout, including nested town/report routes; document the page contract and required use.
- [x] Compact index table and right-hand desktop filters, stacked on phones.
- [x] Fiscal-year tabs, newest first, and category groups. Switching already-loaded years uses local URL history without a request.
- [x] Source cards with official COA destinations and per-figure source provenance; remove redundant back-links from content.
- [x] Enter a Figure opens a right-hand drawer; Sign Off and Reject use separate dialogs. Rejection requires a reason; sign-off notes are optional.
- [x] Shared accessible required marker on labels, used with actual required controls.
- [x] Central input limits on server and browser; official COA HTTPS URLs only, line-appropriate basis, no negative values for the 13 stored lines, no figure year later than its report. Existing maximum is preserved.
- [x] PostGIS validation and pagination regressions, full CI, production build and desktop browser flow verification.

Audit before changes: 1,339 figures, 240 documents, 0 manual entries and 9 verified figures in the finance preview database (read-only query). There are 13 stored financial lines. Manual entry has seven submitted fields: line, fiscal year, basis, value, source kind, conditional source URL and note. Prior implementation accepted either basis for every line, negative values for all lines, any HTTPS source, and any configured year regardless of report year. Structural database constraints enforce source presence, enums, foreign keys and a single verified observation; they do not enforce all form semantics. No database reset or change to metric definitions is authorized by these UI corrections.

#### Follow-up Simplification, 26-10-28

The user's screenshot review superseded the earlier tabs, source-card strip and expanded pagination. The uniform table and concise layout below are the final accepted direction; the earlier checked items describe the first draft only.

- [x] Replace separate category tables with one aligned table and internal category rows; use Item and Amount in PHP (₱).
- [x] Put Fiscal Year in the right filter panel while retaining local history, newest-first choices and Back/Forward.
- [x] Remove the repeated report-year subtitle. Replace source cards with compact Source attribution; link the actual source/page or sheet/cell citation in each row.
- [x] Default to reading mode. Reveal checkboxes through Review Figures; show bulk actions and a selection count only for a non-empty selection.
- [x] Use quiet Previous / Page n of m / Next pagination and remove the inner width cap causing the screenshot's asymmetric empty strip.
- [x] Document the repeated unwanted patterns in design.md, frontend.md, CLAUDE.md and the path-scoped frontend rule.
- [x] Rerun the frontend checks and record verification of this simplified revision.

- [x] Capitol finance index, detail page, entry form and sign-off.
- [x] Gates:
  - `finance-entry`: Staff for their assigned towns; Admin and Super Admin anywhere.
  - `finance-review`: Admin and Super Admin, never on a figure they entered themselves.
- [x] Activity Log events carry an event code and an actor key only.

### Phase 4: Workbook Collection and Parser

- [ ] Collect the remaining Annual Audit Report ZIPs. Deferred 26-10-28 by the user to a separate session; this phase uses the 17 archives already on disk.
- [x] Read the FS and Annex workbooks, finding sheets by their content.
- [x] Extract the business-focused breakdowns: revenue sources, business taxes and fees, and development spending (trimmed 26-10-28).
- [x] Extract business taxes and fees from the General Fund annex.
- [x] Reproduce the values read during planning.

### Phase 5: Industries and Business Data

- [ ] Have the PSIC sections verified with a dated record.
- [x] Add Focus Tags and the Business Categories page.
- [x] Add the `businesses` and `business_years` tables, plus a simulated seed.
- [x] Add `PrivacyGuard`.
- [x] Implement `metrics:refresh` with `stats_area_year`. Industry and Focus Tag totals are summed from it in SQL instead of a second `stats_industry_year` table (26-10-28).

### Phase 6: Analytics Engine

- [x] Add the metric registry. It is the derived rows of `lines.csv` (source `derived`) with their `cagayan.finances.groups`, not a separate enum (26-10-28).
- [x] Add `stats_finance_year`, computed in SQL.
- [x] Add `AnalyticsCatalogQuery` and `BusinessTrendsQuery`, with one `public` switch for bands and the sales share (26-10-28).
- [x] The public Economy page reads the ledger and business trends; add a Capitol Analytics page sharing its pickers and panels.
- [x] Every state is handled, and selections of already loaded data are local.

## Field Catalog

The catalog is the contract for the parser, the ledger, the entry form and the screens. Definitions are in [metrics.md, Government Finances](../../engineering/metrics.md#government-finances-approved-26-10-28).

| Section | Fields | Source | Shown on |
| :- | :- | :- | :- |
| A Totals | Government Revenue, Government Expenses, Surplus or Deficit | Executive Summary (all towns); FS workbook as a cross-check | Home map panel (revenue and expenses only); Analytics |
| B Budget Execution | Current Appropriations, Current Obligations, Budget Utilization | Executive Summary | Analytics |
| C Revenue Sources | Local Taxes, National Tax Allotment Share, Fees and Business Income; Other Revenue and Locally Generated Share derived | FS, Statement of Financial Performance | Analytics |
| D Business Taxes and Fees | Business Tax, Permit Fees, Market Operations, Real Property Tax (General Fund) | Annex | Analytics |
| E Development Spending | 20% Development Fund, Economic Services, with final budget and utilization | FS, SCBAA | Analytics |
| G Checks only | Assets, Liabilities, Equity, Net Income, allotments, continuing items, other revenue lines, expense types, other sector headings | Summary and workbooks | Read for arithmetic and section boundaries, never stored |

## Deferred or Excluded

The following are out of scope:

- **Audit opinions and audit findings** (user, 26-10-27): not extracted, stored or displayed.
- **Infrastructure project lists** (user, 26-10-27): not extracted, stored or displayed.
- **BIR registration seal and online posting:** a separate plan.
- **COA fiscal year 2026:** waits for its report.
- **Python predictions and the outlook:** not part of this program.
- **Other annex revenue lines:** hospital, cemetery and quarry lines are not extracted.

### Follow-ups Left for Later Work (26-10-28)

These are known and documented; each needs its own plan when started.

- **Remaining COA workbooks:** only 17 workbook pairs are on disk (4 towns for 2024, 13 for 2025). Fiscal 2019 to 2023 breakdowns show "The COA workbook for this year has not been added yet".
- **Other towns' breakdowns:** parsed but not loaded. Add a town's PSGC code to `cagayan.finances.workbook_towns` after reviewing its workbook issues (Santo Niño 2025 misses an unlabeled ₱22,392.82 revenue row; Enrile 2025 has no separate national share).
- **PSIC sections:** a data member verifies each Business Type's industry against the PSA classification and records the date.
- **Seeder requirements 7 and 8** (early warning, peer median) wait on C10 and C11.
- **Privacy notes from the review, not rule breaches:** the public page forces the Simulated label on; public tooltips name the hidden reason; the Capitol sales change can approach one business's sales when a group gains or loses one business.

## Decisions and Sources

- **26-10-27, publishing:** the parser checks figures automatically, then a person signs off. Nothing publishes without sign-off. This keeps the existing verification rule ([D35](../../engineering/decisions.md#d35-finance-ledger)).
- **26-10-27, parser:** Python, run locally only. This session set up `tools/coa/` with pdfplumber and openpyxl as the recommended default ([D34](../../engineering/decisions.md#d34-python-source-tooling)).
- **26-10-27, breakdowns:** collect the audit-report workbooks for all 29 towns. Missing ones show Not available.
- **26-10-27, business grouping:** one PSIC industry per Business Type, plus admin-managed Focus Tags ([D36](../../engineering/decisions.md#d36-industries-and-focus-tags)).
- **26-10-27, manual entry:** admins can enter and correct figures through a form, sharing one ledger with parsed figures.
- **26-10-27, analytics audience:** analytics is for both the public and the Capitol, at different depth.

### Sources inspected during planning

- **Executive Summaries:** all 158 have a text layer. The labels vary, for example "Income", "Total Income (Includes Subsidy from Other Funds)" and "Total Revenues". Baggao 2021 interleaves its tables with other text.
- **Workbooks read:**
  - Tuguegarao and Abulug, 2024
  - Gattaran, Baggao and Rizal, 2025
- **What the workbooks show:**
  - Sheet names vary, for example `Performance`, `6 - Financial Performance 2024`, `Stmt of Finl Performance` and `B-1 (Consolidated SFPerf)`.
  - Sector headings vary.
  - Gattaran's 2025 SCBAA sheet carries a 2024 title.

## Change History

| Date | Change | Reason / Reference |
| :- | :- | :- |
| 26-10-27 | Program recorded and Phase 0 documentation drafted | User approved the umbrella plan after two rounds of questions |
| 26-10-27 | Audit opinions and infrastructure projects excluded | User correction during plan review |
| 26-10-28 | Metric definitions M19 to M31 approved; Phases 1 and 2 authorized | User approval |
| 26-10-28 | Scope trimmed to a business focus by the user. Expense types, health, education, social welfare and other sector spending, per-sector capital outlay, Community Tax, and stored assets, liabilities, net income and allotments are dropped. M21 to M23 were revised and M24 withdrawn. Remaining ZIP collection deferred; Phase 4 uses the 17 archives on disk | User review of the workbook results |
| 26-10-28 | Phase 2 ledger built with three changes to the column plan. Review columns are `reviewed_by` and `reviewed_on`, because rejections need them too. The report year comes from `finance_document_id`, not a separate `reported_in_year` column. A nullable `source_url` lets a manual figure cite a report that is not in `documents.csv`. A check constraint requires a document or a URL | Found while building |
| 26-10-28 | Phase 6 built without the `AnalyticsMetric` enum or `AnalyticsSeriesQuery`. The registry is the derived rows of `lines.csv`, so the ledger, the entry form and the screens share one list; derived rows are never enterable. One catalog query serves both pages, switched by audience. The finance part of `demo.php` was removed; only the curated wishes remain | User: "Do not duplicate codes, functions, and other pages" |
| 26-10-28 | Focus Tag sales are bands for every audience, Capitol included. The privacy review found that an exact tag figure minus an overlapping industry could expose a group the guard never checked | User decision after the privacy review; privacy-rules.md P4 |
| 26-10-28 | Every loaded parsed figure signed off at the user's direction ("sign everything"). Workbook breakdowns load for Tuguegarao City only; other towns' parsed workbooks stay in the CSVs | User, 26-10-28 |
| 26-10-28 | Reliability review: Gattaran 2024 found printed in the opposite order to its column headers; the parser now reads printed order when two independent proofs hold (extractor version 2). Amulung and Rizal 2025 checked against the PDFs and signed off | User: "this is scary ... we have to check" |
| 26-10-28 | New observation reason `not_collected` and visible `pending_review`; restated years marked (M26); business year gaps shown as Not available | User: "dont ever put 0. Put Not Available" |
| 26-10-28 | Work consolidated into the single kickoff folder; the finance and follow-up worktrees removed; one-folder rule added to CLAUDE.md and workflow.md | User: "everything should be in kickoff" |
| 26-10-28 | Plan completed and archived with the follow-ups above | User request |
| 26-10-28 | Phase 1 parser built. Summaries that print Appropriations, Allotments or Obligations without current or continuing map to the current line with `definition_variant` `unspecified_period`, as the catalog requires. A new `repeats_previous_year` check flags swapped columns | Found while reading all 158 summaries |

## Verification and Handoff

### Phase 3 UI and Input Verification, 26-10-28

- All changes are in `cagayan-business-kickoff` on `bug/finance-review-ui`; the older finance checkout is unchanged. The original phase status was stale and has been corrected. No new dependency, schema change or database reset.
- Final `composer ci:check` passed: frontend format/lint, TypeScript, Pint, PHPStan (0 errors), and 224 tests with 1,343 assertions. All database tests use `cagayan_business_test` on PostGIS. `npm run build` passed with existing bundle-size warnings.
- Added regressions for exact amount limits and digit count, decimals/scientific notation, report/fiscal-year ordering, line-specific basis, note length, unofficial/lookalike/credential-bearing sources, malicious line input, pagination links/metadata, manual source provenance, and manual CSV bypasses. The unofficial-source test failed when the URL rule was disabled only inside a test process; the rule was immediately restored and final checks passed.
- Browser on port 8000: index pagination and Page 2 breadcrumbs; town/report breadcrumbs; fiscal-year selector and Back restoring 2025 from 2024; one report table; no checkboxes in reading mode; no bulk actions with empty selection; Sign Off opens an optional note and Reject a required reason; Cancel restores focus. No finance entry or review was submitted during these browser checks.
- The simplified revision was visually checked in light and dark at a 1,210px browser width. DOM bounds confirm the filter's right edge is 24px inside the workspace edge, matching its gutter instead of leaving the old asymmetric strip. The final narrow-phone revision has not been visually rechecked.
- The first draft's fiscal-year tabs, source cards and verbose pagination are explicitly superseded by Follow-up Simplification above. Shared usage and the recurring unwanted patterns are documented in frontend.md, design.md, CLAUDE.md and `.claude/rules/frontend.md`. No UI correction is treated as permission to alter metric definitions or source data.

Phase 0 is documentation only. No application code, data or database changed, so no tests or builds were run. Relative links and anchors in the changed docs were checked.

### Phase 1 Verification, 26-10-28

- **Parser tests:** `composer data:finances:test` passed, 46 tests.
  - The tests cover 12 layouts, with expected values read from the PDFs by hand.
  - They reproduce the four verified 2024 pairs to the cent.
  - They also cover variants, a repeated header year, a reversed change column, swapped-column detection, and the Rizal 2025 source net income mismatch.
- **Full run:** `composer data:finances:extract` read all 158 summaries, 2,440 figures.
  - Assets, Liabilities, Equity, Government Revenue and Government Expenses were found in all 158.
  - Net Income was found in 61, Current Appropriations in 108, and Current Obligations in 109. The others do not print these lines.
- **Independent check:** every extracted value appears in Xpdf `pdftotext -raw` text of the same PDF. That confirms the digits, not their rows. The arithmetic checks and layout tests cover rows.
- **Issues recorded:**
  - 10 errors: Gattaran 2024 swapped columns, and Amulung 2025 and Rizal 2025 source net income.
  - 7 warnings.
  - 92 informational: restatements and reversed change signs.
  - Details are in `data/towns/finances/README.md`.
- **Doctor:** `composer doctor` reports the optional parser venv state without failing.

### Phase 2 Verification, 26-10-28

- **Schema:** two new migrations.
  - `create_finance_ledger_tables`
  - `move_government_finances_out_of_town_facts`: deletes revenue and expenditure rows and narrows the `town_facts.measure` check
- **Tests:** `php artisan test --compact` passed 168 tests, including 15 new ledger tests in `tests/Feature/Finances/`. They cover:
  - all-or-nothing validation
  - pending by default
  - a sign-off publishing with its report link
  - a changed value returning to pending
  - manual rows surviving a re-import
  - the latest report winning whatever the review order
  - an export and re-import round trip
  - the committed data publishing exactly the four checked 2024 pairs
  - self-sign-off refused
  - the one-verified index
- **Static checks:** PHPStan and Pint are clean.
- **Clean rebuild:** the steps of `composer db:reset` (migrate, seed and `metrics:refresh`) were run on a separate `cagayan_business_finance` database, so the shared dev database was untouched.
  - The import loaded 174 documents and 2,440 parsed figures: 8 verified and 2,432 pending.
  - The Home atlas shows exactly the four previous towns, with the same values to the cent.
  - `finances:export` reproduces `reviews.csv`.
- **Known pre-existing issue:** the demo-admin seed fails on `users.public_id`, because the seeder runs without model events. It is unrelated to this phase and was flagged as a separate task. The rebuild above ran with the demo-admin email blank.
- **Not run:** the shared dev database was not migrated, because the other checkout uses it. Teammates run `composer db:reset` after merging.

### Phase 4 Verification, 26-10-28

- **Scope:** the user approved committing the workbooks and using only the 17 archives already on disk.
  - Those are Abulug, Alcala, Allacapan and Tuguegarao for 2024, and 13 towns for 2025.
  - A browser collection of the remaining ZIPs was started by mistake and stopped at the user's request. Two files reached the user's Downloads folder and were left for the user to delete.
  - Collecting the rest is deferred to a separate session.
- **Workbook files:** `python tools/coa/run.py add-workbooks` copied 34 workbooks (5.9 MB) into `data/towns/financial-statements/`. Each copy has its checksum and the ZIP's checksum.
  - Abulug's ZIP checksum matches the one recorded at the 2024 verification.
- **Scope trim:** the user approved trimming to a business focus.
  - M21 to M23 were revised and M24 was withdrawn.
  - 13 stored lines remain. Other lines are read for checks only.
- **Parser tests:** `composer data:finances:test` passed 57 tests.
  - 11 of them cover the workbooks, with values read from the cells by hand.
  - They include hand-added sector totals and a General Fund column inside a multi-fund annex.
- **Ledger tests:** `php artisan test --compact tests/Feature/Finances` passed 15 tests after adding the `document_kind` column and the `annex_workbook` kind migration.
- **Import:** `finances:import` loaded 240 documents and 1,339 stored figures. The 8 verified sign-offs survive.

### Workspace Controls Follow-up "26-10-28"

User correction supersedes the entry button in the page header, required entry note, ghost review/cancel controls and shared greeting row. Entry now starts from the fiscal-filter panel. The drawer orders Year, Item, Basis, Amount and Source Report, uses contextual help and required markers, and sends no note. The server prohibits submitted entry notes; existing imported notes and separate review reasons are preserved. Activity Logs and Community Wishes move existing filters to the right without adding sorting behavior. Profile removes the character caption and marks Display Name required. Compact grouped navigation puts Profile and Sign Out in a bottom account dropdown; destination permissions and breadcrumbs share the existing navigation registry. The finance icon is a peso sign. Session activity and expiry warnings remain active.

Browser testing is explicitly excluded by the user for this follow-up. Automated verification results are recorded below once complete. No shared development database reset is authorized or performed.

Follow-up verification "26-10-28": `composer ci:check` passed with 223 tests and 1,342 assertions on PostGIS `cagayan_business_test`. The updated entry test asserts a stored null note; an unsolicited entry note is rejected, and rejection-reason tests remain intact. Frontend formatting/lint, TypeScript and production build passed. Source review checked shared controls, required markers, permissions, logout route method and preserved session expiry/renewal logic. No browser verification was performed, as requested. No shared development database reset, commit, merge or deployment was performed.

Bottom-bar correction "26-10-28": replaced the name/role block with a single-row Account dropdown trigger, aligned beside the appearance toggle. Profile and Sign Out remain in the menu. This supersedes showing identity details in the sidebar. Tests and browser checks were skipped for this small follow-up at the user's request.

Report-year navigation correction "26-10-28": Government Finances index now answers which towns need review in the selected report year. Available authorized years appear newest first, defaulting to the newest; rows never combine years. Removed the Report Year dropdown and redundant table column. Town/status and pagination preserve the active year. Returning to a loaded first-page selection uses local Inertia history; unrequested selections fetch missing data. This supersedes the aggregated index with a year dropdown. Automated tests and browser testing remain skipped as requested; frontend compilation checks are recorded separately.

Year-tab verification "26-10-28": frontend format/lint, TypeScript and production build passed; the changed PHP query passed syntax checking. No automated test suite or browser checks were run for this follow-up.

### Phase 5 Verification, 26-10-28

- **Base:** built on the user's committed Phase 3 redesign (`bug/finance-review-ui`, commit `dfeee4e`). The only change to it was one PHPStan list fix, made with the user's approval.
- **Schema:** one migration adds `focus_tags`, `business_type_focus_tag`, `businesses` (with a UUID `public_id`), `business_years` (sales allowed only on active years) and `stats_area_year`.
- **Not duplicated:** industry and Focus Tag totals read `stats_area_year` through SQL joins, so there is no second summary table holding the same counts.
- **Seed data:**
  - `BarangaySeeder` now stores the 49 barangay shapes.
  - `SimulatedBusinessSeeder` produces 1,508 businesses and 6,473 business-year rows for 2020 to 2026, with every point inside its barangay.
  - Seeder requirements 7 and 8 (early warning, peer median) wait on C10 and C11, which are outside this program.
- **Tests:**
  - privacy guard unit tests for P2 to P4, P6 and P7
  - a hand-computed `stats_area_year` test
  - seeder status-rule and requirement tests
  - Business Categories tests for roles, validation and audit events
- **Data member action:** the PSIC sections still need verification against the PSA classification, with a recorded date.

### Phase 6 Verification, 26-10-28

- **Schema:** one migration adds `stats_finance_year`. `database/sql/metrics/20_stats_finance_year.sql` fills it from verified figures only:
  - the derived lines: Surplus or Deficit, Other Revenue, Locally Generated Share, Budget Utilization, the two development utilizations, and revenue and expenses per resident
  - M26 change and percent change, the M25 census year, and M27 Sum of Reporting Towns rows with coverage and the matched-town change
- **Business trends:** M28 to M31 per industry and Focus Tag, summed from `stats_area_year` in SQL. Sales pass the privacy guard across each year's breakdown (P4). The public gets bands, no sales change and no sales share (P7). The Capitol gets guarded figures, the sales percent change, and the sales share, hidden when either sales figure is hidden (P6).
- **Privacy review:** the `privacy-reviewer` agent found that overlapping Focus Tags allowed subtraction in the Capitol. Fixed by the user's decision above, with tests for tag bands, a small tag group (P5), a change from a hidden year and a complementary sales share (P6), and the Capitol label. Noted and left as is: the public page forces the Simulated label on (existing behavior), and public tooltips show the reason a figure is hidden.
- **Shared front end:** `components/analytics/analytics-pickers.tsx` and `analytics-panels.tsx` serve both pages. `finance-history-chart.tsx` now draws any number of series in one unit. Series are keyed by position, because metric ids with dots made invalid CSS color variables and invisible lines.
- **Tests:** `php artisan test --compact` passed. New tests:
  - `tests/Feature/Metrics/FinanceSeriesTest.php`: gaps, a zero prior value, a variant break, matched-town sums, the per-resident census year
  - `tests/Feature/Privacy/BusinessTrendsPrivacyTest.php`: the sales sentinel, small group, P4 complementary, no wishes in the Capitol, the share and sales percent by audience
  - the rewritten `tests/Feature/Public/EconomicPerformanceTest.php`
- **Static and front end:** PHPStan (0 errors), Pint, `npm run check`, `npm run types:check` and `npm run build` passed.
- **Clean rebuild:** `migrate:fresh --seed` and `metrics:refresh` on the separate `cagayan_business_finance` database: 240 documents, 1,339 parsed figures, 8 verified, 23 finance series rows, 4,323 area rows.
- **Browser, port 8010:**
  - Public Economy and Capitol Analytics at desktop and 375px, in light and dark. No horizontal scroll.
  - Measure, town (including Sum of Reporting Towns) and Back made zero application requests and no page reload.
  - The Capitol check used a local test Admin created in the separate database, because the demo accounts have no password in `.env`.
- **Limits:** only the four signed-off 2024 pairs are published, so most finance views show Not available until more figures are signed off. The remaining COA workbooks are still to be collected.

### Reliability Review and Sign-Off, 26-10-28

- **Request:** the user asked to sign everything off, focus workbook breakdowns on Tuguegarao City, and check whether the revenue data can be trusted.
- **Cross-check:** every report's current-year revenue and expenses compared with the next report's prior-year column, all 29 towns, 2020 to 2025 reports: 217 pairs equal to the cent, 2 swapped (Gattaran 2024), 39 restated by COA, 32 with no later report. The largest restatements (Enrile 2021, Rizal 2024, Calayan 2022, Santa Ana 2020) were opened in the PDFs and are printed that way by COA. Details in [the finance data README](../../../data/towns/finances/README.md#reliability-review-26-10-28).
- **Gattaran 2024:** the summary heads its columns 2024 then 2023 but prints 2023 first. The parser now reads printed order only when the column headed with the report year repeats the previous report's own revenue and expenses to the cent and the stated change only adds up the other way round. Re-extraction changed exactly four values (Gattaran 2024 revenue and expenses for 2023 and 2024). Parser tests: 61 passed, including a regression for Gattaran and three correctly labelled reports that must never be swapped.
- **Amulung and Rizal 2025:** revenue and expenses match the PDFs to the cent and their change columns agree; only COA's own net income line disagrees. Signed off with that note.
- **Sign-off:** `reviews.csv` now holds 1,094 sign-offs. After import: 678 published figures across all 29 towns, 416 superseded earlier versions, no pending figures.
- **Honest gaps:** a missing finance value now says why: not published by COA, not in the report, workbook not yet added, or awaiting sign-off. A figure that replaced a different earlier one says "Restated in the 2022 report". A business year with no records is Not available and never bridged. None of them is ever shown as 0.
- **Tests:** new cases for each reason, the restated report year, the import's workbook filter and Gattaran's corrected value, and business year gaps.

### Consolidation, 26-10-28

- The user found three folders (`cagayan-business-kickoff`, `cagayan-business-finance`, `cagayan-business-followup`) and asked for everything in kickoff. The finance and follow-up folders were git worktrees. Both held only committed or already merged work, plus their own `.env` files.
- The finance worktree was removed. The follow-up worktree was unregistered; Windows refused to delete part of its folder, which now holds only copies of merged code and dependencies, for the user to delete.
- Kickoff now works on `feat/business-analytics`. Local branches already in `staging` or in this branch were deleted, leaving `main`, `staging` and this branch.
- The local `cagayan_business_finance` database used by port 8000 was rebuilt with the final data, and the three demo accounts were restored with `DemoAccountSeeder`.
- The one-folder rule is recorded in `CLAUDE.md`, `docs/team/workflow.md` and Claude's memory.

### Mistakes and Corrections, 26-10-27 to 26-10-28

Recorded at the user's request so later sessions do not repeat them. Each has its correction and where the rule now lives.

| Mistake | Effect | Correction and rule |
| :- | :- | :- |
| Claude started downloading COA archives through the browser without being asked (Phase 4) | Two files reached the user's Downloads folder; the user stopped it | Use only the data already on disk unless the user asks for collection. Recorded in Phase 4 Verification |
| Claude created git worktrees (`cagayan-business-finance`, and earlier `cagayan-business-followup`) for separate phases | The user could not tell where changes lived; folders split the database, `.env` and branches | Work only in `cagayan-business-kickoff`. `CLAUDE.md` step 0, `docs/team/workflow.md`, Claude's memory |
| Claude ran `migrate:fresh --seed` on `cagayan_business_finance`, believing it was an isolated check database | It was also the database behind port 8000, so the three local demo accounts were deleted | Accounts restored with `DemoAccountSeeder`. Check the database name and its users before any rebuild: `docs/team/workflow.md`, "The shared database" |
| Claude created a temporary local Admin account to check Capitol Analytics in the browser | A test account with a generated password existed in the local database until the next rebuild removed it | Use the seeded demo accounts; ask before creating any account |
| Chart series were keyed by metric ids containing dots | `--color-performance.revenue` is not a valid CSS name, so finance lines drew nothing | Series keyed by position; `docs/engineering/frontend.md`, "Charts" |
| Phase 6 first shipped without M30 (Industry Share of Town) and without the sales percent change (M31) | Approved metrics were missing from the screens | Both built with tests during the browser check |
| Focus Tag sales were exact in the Capitol | A tag minus an overlapping industry could reveal a group the privacy guard never checked | User decision: tag sales are bands for everyone; `privacy-rules.md` P4 |
| The parser trusted Gattaran 2024's column headers | The 2023 values were stored as 2024 | Parser reads printed order when two proofs hold; reliability review above; `data/towns/finances/README.md` |
| Missing values said "Not in the signed-off figures" even when no workbook had been collected | The reason on screen was not true | Separate reasons for not published, not reported, not collected and awaiting sign-off; `metrics.md`, Observation States |
| Claude ran `npm ci` while the user's `composer dev` was running | The install stopped halfway and broke the running Vite server | The user stopped Vite and reinstalled. Stop `composer dev` before installing: `docs/team/workflow.md`, "The shared database" |
| Claude asked the user to run `/open-pr` when the user had already asked for the pull request | An extra round trip for the user | When the user asks for a push or pull request in plain words, do it directly |

## Later Change

"26-10-28": the optional sign-off note was removed on the user's request. A sign-off now records the signed-in Admin automatically and the Status column shows "Signed off by Name". Rejection keeps its required reason. See design.md, "Government Finances Simplification".
