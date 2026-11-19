# Executive Summary Only Handoff

Updated "26-11-14" for the next developer or AI agent. This consolidates the original Executive Summary Only handoff with this session's verification, source-download additions, importer optimization and development-server startup.

Read the [completed plan](../plan/archive/26-11-14-executive-summary-only.md), [D49](decisions.md#d49-executive-summary-only-and-parallel-tests), [verification and timing report](executive-summary-verification.md), and [targeted testing policy](testing.md#choose-checks-from-the-change).

## Current State

- Branch: `chore/maintainability-refresh`. Work only in this checkout. The accumulated finance work is committed as `0d573dd` and pushed with all normal hooks passing. The owner requested a PR into `staging`; no merge or deployment is authorized.
- Executive Summary PDFs are the sole source of finance figures. Workbook reading, workbook intake and Revenue Sources are retired.
- The inherited retirement migration is applied. The development ledger contains 348 Executive Summary documents and 5,174 parsed figures; its PDF figures and sign-offs were preserved.
- All 520 distinct workbook contents are downloadable, representing 521 preserved original collection copies with one byte-identical duplicate. The complete ZIP contains 868 reports: 348 PDFs and 520 workbooks.
- The actual CSV importer is faster: the matched three-test benchmark improved from 7m37s to 6m19s (16.9%). All three full-data test bodies and their 28 assertions remain.
- Integration is complete: production build and full `composer ci:check` passed through the normal pre-push hook for `0d573dd`. Standalone runs passed 497 PHP tests with 3,678 assertions and 694 parser tests. The completed plan is archived; final light/dark comparison and screenshots remain PR-review limitations.
- Parser version 1.7.1, its financial output, the blur overlay, Python phase events and 0.5-second upload pacing are unchanged.

## Owner Instructions That Carry Forward

1. Do not restore a workbook reader, workbook upload, `intake` column, `workbook_towns`, history replay or Revenue Sources without a new owner decision. The earlier Buguey alias fix and other workbook parsing repairs are abandoned.
2. Preserve original reports, existing register entries and frozen history. The four additional download entries were expressly approved; they do not authorize extraction or ingestion of those workbooks.
3. Do not change Executive Summary output without verifying each changed value against its original PDF page. Government finance figures are not private-business sales or an investment score. Unresolved discrepancies remain for human review.
4. Use targeted tests during development and carry forward valid results. Never rerun every case solely for an isolated fixture, assertion or documentation edit. Run one test command at a time; parallel workers inside it are allowed.
5. Use small fixtures or one selected municipality/year PDF during development. Bulk ledger imports are for necessary seeding, integration or justified benchmarks. No new municipality/year bulk-import flags were added.
6. Preserve the existing blur progress experience. Do not slow CSV database work merely to hold a progress screen open. The single-PDF parser already has its own phase events and configured pacing.
7. Preserve all other sessions' edits. No reset, stash, worktree, second clone, destructive database reset, hook bypass, commit or push is authorized by this handoff.
8. Browser checks were excluded for this pass. Do not describe static review or request tests as visual/browser verification.

## Inherited D49 Implementation

The original handoff had already implemented retirement. This session verified and completed that work; it did not originate every removal in the shared diff.

| Area | Retired Behavior |
| :- | :- |
| Python | `coa/workbook/`, `workbooks.py`, `workbook_audit.py`, their reader/audit/promotion commands and associated workbook-reading tests |
| Finance CSV intake | Workbook lines and dependent derived lines, the `intake` column, workbook documents/figures in active CSVs and replay of frozen history |
| Laravel | Workbook/ZIP upload and extraction branches, `workbook_towns`, workbook-specific corroboration and finding paths |
| Interface | Revenue Sources, workbook report-kind selection and related guide/finding copy |

Keep the workbook collector, archive collection helpers, source register, public individual downloads and ZIP download. Collection and downloading are separate from reading figures. Legacy `FinanceDocumentKind` workbook cases remain, but no active ledger document uses them. The source library now owns four download labels: Financial Statements, General Fund Annex, AAPSI and Appendices. The latter two are source-library kinds only, not ledger or parser kinds.

## Work Completed in This Session

### Verification and Fixture Repairs

- Fixed the seeder assertion that rejected numbered parallel test databases.
- Corrected an Economy test that requested `performance.expenses` but expected retired `revenue.tax`.
- Gave generated import fixtures fresh directories. Moved a diagnostic CSV aside before creating a directory at its path, avoiding observed Windows Permission denied failures.
- Recorded these fixture and profiling lessons in the testing guide and `.claude/rules/testing.md`.
- Made targeted testing the canonical development workflow, linked from `AGENTS.md`, `CLAUDE.md`, testing rules and team workflow. Full CI remains the integration/pre-push gate.

### Source Downloads

The audit found four distinct originals preserved on disk but absent from the previous 516-entry download register. At the owner's direction, added the 2023 Ballesteros, Lasam and Santa Teresita AAPSI files and Peñablanca's 2019 Appendices to downloads only. Original collection files were not altered; the previous 516 register rows are byte-preserved, with four rows appended.

`PublicWorkbooks`, `FinanceEvidenceQuery`, citation types and the source formatter now support the source-only labels. The formatter identifies them as XLSX downloads rather than PDFs. Source and analytics tests cover the labels and access checks.

### Real Importer Optimization

`app/Console/Commands/ImportFinancesCommand.php` now preloads parsed review candidates once within the existing transaction, keyed by document, line, integer fiscal year and basis. The database's `finance_figures_parsed_unique` constraint enforces that identity. The importer selects only fields needed for replay/sign-off.

Manual figures are preloaded by supplied public UUIDs after parsed reviews. A later correction in the same group refreshes its model because an earlier sign-off can have superseded it. UUID lookup keys are normalized to PostgreSQL's lowercase representation, preserving accepted uppercase CSV UUIDs. Maps are local to one import invocation.

`FinanceSignOff`, winner queries, row locks, transactions, reviewer checks, report ordering, manual precedence, amount comparisons, warnings and batched inserts remain intact. Both complete seeder tests still invoke the real seeder and importer. No cached seeded database, mocked importer, reduced corpus, skipped existing case or removed assertion was used.

Regression additions cover bounded lookup growth, competing corrections, repeated verify/reject/verify decisions, integer fiscal-year keys, uppercase/lowercase UUIDs and missing/changed-value warnings. Temporary profiling hooks are removed; `tests/Pest.php` was restored byte for byte.

## Data and Preservation

The inherited migration `2026_11_14_023353_retire_workbook_finance_records.php` removes workbook ledger records and dependent statistics. Its `down()` restores nothing. It was applied on the development database, followed by `composer data:finances` in 85.602 seconds:

- 348 documents and 5,174 parsed figures loaded.
- 0 new figures, 0 changed back to Pending, 0 removed, 0 manual figures.
- 2,379 figures verified; other figures retain their existing review states.
- PDF figure/review checksums matched before migration, after migration, after reload and final verification. Stored private workbook-upload files remain on disk.

Frozen history is never imported:

| History Folder | Preserved Rows |
| :- | :- |
| `data/towns/finances/history/26-11-14-workbook-reading-retirement/` | 1,217 figures, 423 findings, 1,381 reviews, 516 documents, with checksum manifest |
| `data/towns/finances/history/26-11-14-workbook-retirement/` | Earlier 1,062 figures and 3,577 findings, with checksum manifest |

The source audit verified every individual workbook lookup and the complete ZIP: **868 reports, 184,193,021 bytes, no failures, 56.249 seconds**. Final optimization preservation matched all **1,482 files**, including parser modules, canonical output, originals, registers, history, download code, sign-off service, seeder/test bodies, upload/progress code and pacing configuration.

Active PDF findings remain: 197 Extraction Limits, 203 Between Reports and 188 Within the Report. Unchanged output and passing tests do not establish that every unresolved source figure is correct. Human review remains required.

## Performance Evidence

The matched runs used exactly the same three tests, two workers, dataset and instrumentation. Both passed 3 tests and 28 assertions. Combined command wall time fell from **456.851s to 379.458s**, saving **77.393s (16.9%)**.

| Case | JUnit Seconds Before | JUnit Seconds After |
| :- | -: | -: |
| Committed COA import | 125.269 | 96.264 |
| Full seeder creating demo accounts | 126.388 | 97.348 |
| Full reseed preserving an existing account | 325.656 | 277.475 |

JUnit includes setup/hooks; profiled body times were 121.902 to 92.795, 122.849 to 94.027, and 324.545 to 276.341 seconds respectively. Worker times overlap and cannot be summed as elapsed wall time.

Each case replaced 4,510 individual review-candidate lookups with one preload, removing **4,509 Laravel query events**. Review replay fell from 112–128 seconds to about 84 seconds. The cost is **14–16 MiB more peak PHP memory**, with a maximum measured peak of 100 MiB.

The reseed's metric rebuild remains the major long tail: **191.477s before, 188.618s after**. Its SQL was not changed. Laravel SQL-event totals exclude direct PDO execution and transaction-control commands; phase timers include the full metric rebuild. Do not report event totals as complete database time or attribute all run variation to the lookup change.

One initial uninstrumented run took 438.861s because the profiling hook was file-scoped. A smoke test verified the corrected hooks before the matched pair. An invalid new duplicate-observation fixture exposed the existing uniqueness constraint; the unnecessary fixture and fallback were removed. No pre-existing coverage was removed. See the [detailed profiling report](executive-summary-verification.md#import-lookup-optimization) for query counts, phase tables, memory and corrections.

## Verification Results and Limits

| Evidence | Result and Provenance |
| :- | :- |
| Matched full-data benchmark | Fresh before/after: 3 tests, same 28 assertions; all three normalized 5,174-row ledger and 2,828-row statistics hashes match |
| Targeted importer/sign-off/publication/review/evidence and one real PDF queue case | 104 tests, 717 assertions passed; 25.838s wall (24.538s Pest) after probe removal |
| Final UUID follow-up | Later isolated correction: 2 cases, 8 assertions passed; 2.588s Pest; scoped Pint/PHPStan passed again |
| Scoped Pint and PHPStan | Passed for the optimized importer and changed test formatting; no whole-application static-analysis claim for this final backend-only slice |
| Staged extraction | Earlier 348-PDF extraction: all 25 output files match canonical CSVs and saved checksums; carried forward with unchanged parser/source hashes |
| Python parser suite | Earlier 694 passed; historical 28s parallel versus 115s serial. Carried forward, not rerun for the unrelated importer change |
| Frontend verification | Earlier full 33 Node tests passed; later targeted 13 analytics cases passed after source-kind changes. Formatting, TypeScript and production build passed for those frontend edits |
| Historical aggregate CI before integration | 486.608s wall; Pest 491/492 in 417.797s, with one fixture error subsequently corrected by 29 targeted evidence tests in 22.818s |
| Source ZIP and preservation | Actual archive/lookup check passed; final 1,482-file preservation passed |
| Browser verification | Excluded by owner; none claimed |

The 104-test run and later UUID pair are separate results, not a claimed fresh combined 105-test run. The full benchmark contains no manual figures, so the isolated UUID correction did not invalidate its exercised paths. The later commit/PR integration gate passed for `0d573dd`; historical measurements above are unchanged. Do not rerun tests solely for documentation edits, while preserving mandatory Git hooks.

## Independent Validation and Interface Polish

A second agent checked this handoff against the repository on "26-11-14", then made the owner's requested interface changes.

Confirmed from the files and database, not from this document:

- `extracted/` holds 5,174 figures, all `executive_summary`. `issues/` holds 588 findings: 197 Extraction Limits, 203 Between Reports and 188 Within the Report. `documents.csv` holds 348 available Executive Summaries and `reviews.csv` 4,510 sign-offs.
- `financial-statements/index.csv` lists 520 files (238 Financial Statements, 278 Annexes, three AAPSI, one Appendices). Every file exists and matches its SHA-256.
- Both history manifests match their files. `lines.csv` has no `intake` column. The retirement migration shows as run. The extractor version is 1.7.1.
- No workbook branch remains in the importer, upload job, parser stream, `coa.intake` or `config/cagayan.php`.

Not rechecked: the benchmark timings, the 104-test run and the ZIP build. Those stand on the session's own evidence. The Node suite now has 34 tests, not 33.

Interface changes, owner request "26-11-14":

- **Government Finances Guide.** Sections are tabs at the left (a scrolling row on phones), one shown at a time, in `components/analytics/finance-guide.tsx`. How a Budget Is Spent sits inside Budget Execution. Row labels and N/A cells open the matching tab. This replaces the earlier "no tabs" rule in `design.md`. Do not turn it back into one long page.
- **Shared guide component.** `components/guide-dialog.tsx` (`GuideDialog`, `GuideSection`, `GUIDE_TARGET`) now draws both the Government Finances Guide and the Report Differences Guide. In Report Differences, a finding's name opens the guide on its category tab, scrolled to that finding and marked. Reuse it for any new guide (`design.md`, "Guides"). Lint, types, build and the Node tests pass; this step was not checked in the browser, at the owner's instruction.
- **Report Differences page.** Rebuilt "26-11-14" after the owner found the municipality by year grid overwhelming and its green dots unexplained. It is now one table for the chosen report year with a worded Review column, the year in the right-hand filter panel, and rows that open the findings (`design.md`, "Report Differences"). The URL keeps `reportYear`, `town` and `year`; the old `family` filter is gone from the page. `RowLink` gained an optional `onClick` for rows that open in place. Checked in the browser before the year moved into the filter panel; the final layout was not rechecked, at the owner's instruction.
- **Vite on this laptop can miss a file change.** The dev server kept serving the old Report Differences Guide after a rewrite, so the owner saw no change. If a screen does not update, compare `http://localhost:5173/resources/js/<file>` with the file on disk and save the file again.
- **Scrollbar.** The `scrollbar-quiet` utility in `resources/css/app.css` is for scroll areas inside overlays: no track, a faint thumb that darkens on hover or focus.
- **Manual entry.** Placeholders show the expected format: "Enter amount" or "Enter count" in entry cells (never a zero, which would read as a stored figure), "e.g. 1" for Page, the COA and PSA site addresses for source links, and a prompt for Reason for Correction. Page and COA Link mark themselves invalid on a server error.

Checked: `npm run check`, `npm run types:check`, `npm run build`, 34 Node tests, and the guide in the browser on public Economy at desktop and phone width with no console errors. This one browser check was done at the owner's request for this change; the earlier exclusion still describes the retirement work. A follow-up browser pass as the local demo Admin covered the guide on Capitol Analytics (a row label opens its tab at the line; arrow and Home keys move between tabs), Enter Report on Abulug 2025 and Population for Tuguegarao City; nothing was saved. Not checked: light mode side by side and `/design-check`.

## Development Server

`composer dev` was started at about 15:05 PHT on "26-11-14", launching Laravel, the queue worker and Vite. The stale `public/hot` was cleared by the existing guard. Laravel HTTP 200 was rechecked at **19:58 PHT**; the hot file points to Vite on port 5173. Queue startup was observed, but no separate fresh queue-health claim is made at 19:58.

Use [http://localhost:8000](http://localhost:8000) and reuse the running services. Server state is transient: check before starting anything, and never launch a second server or assume the originating chat's process handle is available elsewhere.

## Start Here Next

1. Read this handoff, the completed archived plan and targeted testing policy. Inspect current Git status before new work. Keep the existing checkout and branch rules.
2. For development, upload one selected municipality/year Executive Summary or use small fixtures. The bulk CSV importer is not the blur-overlay pipeline; no `--town` or `--year` bulk selector was added.
3. Run only coverage affected by new changes. A complete `FinanceImportTest` includes the expensive committed-data case. Example focused selections, run individually:

```powershell
php artisan test --compact tests/Feature/Finances/FinanceImportTest.php --filter="keeps the last manual correction"
php artisan test --parallel --processes=2 --filter="^(?!.*imports.the.committed).*FinanceImportTest"
php artisan test --compact tests/Feature/FinanceParsingProgressTest.php --filter="executes a real Executive Summary"
```

4. The retirement integration gate passed and its plan is archived. New changes use targeted tests and normal commit/pre-push hooks; never bypass hooks or overlap separate test commands. Outstanding final visual checks belong to PR review, not an unfinished workbook-reading implementation.
5. Further metric-rebuild optimization is a separate measured follow-up. Preserve metric definitions and full coverage. Do not invent a speedup or change machine settings to manufacture one.
6. The owner's separate future direction is improving the Executive Summary parser and its displayed Financial Condition, Results of Operation and budget lines. That expansion was not implemented here and requires original-page verification for every changed figure.

Local artifacts are under ignored `storage/app/coa-import-optimization/` and `storage/app/executive-summary-*`, including JUnit, phase profiles, comparison and preservation JSON. They may not travel to another checkout. The versioned [verification report](executive-summary-verification.md) contains the essential evidence and methodology; do not claim to have rerun missing artifacts.

Earlier workbook research is preserved in the [superseded plan](../plan/archive/26-11-13-workbook-trim-all-town-summary.md) and [workbook handoff](coa-workbook-handoff.md). They explain the retirement and are not instructions to restore extraction.
