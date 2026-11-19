---
title: "COA Excel Collection"
created: "26-11-13"
updated: "26-11-13"
status: completed
branch: "chore/maintainability-refresh"
base: staging
predecessor: "../archive/26-11-11-coa-browser-collection.md"
---

# COA Excel Collection

## Objective

Collect available COA Annual Audit Report ZIPs for all 29 Cagayan local governments, newest first from 2025 through 2020. Retain every XLSX workbook and provenance, deleting successfully processed ZIPs. Authorized by the owner's implementation request on "26-11-13".

## Scope and Acceptance

- [x] Reuse durable collection checkpoints, locks, bounded workers and resume behavior.
- [x] Checkpoint each observed completed download; validate and extract batches of 15 ZIPs and flush partial batches. PDF batches remain 12.
- [x] Validate all XLSX workbooks, preserve original names and nested paths, reject unsafe paths and enforce extraction limits.
- [x] Save success before deleting a ZIP; retain failed ZIPs, record readable archives with no XLSX, and preserve existing sources.
- [x] Discover official links and reconcile outcomes for all 29 governments in 2025 through 2020.
- [x] Test boundaries, errors, cleanup, interruption and resume; run Python tests and repository CI.

## Deferred or Excluded

No financial parsing, imports, sign-offs, publication, package additions, changes to existing source files, commits or pushes. XLS, XLSM and non-workbook content are excluded from extraction.

## Decisions and Sources

"26-11-13": The owner chose repository Python scripts, all Cagayan governments, 2025 through 2020, and deletion of processed ZIPs. Batch size increases from 12 to 15 for this ZIP workflow only. Failed archives remain available for retry. Metadata and provenance remain alongside XLSX payloads.

Official listing: https://www.coa.gov.ph/reports/annual-audit-reports/aar-local-government-units/. Follow the existing [collection rules](../../../.claude/rules/coa-collection.md) and [collection guide](../../../tools/coa/COLLECTION.md). Existing uncommitted verification work is preserved.

## Change History

| Date | Change | Reason / Reference |
| :- | :- | :- |
| "26-11-13" | Started implementation of the approved Excel collection plan | Owner implementation request |
| "26-11-13" | Completed all six years and independent retained-file verification | 174 checked ZIPs, 300 XLSX files; verification-summary.json in ignored collection storage |

## Verification and Handoff

Implementation complete. `composer data:finances:collect-workbooks` extends the existing local collector with all-XLSX extraction, batches of 15, atomic workbook publication, per-download journals and guarded ZIP deletion. No network library or new package was added. Existing PDF behavior remains at 12.

Verification on "26-11-13": **743 COA Python tests passed**, including 29 new workbook collection cases. `composer ci:check` passed frontend formatting/lint, TypeScript, frontend tests, Pint, PHPStan and **481 Laravel tests (3,517 assertions)**. Its first sandboxed run stopped because PHPStan could not read the existing configuration; the authorized rerun passed. Composer configuration validation and `git diff --check` passed.

Live collection completed "26-11-13". Initial COA security verification completed automatically. Observed file-ID-bound ZIP URLs and browser filenames were inventoried; downloads ran sequentially with at least 3.5 seconds between starts. The first eight 2025 downloads preceded watcher setup and were checkpointed when the watcher started; later downloads were observed automatically. Five 2025 URL basenames had revision suffixes absent from the saved filenames; exact saved names were recorded and those files resumed successfully. Transient fingerprint changes during browser saves were rejected, then retried after completion. The final 2020 queue encountered browser download timeouts; the 24 successful ZIPs were flushed before the five remaining municipal downloads resumed in a fresh browser tab. No final download, validation, cleanup or coverage failures remain.

| Report Year | Checked ZIPs | Retained XLSX |
| :- | -: | -: |
| 2025 | 29 | 58 |
| 2024 | 29 | 54 |
| 2023 | 29 | 56 |
| 2022 | 29 | 49 |
| 2021 | 29 | 42 |
| 2020 | 29 | 41 |
| **Total** | **174** | **300** |

Every ZIP has been deleted after its success checkpoint. All 300 retained files are XLSX, totaling 58,416,475 bytes. Independent reconciliation matched each year's 29 PSGC codes to the canonical Cagayan scope, checked every retained SHA-256 against its manifest, confirmed every downloaded ZIP is absent and matched the complete output directory to the recorded workbook paths. The final report is `storage/app/source-review/coa/excel-collection/verification-summary.json`. A live resume of the completed 2025 collection reused all 29 successes with deleted ZIPs, without new journal events or re-extraction.

Seven readable ZIPs contained no XLSX and have durable `no_xlsx` outcomes: Alcala and Gattaran 2022; Gattaran and Santa Ana 2021; Baggao, Gattaran and Santa Ana 2020. Other ZIPs contain one or more workbooks; no missing workbook is inferred merely from a smaller file count.

Collection, provenance, reports and original workbook member paths live under ignored `storage/app/source-review/coa/excel-collection/`. Existing registered source files, financial CSVs, ledger records and sign-offs were not changed by this collection. Earlier uncommitted verification changes remain preserved. No commit or push was performed.
