---
title: "XLSX Parser Hardening and Staged Extraction"
created: "26-11-13"
updated: "26-11-13"
status: completed
branch: "chore/maintainability-refresh"
base: staging
predecessor: "../archive/26-11-13-coa-excel-collection.md"
---

# XLSX Parser Hardening and Staged Extraction

## Objective and Authorization

The owner approved the implementation plan on "26-11-13": apply Executive Summary-style evidence and conservative checks to XLSX, process the 300 collected originals, and document a reproducible staged comparison. Preserve the originals and canonical finance files. No imports, sign-offs, metrics refreshes, publication, new packages or metric changes.

## Scope and Acceptance

- [x] Capture the 1.4.0 baseline before changing extraction rules.
- [x] Recognize observed filename variations and confirm statement contents; explicitly account for the three unrelated AAPSI files.
- [x] Strict text amounts, formula-cache/error safeguards, conflicting-year withholding, fund-aware selection and cell-level evidence work through the shared readers.
- [x] Extraction limits are not misclassified as report arithmetic; eligible and check-only figures remain separate.
- [x] Add a protected, resumable Composer staging command with bounded workers and all 300 per-file outcomes.
- [x] Compare reader and filename coverage separately, inspect every changed amount against source cells, and preserve unresolved cases as review items.
- [x] Independently check original hashes and canonical/source registers before and after both runs.
- [x] Full Python suite, repository CI, Composer validation and documentation/link checks pass.
- [x] Publish repository documentation and a readable staged run report, then archive this plan.

## Decisions and Evidence

"26-11-13": Keep the current metric scope. Ambiguous funds and unsupported statements are explicit outcomes, not invitations to invent definitions. M22 stays General Fund; M23 remains withdrawn from display. No automatic recalculation, year repair or independent Excel engine is added.

The read-only audit found 297 financial workbooks and three AAPSI files. Existing ZIP patterns miss 73 financial filenames. Existing readers produce no eligible figures for 116 financial workbooks. Tuao 2022 has two monetary strings at Statement of Fin. Performance!D9/E9; their omission exactly explains the existing revenue mismatch. Gattaran 2025 SCBAA!A3 states 2024 but the old reader emits 2025 sector figures. Other findings include fund ambiguity, duplicate values and unsupported thousand-peso units. These are review evidence, not financial sign-off.

## Implementation and Verification History

| Date | Work | Evidence |
| :- | :- | :- |
| "26-11-13" | Started on the current work branch, preserving earlier uncommitted work | Owner implementation request |
| "26-11-13" | Captured 1.4.0 baseline and implemented 1.5.0 safeguards; initial final run covers 300 files | 785 Python and 481 Laravel tests passed; final checkpoint resume reused all 300 records |
| "26-11-13" | Owner clarified that zero-figure workbooks must be checked for equivalent content in different cells, formats or layouts | Inspected every remaining zero-eligible workbook across all sheets and columns; recovered five more workbooks while keeping original files and canonical figures unchanged |
| "26-11-13" | Owner explicitly delegated ZIP collection to a sub-agent while the main agent checks parsing/ingestion | Independent collection reconciliation confirms existing 174 successes and 300 original hashes; reuse checkpoints rather than duplicate downloads |

## Run Artifacts and Handoff

All artifacts are local and ignored beneath `storage/app/source-review/coa/`. The source collection and canonical data remain read-only inputs.

| Run | Purpose | Observed result |
| :- | :- | :- |
| `xlsx-parser-261113-baseline` | Captured with 1.4.0 before reader changes | 300 outcomes: 130 parsed, 51 partial, 116 zero-eligible, 3 outside scope; 1,642 eligible and 3,144 check-only observations; 224 financial names recognized |
| `xlsx-parser-261113-layout-final` | Final 1.5.0 extraction, comparison and original-cell review | 300 outcomes: 53 parsed, 134 partial, 110 zero-eligible, 3 AAPSI outside scope; 1,681 eligible and 2,876 check-only observations; all 297 financial names recognized |
| `xlsx-zero-figure-review-261113` | Owner's follow-up on excluded content | All 115 initial hardened zero-eligible files scanned across all sheets/cells and two/three adjacent horizontal/vertical labels; five more workbooks recovered, 55 eligible candidates |

The final run's `REPORT.md` lists all 300 outcomes and changed eligible candidates with source cells. `summary.json` groups outcomes by report year and document kind. `comparison.json` includes all 459 changed observations and reopened source-cell inspections: 115 new observations have cell support (61 eligible, 54 check-only), while 344 removals remain explicit review items (22 eligible and 322 check-only). The 22 removed eligible observations are RPT net figures whose explicit discount components cannot safely be read; no correction or government arithmetic error is claimed. The 47 batch cross-check findings remain review evidence, separate from the local reader score.

| Report Year | FS | Annex | AAPSI | Eligible Candidates |
| :- | -: | -: | -: | -: |
| 2025 | 29 | 29 | 0 | 374 |
| 2024 | 27 | 27 | 0 | 333 |
| 2023 | 27 | 26 | 3 | 316 |
| 2022 | 24 | 25 | 0 | 244 |
| 2021 | 20 | 22 | 0 | 209 |
| 2020 | 18 | 23 | 0 | 205 |
| Total | 145 | 152 | 3 | 1,681 |

### Source-Supported Fixes and Remaining Limits

- Tuao 2022: strict valid peso strings at `Statement of Fin. Performance!D9/E9` recover tax revenue 15,666,217.15 and 12,954,238.73. This resolves the parser omission; it is not a sign-off.
- Tuguegarao 2022 Annex: the uniquely supported current-year General Fund statement replaces an unsupported historical-sheet selection; four detailed candidates have original-cell support.
- Santa Ana 2022/2023/2024 and Buguey 2023: explicit consolidation working sheets no longer tie with final consolidated statements. The rule uses printed worksheet content, not a tab-name preference.
- Tuguegarao 2021: `Financial Performance!D6/G6/I6` identify 2021/2020/2019. Historical headers now bound the comparative search, retaining D/G values rather than allowing I into the 2020 candidates. Duplicate older historical headers do not make the current/prior pair ambiguous.
- All baseline 116 zero-eligible files are reconciled in `baseline-no-figure-review.csv`: two now parsed, four partial and 110 still zero-eligible. This is distinct from the 73 filename-recognition misses.
- The follow-up's remaining 110 comprise 106 annexes without exact supported M22 detail labels in the tested single/adjacent-cell scans and four source/layout limits. Claveria 2021 FS and Santo Niño 2024 Annex repeat/conflict on printed years; Baggao 2023 C-1/C-2 both explicitly say General Fund and remain ambiguous; Santo Niño 2023 FS has readable native numbers but unsupported revenue labels/group headings and blank/dash components. No automatic year/fund/zero repair was made. Unrecognized semantic aliases remain a documented limitation.
- Unsupported units, ambiguous budgets, missing formula caches/errors, incomplete sums and unknown revenue components remain reading limits. Missing matched lines do not establish report arithmetic errors. M22 General Fund detail is never compared with consolidated totals; M23 stays check-only.

### Preservation and Reproduction

Independent SHA-256 verification matched all 300 originals, all 28 canonical finance CSVs and three source-register CSVs against both baseline and final before/after snapshots. Both staged checksum manifests and the final parser-code identity also match. A completed final resume reused the same 300 file checkpoints. There were no imports, workbook saves, sign-offs, metric refreshes or publication.

```powershell
composer data:finances:audit-workbooks -- --collection storage/app/source-review/coa/excel-collection --output storage/app/source-review/coa/xlsx-parser-261113-layout-final --baseline storage/app/source-review/coa/xlsx-parser-261113-baseline --workers 4 --resume
python -B storage/app/source-review/coa/xlsx-zero-figure-review-261113/review.py
python -B storage/app/source-review/coa/xlsx-verification-261113/verify.py
composer data:finances:test
composer ci:check
composer validate --no-check-publish
git diff --check
```

For a fresh parser run, change the output directory and omit `--resume`. Preserve the captured 1.4.0 baseline and its manifest; running current code does not recreate a historical baseline. The final run identity records exact parser, code, catalog, input and baseline hashes.

Initial verification retries encountered Windows long temporary paths and transient access errors; a short ignored pytest directory avoids the path issue. A temporary test directory outside ignored source-review storage was removed after it made formatting scan generated fixtures. Generated test fixtures explain much of the JSON accumulation; per-source review checkpoints intentionally remain separate from originals. Final checks are recorded below when complete.

"26-11-13": The owner questioned the accumulated JSON files. Counted 7,017 outside dependency folders during a running audit: repeated synthetic test collections, several 307-JSON workbook runs, earlier parser audits and Inertia devtools. Removed 1,284 disposable JSON fixtures from the six explicitly named `t-xh5` through `t-xh10` test directories after recording their results; preserved sources, baseline, final and prior audit evidence. The Composer Python test runner now uses a short ignored temporary directory and cleans its fixtures after success/failure, with regressions. An explicit `--basetemp` retains debugging fixtures by choice.

Final COA verification: `composer data:finances:test` passed **789 tests** in 107.12 seconds, including the Executive Summary regressions, shared intake, collection boundaries, staged interruption/resume, source protection, strict amounts, year/fund ambiguity, working-sheet exclusion, historical-year bounds and test-fixture cleanup. The runner's temporary directory was absent after completion. Log: `storage/app/source-review/coa/xlsx-python-final.log`. Composer validation, documentation/link checks and `git diff --check` passed.

The sandboxed PHPStan configuration read failed; its outside-sandbox retry passed. The remaining CI process then stalled because Docker Desktop was stopped and the engine pipe was absent. Stopped that CI process, started the existing per-user Docker Desktop installation hidden, and ran `composer db:up`; the existing PostGIS container became healthy without resetting or deleting data. A fresh full `composer ci:check` passed frontend formatting/lint, TypeScript, frontend tests, Pint, PHPStan and **481 Laravel tests (3,517 assertions)**. Log: `storage/app/source-review/coa/xlsx-ci-final.log`.

The main agent also reviewed the Python-to-Laravel ingestion boundary: shared readers, eligible-line filtering, fixed-decimal serialization, year/document context, duplicate observations and checksum validation. Read-only intake calls for Tuao 2022, Tuguegarao 2021, Gattaran 2025 and Santo Niño 2023 retained source hashes/evidence, emitted only eligible lines in the report/prior-year context, and fit the existing 2 MiB event limit (largest sample 118,303 bytes). Results: `storage/app/source-review/coa/xlsx-ingestion-contract-261113.json`. No upload job or import was run.

The delegated collection verification independently reconciled 174/174 completed ZIP outcomes and 300 retained workbooks, then resumed each of the six years with extraction/cleanup guarded against execution. All six reused their 29 checkpoints without journal changes, source hash/size/mtime changes or unrelated Downloads changes. Report and rerunnable verification: `storage/app/source-review/coa/collection-resume-verification-261113/REPORT.md`. The owner's later 2014–2019 ZIP request is a separate collection scope and does not extend this completed 300-file parsing run.

Completed "26-11-13". Remaining unsupported statements and unresolved findings are documented review outcomes, not unfinished extraction promises. Later financial verification/import requires separate authorization. Existing unrelated uncommitted changes are preserved; no commit or push was performed.
