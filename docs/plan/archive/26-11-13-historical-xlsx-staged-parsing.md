---
title: "Historical XLSX Staged Parsing and Format Coverage"
created: "26-11-13"
updated: "26-11-13"
status: completed
branch: "chore/maintainability-refresh"
base: staging
predecessor: "../archive/26-11-13-coa-excel-collection-2014-2019.md"
---

# Historical XLSX Staged Parsing and Format Coverage

## Objective and Authorization

On "26-11-13" the owner requested the improved parser on all 221 collected 2014–2019 XLSX originals, the same arithmetic/Executive Summary/consecutive-report checks, unchanged sources and no imports. Also document municipality/year pairs without XLSX and investigate possible alternative formats. This follows the [historical collection](../archive/26-11-13-coa-excel-collection-2014-2019.md) and [parser hardening](../archive/26-11-13-xlsx-parser-hardening.md).

## Scope and Acceptance

- [x] Reconcile all 221 originals against the collection and protect their hashes, the earlier 300 originals and canonical finance/source registers.
- [x] Inspect document kinds and historical filename variations before final parsing; do not mistake a filename miss for irrelevant content.
- [x] Run shared read-only readers with up to four workers, durable outcomes/evidence and fresh ignored outputs; preserve metric definitions, fund/year safeguards and check-only filtering.
- [x] Apply workbook arithmetic, compatible Executive Summary comparisons and consecutive-report checks, recording the coverage and limitations of those checks.
- [x] Reconcile 221/221 per-file outcomes and investigate zero/withheld/unsupported results across worksheet contents.
- [x] Document all 40 no-XLSX municipality/year outcomes, with actual alternative archive formats where source ZIP inspection is available. Never infer XLS/PDF formats merely from absence of XLSX.
- [x] Reopen source cells for changed candidates and document unresolved extraction/source findings without declaring financial sign-off.
- [x] Verify source preservation, successful checkpoint reuse, appropriate regressions/checks and documentation links; archive after acceptance passes.

## Boundaries

Sources: `storage/app/source-review/coa/excel-collection-2014-2019/`. Staged parsing goes to a separate fresh directory under ignored source-review storage. No workbook saves/recalculation, financial import, sign-off, metric refresh, publication, UI changes, database changes, new packages or commits. M22 remains General Fund only and M23 stays check-only. Unreadable or omitted components are extraction limits, not proven source arithmetic errors.

Capture the current parser's historical baseline before any routing adjustment. The existing 2020–2025 baseline is a different input inventory and must not be used as the historical baseline. Re-inspect only the 40 no-XLSX source ZIPs for member metadata, matching their existing source checksums before claiming confirmed formats. Do not extract or convert their excluded contents. Record findings durably before deleting those temporary downloads; retain changed/failed sources as unresolved evidence.

## Initial Findings and Dated Progress

| Date | Finding or action | Evidence |
| :- | :- | :- |
| "26-11-13" | Recorded owner scope before starting the run | 221 retained files; 40 explicit no-XLSX collection outcomes |
| "26-11-13" | Filename inventory identified one routing miss | 93 prefix-recognized FS, 127 Annex and `0-_Lasam2018_Part1-FS.xlsx`; inspect worksheet content before choosing its reader |
| "26-11-13" | Captured the unchanged 1.5.0 historical baseline | 221 outcomes: 36 parsed, 49 partial, 135 zero-eligible, one filename-routing miss; 702 eligible and 1,410 check-only observations |
| "26-11-13" | Inspected older statement layouts before changing readers | Lasam's workbook explicitly states consolidated performance and 2018; several 2014 FS sheets explicitly say consolidated Statement(s) of Income and Expenses; Gonzaga 2015 has explicit period 2015 and a single Amount header |

### Narrow Historical Layout Support

Implement version 1.5.1 with regressions: recognize the observed legacy printed Statement(s) of Income and Expenses heading as an FS performance candidate, still requiring existing catalog labels and the same fund/year safeguards. Where no numeric year header exists, accept only an explicitly matching statement period plus one explicit Amount/Amounts header and exactly one populated candidate amount column. Multiple populated columns or conflicting periods remain withheld. Recognize an explicit Part1-FS suffix when a filename prefix is malformed, while shared readers still confirm statement contents. This does not add metric aliases/definitions, repair source years, invent missing values or change publication eligibility.

The first historical draft also exposed incomplete cross-document comparability: legacy statements may match only a few catalog components without a recognized complete subtotal. A component subset must not be treated as the workbook's total. Final Executive Summary checks will use a readable printed Total Revenue or uniquely identified Total Expenses cell, preserve its citation, and record per-file/per-metric agreement/difference or withholding coverage. The existing global blocking-reading-error safeguard and General Fund exclusion remain. Missing/ambiguous/unreadable totals are comparison limits, not source differences. Printed expense totals are evidence only, never a new publication/import metric.

Original-cell review of the two duplicate-component findings found Pamplona 2019 Performance!E20/G20 and E22/G22 (two Financial Expenses rows) and Santo Niño 2019 Financial Performance!G10/J10 and G11/J11 (IRA and Share from National Taxes). These can represent separate source components that collide in one catalog line. Keep the values withheld but classify non-aggregate duplicate mappings as `duplicate_values_unresolved` extraction limits; only complete conflicting aggregate rows in the totals reader retain `conflicting_values` source classification. This changes finding attribution, not accepted amounts or metric definitions. Strengthen the regression to assert the reading family and unchanged withholding behavior.

Final content reconciliation found Lasam 2018's malformed-prefix FS and normal-prefix FS have the same SHA-256 and values. Both originals/per-file outcomes remain intact, but the 744 eligible per-file observations represent 732 unique-content observations; the malformed filename adds 12 duplicate eligible observations, not new financial facts. Peñablanca 2019 has two distinct Annex files, both zero-eligible. Across 174 government/year archives, 93 have at least one FS XLSX; 81 have no collected FS XLSX. Document the 188 per-FS-file comparison opportunities separately from municipality/year availability.

The baseline is `storage/app/source-review/coa/xlsx-historical-261113-baseline/`. Final outputs must be fresh and compared to this same 221-file inventory. No-XLSX archive-format evidence is separate under `xlsx-historical-formats-261113/`; temporary reinspection ZIPs must match the original collection checksum and receive durable member-metadata evidence before deletion, with no member extraction/conversion.

### PDF Scope Confirmation

The owner asked explicitly whether Executive Summary/PDF behavior changed. SHA-256 comparison against this task's captured historical baseline confirms `summary.py`, `numbers.py`, `checks.py`, `lines.py` and `archives.py` are unchanged. The visible Git diff in `summary.py` predates this historical task and only classifies workbook-specific extraction issue names; it does not alter PDF extraction. Current changes are in the shared XLSX reader and the staged workbook audit/comparison path. The shared `EXTRACTOR_VERSION` metadata is bumped to 1.5.1, so a future PDF parse would carry that version string even though its reader algorithm is unchanged. Reader improvements apply to all XLSX inputs, not just the new 221 files. Existing Executive Summary CSVs are read-only comparison references; no canonical PDFs are reparsed or finance records imported by this run.

### No-XLSX Format Evidence Limits

The completed collection proves that 40 municipality/year ZIPs contained no `.xlsx`; its deliberate cleanup did not retain excluded member filenames. A controlled source reinspection was prepared with all 40 original ZIP checksums and canonical identities. The first browser download (Gattaran 2014) timed out and produced no file. A fresh official COA tab loaded the listing but did not complete the requested municipal branch; a direct read-only request to the same previously observed ZIP URL returned HTTP403. No replacement ZIP bytes or alternate-format member evidence were obtained. The coverage table therefore states alternate formats **unconfirmed**, rather than guessing XLS/PDF from absent XLSX. No originals, excluded members or failed/changed source ZIPs were deleted by this unsuccessful reinspection. If access returns, the local inspection helper enforces old source hashes, archive bounds/CRCs, durable evidence and cleanup without extracting any member.

## Verification and Handoff

Final staging: `storage/app/source-review/coa/xlsx-historical-261113-complete/`. Supplemental review and the full no-XLSX list: `storage/app/source-review/coa/xlsx-historical-review-261113/REPORT.md`. No-XLSX CSV: `storage/app/source-review/coa/xlsx-historical-formats-261113/no-xlsx-coverage.csv`.

### Run Outcomes

221/221 originals have documented outcomes: 49 parsed, 50 partial and 122 zero-eligible, with no file-level failures or unclassified kinds. The 94 FS files and 127 Annex files emit 744 eligible per-file observations (732 unique-content observations) and 1,455 check-only observations. The baseline had 702 eligible and 1,410 check-only observations. All 87 added per-file observations have original-cell input/sum support, including 42 eligible observations; 12 eligible additions belong to the byte-identical Lasam copy and do not add unique financial facts. No observation is signed off.

| Report Year | XLSX Files | FS Files | Annex Files | Eligible Per-File Observations |
| :- | -: | -: | -: | -: |
| 2019 | 45 | 18 | 27 | 190 |
| 2018 | 46 | 21 | 25 | 217 |
| 2017 | 12 | 6 | 6 | 65 |
| 2016 | 43 | 18 | 25 | 194 |
| 2015 | 35 | 15 | 20 | 34 |
| 2014 | 40 | 16 | 24 | 44 |
| Total | 221 | 94 | 127 | 744 |

All 122 zero-eligible files were inspected across stored worksheets, rows/columns and two/three adjacent text-cell forms. They comprise 112 Annex and 10 FS files. Of the Annex files, 85 have no exact supported detailed M22 label in those scans, while 27 contain matching labels but still have selection/year/fund/amount limits. Unrecognized semantics, embedded pictures/objects and unsupported layouts remain limitations rather than proof of absent government figures. The remaining FS cases are Buguey 2014; Ballesteros, Buguey, Camalaniugan, Iguig, Lal-Lo, Pamplona and Sanchez Mira 2015; and Buguey and Santa Ana 2016. Missing/ambiguous year columns, unreadable components, unsupported statement layouts or sheet ambiguity remain withheld. The supplemental JSON retains the detailed per-sheet evidence and issue lists.

### Executive Summary and Consecutive Checks

Of 188 per-FS-file income/expenses opportunities: 30 agree within the existing one-peso tolerance, four differ, 120 are blocked by extraction limits, and 34 have no unique readable printed total. The duplicate original contributes duplicate file opportunities; these are not 188 independent government/year validations. Across all 174 source archives, 93 government/years have an FS XLSX and 81 do not. General Fund Annex details never enter consolidated comparisons. `summary-comparison-coverage.json` retains every eligibility decision, workbook cell and Executive Summary page/hash reference.

| Report Year | Municipality | Metric | Workbook Cell | Workbook Printed Total | Executive Summary Total | Difference |
| :- | :- | :- | :- | -: | -: | -: |
| 2014 | Calayan | Expenses | IS!D21 | 70,269,065.82 | 72,090,092.85 | -1,821,027.03 |
| 2014 | Camalaniugan | Expenses | IS!C18 | 62,527,727.96 | 61,960,978.96 | 566,749.00 |
| 2014 | Claveria | Expenses | IS!I19 | 63,358,293.97 | 63,396,793.97 | -38,500.00 |
| 2014 | Santa Praxedes | Expenses | Financial Performance!E19 | 34,559,911.33 | 35,076,211.33 | -516,300.00 |

These are review findings against existing Executive Summary observations, not a declaration that either source is wrong. Camalaniugan's total is a cached `SUM(C15:C17)`; it was not recalculated. The run also records 15 restatement findings and one repeated-prior-year finding from consecutive available reports, grouped by municipality/document kind/basis/definition variant. Missing reports cannot establish a restatement. No complete report-arithmetic error was established; unmatched/incomplete components and duplicate component mappings remain reading limits.

### Confirmed No-XLSX Municipality Years

All 40 source ZIPs were successfully collected and validated with `no_xlsx` outcomes. They are not missing downloads or unpublished reports. Alternate member formats are **unconfirmed** for every row: excluded filenames were not preserved during the authorized XLSX-only cleanup, and the later reinspection attempt could not acquire source bytes. Do not label these `.xls`, `.xlsm` or PDF-only without new member evidence. Source URLs, original archive hashes and canonical identities are retained in the supplemental machine-readable table.

| Report Year | Municipality |
| :- | :- |
| 2019 | Amulung |
| 2019 | Santa Ana |
| 2018 | Amulung |
| 2018 | Santa Ana |
| 2017 | Alcala |
| 2017 | Amulung |
| 2017 | Baggao |
| 2017 | Buguey |
| 2017 | Calayan |
| 2017 | Claveria |
| 2017 | Enrile |
| 2017 | Gattaran |
| 2017 | Gonzaga |
| 2017 | Iguig |
| 2017 | Pamplona |
| 2017 | Peñablanca |
| 2017 | Piat |
| 2017 | Rizal |
| 2017 | Sanchez Mira |
| 2017 | Santa Ana |
| 2017 | Santa Praxedes |
| 2017 | Santa Teresita |
| 2017 | Santo Niño |
| 2017 | Solana |
| 2017 | Tuao |
| 2017 | Tuguegarao City |
| 2016 | Amulung |
| 2016 | Piat |
| 2016 | Santo Niño |
| 2016 | Tuao |
| 2015 | Amulung |
| 2015 | Baggao |
| 2015 | Piat |
| 2015 | Santa Ana |
| 2015 | Santo Niño |
| 2015 | Tuao |
| 2014 | Gattaran |
| 2014 | Iguig |
| 2014 | Peñablanca |
| 2014 | Solana |

### Preservation and Reproduction

Independent manifest/snapshot checks matched the 221 historical originals and earlier 300 originals (521 total), all 28 canonical finance CSVs and three source-register CSVs. The historical baseline/final staged artifacts and the earlier 300-file stage remain sealed by their checksum manifests. The final resume reused all 221 file checkpoints without parsing again; the resume log contains no per-file completed-download/parser progress entries. No original workbook was saved or recalculated. No import, sign-off, metric refresh or publication occurred.

```powershell
composer data:finances:audit-workbooks -- --collection storage/app/source-review/coa/excel-collection-2014-2019 --output storage/app/source-review/coa/xlsx-historical-261113-complete --baseline storage/app/source-review/coa/xlsx-historical-261113-baseline --workers 4 --resume
tools/coa/.venv/Scripts/python.exe -B storage/app/source-review/coa/xlsx-historical-review-261113/review.py
composer data:finances:test
composer ci:check
composer validate --no-check-publish
git diff --check
```

For a fresh run, use a fresh output and omit `--resume`; do not force an old run through changed rules. Keep the 1.5.0 historical baseline as captured rather than recreating it with 1.5.1. Final verification logs record **802 passing COA Python tests**, including unchanged Executive Summary regressions and shared intake contracts, and full CI passed with **481 Laravel tests (3,517 assertions)** plus frontend checks, Pint and PHPStan. The Python suite was rerun after the final Python-only finding-attribution adjustment; PHP application code and contracts were unchanged. Composer validation and documentation/link checks are recorded at closure.

Completed "26-11-13". Logs are `xlsx-historical-python-complete.log`, `xlsx-historical-ci.log`, `xlsx-historical-complete.log`, `xlsx-historical-resume-complete.log` and `xlsx-historical-review-final.log` under ignored COA source-review storage. The two development runs were preserved in `xlsx-historical-review-261113/development-runs-evidence.zip`; all 473 archived files were checksum-verified before their expanded draft folders were removed. The baseline, final run and all originals remain available. This keeps development JSON clutter separate from final evidence.

Documented unresolved outcomes are review limits, not automatic corrections or permission to import. All 40 no-XLSX pairs remain confirmed collection outcomes with unknown alternate formats; source reinspection is a later retry when access is available. No new packages, database/UI changes, historical imports, sign-offs, metric refreshes, commits or pushes were performed. Existing unrelated uncommitted changes remain preserved.
