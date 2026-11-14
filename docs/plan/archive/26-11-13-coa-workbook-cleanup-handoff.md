---
title: "COA Workbook Cleanup and Handoff"
created: "26-11-13"
updated: "26-11-13"
status: completed
branch: "chore/maintainability-refresh"
base: staging
predecessor: "26-11-13-workbook-ingestion-all-governments.md"
---

# COA Workbook Cleanup and Handoff

## Objective

On "26-11-13" the owner requested a comprehensive cleanup, developer/AI context, a clear handoff on inconsistent workbook layouts, removal of unused scripts and bulky generated files, and municipality-based human-readable source folders. The owner also clarified that intake uses only Tuguegarao's existing fields, despite collection spanning all 29 governments and 2014–2025.

## Scope and Acceptance

- [x] Reconcile the actual inserted line codes against Tuguegarao; remove only the 208 newly inserted, still-Pending extra-line observations and document the nine-line intake scope.
- [x] Rename the 482 newly registered source copies to municipality folders and readable document names; update CSV and database citations atomically without modifying workbook bytes or pre-existing reviewed sources.
- [x] Keep all 521 collection originals, 348 original Executive Summary PDFs, review decisions and published statistics unchanged.
- [x] Inventory and remove generated disposable test fixtures after proving they contain no authoritative originals; consolidate historical generated evidence into checksum-verified archives before removing expanded duplicates.
- [x] Remove unused one-off inspection scripts from the active workspace while preserving reproducibility evidence in archives and supported commands in tools/coa.
- [x] Produce readable source indexes, run summaries, coverage/exclusion lists and a tracked developer/AI handoff explaining supported layouts, exact scope, the four reconciliations, uncertain cases and safe next steps.
- [x] Verify all current document paths/hashes, final Pending counts, publication preservation, repository checks and documentation links; archive completed plans and update current context.

## Boundaries

No further broad ingestion or automatic sign-off. No original source deletion, finance metric redefinition, PDF algorithm change, UI redesign, new package, database schema change, commit or deployment. Do not discard all workbook data solely because formatting differs. Ambiguous scope/year/formula/amount sections stay withheld; uncertain accepted candidates stay Pending for source review. An exact arithmetic bridge does not establish a preferred accounting definition.

## Decisions and Sources

"26-11-13": nine observed Tuguegarao lines are `revenue.tax`, `revenue.national_share`, `revenue.service_business`, `revenue.grants`, `revenue.other`, `local.business_tax`, `local.permit_fees`, `local.market_operations`, `local.real_property_tax`. `revenue.other_national_share` remains a catalog definition but is outside this authorized intake subset. Preserve it only as raw review evidence. Existing eight non-displayed legacy workbook observations predate this ingestion and are not removed.

"26-11-13": numeric source-copy folders are PSGC geographic identifiers, not malformed years. Keep PSGC in metadata, use municipality names in folders. Only newly created registered copies are renamed; collection original filenames and hashes remain intact.

## Change History

| Date | Change | Reason / Reference |
| :- | :- | :- |
| "26-11-13" | Started scope correction, source organization and evidence cleanup | Owner's explicit clarification and cleanup request |

## Verification and Handoff

Completed "26-11-13". The [developer/AI handoff](../../engineering/coa-workbook-handoff.md) is the primary operational context; current source navigation is [the workbook index](../../../data/towns/financial-statements/index.csv). [The intake record](26-11-13-workbook-ingestion-all-governments.md) retains initial authorization and the owner's later scope correction.

### Final Results

- Removed only 208 newly added, still-Pending `revenue.other_national_share` observations. Final new intake is 2,177 Pending observations, nine exact Tuguegarao codes, all 29 governments. All 2,670 pre-existing observations and review decisions remain unchanged; final ledger count 4,847. No new sign-offs or publication.
- Renamed 482 new source copies to `<year>/<Municipality>/<Municipality>-Financial-Statements-<year>.xlsx` or `-Annexes-<year>.xlsx`; updated canonical CSV/database paths and verified checksums. Removed obsolete copies and 284 empty PSGC-number folders. Preserved all 521 collection originals, 348 original PDFs and earlier reviewed source bytes.
- Deleted 6,253 disposable generated fixture files in 39 verified directories (569,583,901 bytes), after confirming no authoritative source paths were inside. The supported test runner already uses automatically cleaned temporary fixtures.
- Consolidated 3,586 generated evidence files into three ZIPs; every member checksum/CRC was verified before removing expanded copies. `archives/index.csv` and internal `EVIDENCE-MANIFEST.csv` files record the proof. Removed unused `inspect_sources.py`, inventory/inspection/promotion helpers and their loose snapshots. Production code remains in `tools/coa/`; original collection journals remain in place.
- Kept readable CSV summaries for all 521 outcomes, all 47 no-XLSX government/year cases, nine-field scope, final counts, source indexes, scope exclusions, source-path changes and the four expense reconciliations. The handoff documents unsupported layouts, fund/year/cache safeguards and the 11 remaining printed-total differences; it does not claim unsupported figures must be invented.
- Checked actual Tuguegarao 2025 review/Analytics UI: Revenue Sources and Business Taxes and Fees enabled; Budget and Actual Amounts disabled. Pending, Superseded, unavailable coverage and locked verified cells are distinct from withdrawn fields. The screenshot is retained under `summaries/ui-disabled-measures.png`. The owner's Abulug tab was left unchanged.
- Normalized two newly added Gonzaga source-label line breaks in CSV/DB metadata for clean diffs; no amount, source cell or review decision changed. Restored retained CSV row order, preserving all row contents.

### Verification

Final source/database verification passed: all 864 registered paths and hashes valid; all 521 originals, 348 PDFs and 15 parser modules unchanged; all Executive Summary document/figure/issue rows preserved; `reviews.csv`, `manual-figures.csv` and the line catalog byte-identical to the pre-ingestion baseline. All 1,702 published statistic rows remain unchanged. Full repository CI passed (481 tests, 3,519 assertions); the final narrowed/path-organized dataset passed the focused import regression (1 test, 11 assertions). Composer validation, local documentation links and `git diff --check` passed.

No commit, PR, push or deployment was requested or created. The local server was already listening on port 8000 and answered HTTP200; it was reused for the owner's data inspection. The only retained ingestion working-folder file is its active runtime log plus a short explanatory README.

### Handoff Boundaries

The cleanup does not globally rewrite metric definitions, the generic parser or upload contracts. The nine-line subset is the owner's approved promotion scope for this curated dataset. Future work must integrate the collection-backed 516-source inventory and explicit promotion gate before unscoped canonical regeneration; legacy `sources.csv` covers only the earlier 34 copies. Do not restore archived canonical snapshots over current data or treat a gray status as permission to remove a valid field. Source verification remains separate from import: the 2,177 new observations are Pending because no new sign-off was authorized or performed.

Evidence: `storage/app/source-review/coa/summaries/REPORT.md`, `final-state.csv`, `verification.csv`, `checks.txt`, `source-path-changes.csv`, `excluded-extra-field.csv` and the indexed archives. Known future integration/review work remains explicitly listed in the handoff; it is not hidden by this completion status.
