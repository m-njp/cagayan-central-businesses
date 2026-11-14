---
title: "Executive Summary Only and Faster Tests"
created: "26-11-14"
updated: "26-11-14"
status: in-verification
branch: "chore/maintainability-refresh"
base: staging
predecessor: "../archive/26-11-13-workbook-trim-all-town-summary.md"
---

# Executive Summary Only and Faster Tests

## Objective

The owner retired all workbook (XLSX) reading on "26-11-14". The Executive Summary becomes the single source of government finance figures: Financial Condition, Results of Operation, and Appropriations, Allotments and Obligations. Original workbooks stay downloadable on About the Data. The owner also asked for faster tests, run in parallel, with no browser tests and few agents. Decision: [D49](../../engineering/decisions.md#d49-executive-summary-only-and-parallel-tests).

## Findings From the Earlier Sessions

- **Excel Parser and Data Ingestion** and its nested **Workbook Parser Reliability and Inventory** session built the 516-workbook register, the intake gate, the module split and the verification layer (extractor 1.6.0 to 1.8.0). Coverage never became uniform: 232 of 521 workbooks gave no eligible line, 47 government and year archives had no XLSX at all, and Enrile's 2019 Annexes carried Solana's sheet.
- **Unclear Session** was fixing Buguey 2018 to 2020, where the national share alias accepted only "Collections" while the workbook prints "Collection", and a ₱1,212.00 Miscellaneous Income row below the surplus line was counted as revenue. Each fix moved a handful of findings and exposed a new one (Enrile 2018). That fix is abandoned with the reader.
- The committed findings held 439 Extraction Limits rows: 242 from workbooks and 197 from Executive Summaries. The workbook share is why the owner saw governments such as Santo Niño flagged on almost every line.

## Scope

- Delete the Python workbook reader (`coa/workbook/`, `workbooks.py`, `workbook_audit.py`), its commands and tests. `coa.intake` reads one PDF and refuses a workbook or archive by content.
- `finances:import` loads Executive Summary documents and figures only; no `intake` column, no history replay, no `workbook_towns`.
- A migration removes workbook figures, documents, uploads and lines from existing databases.
- Capitol upload takes the Executive Summary PDF only. Revenue Sources leaves Economy, Analytics, the guide and Report Differences.
- Freeze the removed rows as history. Keep originals, `financial-statements/index.csv`, the workbook collector and public downloads.
- Parallel tests: Pest through `php artisan test --parallel`, parser tests through pytest-xdist.

Out of scope: changes to the Executive Summary parser. Its modules and version 1.7.1 are unchanged. Expanding it is the next plan.

## Acceptance Checks

- [x] Workbook rows frozen byte for byte with a checksum manifest; Executive Summary rows untouched.
- [x] Parser reads PDFs only; workbook reader and commands removed.
- [x] Importer, upload job, parser contract, corroboration, review issues, Report Differences and Analytics hold no workbook branch.
- [x] Revenue Sources removed from config, guide, charts, upload form and finding guide.
- [x] Decisions, metrics M21, AGENTS, rules, READMEs, design, database and roadmap updated.
- [x] Parser tests carried forward with unchanged module hashes; PHPStan, Pint, required frontend checks/build and all observed failing cases verified through the full run plus targeted follow-ups.
- [ ] Fresh green aggregate CI at integration/pre-push. Deferred after isolated fixture fixes under the owner's targeted-testing correction; the last aggregate run was 491/492, not green.
- [x] Staged extraction proves the Executive Summary output is identical to the committed CSVs.
- [x] Browser checks excluded by the owner's instruction for this pass; no browser verification is claimed.

## Change History

| Date | Change | Reason |
| :- | :- | :- |
| "26-11-14" | Owner requested commit and PR and approved keeping the accumulated finance work in one PR; corrected M35's stale blanket Budget Execution coverage sentence | D48 records 2025 exceptions for Santo Niño and Enrile, so availability must be checked per source |
| "26-11-14" | Plan created; workbook reading retired; parallel tests added | Owner decision after 438 Extraction Limits findings and three sessions of layout fixes |
| "26-11-14" | Verification resumed one command at a time; four missing source identities added to downloads only | Owner approved all-original downloads after checksum audit found three AAPSI files and Peñablanca's 2019 Appendices absent from the register |
| "26-11-14" | Targeted checks made the working default in the canonical testing guide and both agent entry points | Owner rejected full-suite reruns after every change; carry forward valid results and reserve aggregate CI for integration/pre-push |
| "26-11-14" | Owner approved profiling and optimizing the real importer while retaining all three full-data import/seeder tests | Measure with two workers before/after; preload review/manual lookups without changing sign-off rules, parser output, source downloads or machine settings |
| "26-11-14" | Guide tabs, quiet scrollbar and entry placeholders added; handoff validated by a second agent | Owner asked to polish near completion; supersedes the guide's earlier "no tabs" rule in design.md |
| "26-11-14" | Consolidated the original handoff with completed verification, source downloads, importer optimization and development-server status | Owner requested a current handoff for the next developer; distinguish fresh targeted evidence from carried-forward checks and the pending aggregate gate |

## Import Optimization Follow-up

- [x] Capture preservation checksums and a matched two-worker baseline for exactly the committed-data import and both full-seeder tests.
- [x] Measure import phases, SQL counts/durations and peak memory without storing bindings or row values.
- [x] Preload importer review/manual lookups within the existing transaction; preserve ordering, freshness, locks, sign-off service and warnings.
- [x] Add focused query-growth and replay correctness coverage without removing existing tests or assertions.
- [x] Run relevant import/sign-off/publication/review cases, scoped Pint and PHPStan.
- [x] Repeat the same three-test measurement; require improved measured cost and matching full-ledger/statistics fingerprints.
- [x] Remove temporary profiling hooks; verify parser, canonical outputs, frozen history, registers, originals and download code are unchanged; report actual results.

Owner clarification during implementation: preserve the blur overlay, Python phase events and existing 0.5-second upload pacing. Development iterations use small fixtures or a selected Executive Summary PDF. Only the approved before/after benchmarks use all committed finance data, in isolated test databases. This optimization adds no bulk-import selector or UI pacing change.

Optimization completed "26-11-14": matched two-worker command wall time **456.851s to 379.458s**, saving **77.393s (16.9%)**. Each full-data case eliminates 4,509 SQL query events. All three retain the same 28 assertions and matching normalized hashes for 5,174 ledger rows and 2,828 statistics rows. The cost is 14 to 16 MiB more peak PHP memory (maximum 100 MiB). The reseed's unchanged metric refresh still takes 188.618s. Final targeted verification passed **104 tests / 717 assertions**, including one real PDF queue case, in 25.838s wall; scoped Pint/PHPStan pass. All 1,482 preservation checksums match and temporary probes are removed. Detailed phases, SQL-event timing limits, corrected profiling setup and retained coverage are in the [timing report](../../engineering/executive-summary-verification.md#import-lookup-optimization). No full CI, browser test, province-wide development reload or parser suite was repeated for this optimization. The original integration/pre-push gate remains pending separately.

Final review follow-up: an uppercase manual-UUID fixture exposed case normalization needed by the preloaded map. The corrected lowercase/uppercase pair passed 2 tests / 8 assertions; scoped Pint/PHPStan passed again. Only that affected pair was rerun. All benchmark cases have no manual figures, so their matched evidence remains valid.

## Guide and Entry Form Polish

Owner request "26-11-14": validate the continuation handoff, give the public Government Finances help a left panel of tabs in place of one long scroll, use a less obvious scrollbar, and add placeholders to the manual input forms.

- [x] Handoff validated against the repository: 5,174 figures, 588 findings, 348 documents, 4,510 sign-offs, 520 downloads with matching checksums, both history manifests, migration run.
- [x] Guide sections are tabs at the left, one at a time; How a Budget Is Spent sits inside Budget Execution; row labels and N/A cells open the matching tab; arrow keys move between tabs.
- [x] `scrollbar-quiet` utility for scroll areas inside overlays, used by the guide.
- [x] Placeholders in the finance report grid, the population table and their source and reason fields.
- [x] `design.md` updated: the guide's "no tabs" rule is replaced by the owner's request.
- [x] `npm run check`, `npm run types:check`, `npm run build`, 34 Node tests; guide checked in the browser on Economy at desktop and phone width, no console errors.
- [x] Browser pass as the local demo Admin: guide on Capitol Analytics opens from a row label on the right tab, arrow and Home keys move between tabs; Enter Report (Abulug 2025) and Population (Tuguegarao City) show the placeholders. Nothing was saved. Zero-like placeholders were replaced with words after this pass.
- [x] Shared `components/guide-dialog.tsx` extracted; the Government Finances Guide and the Report Differences Guide both use it. A finding name opens its category tab, scrolls to the finding and marks it. Lint, types, build and Node tests pass. Not checked in the browser, at the owner's instruction.
- [x] Report Differences rebuilt as one table per report year with a worded Review column, the year in the right-hand filter panel and row links; the grid, dots, category tabs and key line are gone. The finding highlight is a ring on the entry. Lint, types, build, 34 Node tests and `ReportDifferencesTest` (5) pass. Browser-checked before the year moved into the panel; the final layout was not rechecked, at the owner's instruction.
- [ ] `/design-check` and a light mode comparison.

## Current Handoff

### Commit and PR Review "26-11-14"

The owner approved one accumulated finance PR. Current `origin/staging` was merged without application-file conflicts. M35's stale blanket claim about 2023 and later Budget Execution was corrected with owner approval to match D48's source-specific exceptions.

Fresh preservation checks: all 520 registered workbook originals and all six files in the two frozen-history manifests match their SHA-256 values. `.gitignore`, Docker, npm dependencies and Composer dependencies are unchanged; the Composer edits add commands and parallel test execution. The pinned Python development dependencies are approved in D49.

Privacy review against P1 to P15: P1, P7, P8, P10, P13 and P14 hold for the changed serialization, public downloads and existing simulated-business rendering. P2 to P6, P9, P11, P12 and P15 have no changed business behavior: this slice adds public government finance data and COA sources, not business records, sales exports, wishes or registry publication. No new model serialization, private business fields, pending finance amounts or reviewer identities reach the public endpoint. The full PHP suite includes the existing privacy coverage; no architecture test directory exists.

Static design review covered shared surfaces, tabbed guides, N/A reasons, report-year filtering, Wayfinder navigation and loaded-data history paths. No blocking finding was identified in this pass. Final light/dark comparison, screenshots and fresh browser request-count measurements remain open; earlier browser observations retain the limits recorded above.

Fresh pre-commit verification: `composer test:fast` passed all 497 tests with 3,678 assertions (458.336 seconds reported by Pest); `composer data:finances:test` passed all 694 tests in 29.21 seconds. The first sandboxed PHP attempt could not connect to local PostgreSQL; the successful run used local database access. `git diff --check` passed. The normal commit and pre-push hooks still enforce frontend checks, PHP static checks, build and aggregate CI.

The [consolidated Executive Summary Only handoff](../../engineering/executive-summary-handoff.md) is the entry point for the next developer. It records inherited D49 scope separately from this session's changes, known limits, targeted commands, local-only artifacts and the remaining integration gate. `composer dev` was started at about 15:05 PHT on "26-11-14"; HTTP 200 was rechecked at 19:58 PHT, with Vite on port 5173. Reuse existing services and recheck transient server state before starting another instance. This documentation refresh ran link and whitespace checks only; no application tests, build or import was needed.

## Verification

See the [verification and timing report](../../engineering/executive-summary-verification.md). All 25 staged output files match canonical CSVs. Both history manifests pass, and all parser modules match the continuation's starting hashes; the handoff's 694-test parser result is retained. The retirement migration is applied; all 5,174 Executive Summary figure/review checksums match before and after migration, import and final verification. The import loaded 348 documents and 5,174 figures with no new, changed-to-Pending or removed figures (85.602 seconds).

Initial full Pest profile: 490 tests, 484 passed, two failed assertions and four fixture errors (581.845 seconds wall). These were fixed; targeted import/Economy/download checks passed 76 tests in 20.397 seconds. The latest aggregate CI took 486.608 seconds: frontend checks, 33 Node tests, Pint and PHPStan passed, then Pest passed 491/492 in 417.797 seconds with one additional Windows fixture error. That fixture was fixed; all 29 evidence-review tests passed in 22.818 seconds. After the source-formatter correction, all 13 analytics tests passed in 2.464 seconds. Final frontend check, TypeScript, build, scoped Pint and the newly built login-path security test pass. No full green aggregate run is claimed.

Actual source verification: all 520 individual workbook download lookups pass; the complete archive contains 868 reports and verifies every member checksum (56.249 seconds). All 521 collection copies are preserved; one identical duplicate shares a download. The old 516 register rows are byte-preserved, with four new rows appended. Scoped static design/privacy review found no remaining blocker; browser checks were excluded by the owner. Nothing was committed or pushed. The remaining plan checkbox is the integration/pre-push aggregate gate, not a request to rerun every test after documentation edits.
