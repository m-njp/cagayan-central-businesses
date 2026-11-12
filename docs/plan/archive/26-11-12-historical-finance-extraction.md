---
title: "Historical Finance Extraction"
created: "26-11-12"
updated: "26-11-12"
status: completed
branch: "chore/maintainability-refresh"
base: staging
predecessor: "../archive/26-11-12-source-library-and-file-organization.md"
---

# Historical Finance Extraction

## Objective

On "26-11-12" the owner requested extracting the 174 Executive Summaries for 2014 through 2019 and the 16 newly collected 2025 reports into the finance review queue, with source pages for verification.

## Scope and Acceptance

- [x] Reconcile the exact 190 target documents against the existing source register and ledger.
- [x] Extract supported catalog lines with exact decimal amounts, document checksums and PDF page citations; preserve individual issues and unavailable sections.
- [x] Preserve existing financial values and review decisions outside this batch; inspect the extraction/import differences before writing the ledger.
- [x] Load eligible new observations as Pending through the existing finance import workflow, retaining the approved municipality/line scope.
- [x] Confirm reviewers can select historical report years and inspect the source pages; new figures do not enter published finance statistics.
- [x] Run relevant parser/import/review checks and record counts, exceptions and handoff.

## Decisions and Exclusions

Reuse the existing Python readers, source checks and Laravel import. Parsing is transcription, not verification. No fabricated values, automatic sign-off, new metric definitions, OCR dependencies, workbook scope expansion, commits or deployment. Original reports may include prior-year comparative figures; keep their fiscal year distinct from the report year. Any source errors or unsupported layouts remain explicit review issues.

## Verification and Handoff

Completed on "26-11-12". Independent financial verification is the intended next step.

## Implementation and Evidence

- Reconciled exactly 174 historical sources and 16 previously unavailable 2025 summaries. Local `targets.csv` identifies every report by PSGC, report year, filename and SHA-256.
- Extractor 1.2.0 supports `P=` currency, monetary digits without grouping separators, unmarked percentages, six-line wrapped expense qualifiers and readable current/prior values beside malformed trailing change/percentage text. Those malformed trailing fields generate warnings; malformed main amounts are not inferred. Regression tests cover the new layouts, missing amounts and preservation of page/definition evidence.
- The full extraction was staged before promoting the requested batch programmatically. The canonical files now contain 5,460 unique observations: 2,541 unchanged existing observations and 2,919 new source-cited observations. Existing 2020 through 2024 extraction CSVs, `lines.csv`, `reviews.csv` and `manual-figures.csv` remain byte-identical to the baseline. Only the target reports were added to document metadata and figures; existing issues were retained.
- Scope is unchanged: the import queued 1,497 eligible observations from all 190 documents. Another 1,422 financial-condition/allotment/continuing-obligation observations remain in CSV because the existing `summary_detail_towns` rule enables those lines only for Tuguegarao City. No workbook-town expansion or metric definition change occurred.
- New Pending figures by report year: 2014 232; 2015 238; 2016 236; 2017 244; 2018 238; 2019 241; 2025 68. Every historical report year covers 29 municipalities/city; the 2025 batch covers the 16 new summaries. The database now has 2,675 parsed figures: 1,509 Pending (including 12 previously pending), 720 Verified and 446 Superseded.
- Each new observation's page is positive and within the registered PDF page count; all 190 PDF hashes were rechecked before promotion. Figure source labels and original document checksums remain attached. Registered review links use the stored PDF route with `#page=N`; document links use the same registered source inventory. Manual/unregistered sources retain their original URL fallback. The existing staff/municipality authorization remains in place.
- Review validation now begins at 2013, for the comparative column in the 2014 reports. Published-year configuration and verified-data gates are unchanged.
- The live import ran through `finances:import` inside an outer transaction with rollback guards. It added 1,497 figures, changed zero existing values back to Pending and removed zero figures. All 1,178 existing observation values, citations and review decisions were compared before commit, and all 993 published statistic rows matched the baseline. No new sign-offs were recorded.
- Local evidence lives under ignored `storage/app/source-review/coa/finance-extraction-261112/`: baseline CSVs, ledger/statistic snapshots, full staged output, target list, promotion script/result and guarded import script/log/result. The operational scripts select this fixed source batch; the versioned readers and importer remain the normal supported workflow.

## Source Exceptions for Review

All reports have queued observations, but source anomalies remain explicit. The selected report issue files contain 80 warnings, 38 errors and 123 informational notes; these include source arithmetic, conflicting rows, header mismatches, restatements and dual-reader checks. These are source/transcription review issues, not failed downloads or automatic approvals.

- Santa Praxedes 2016 Income prints `48,014,545,75` on page 1. The main monetary row is withheld for manual transcription/verification.
- Gattaran 2018 Income prints comparative `231,382,293,32` on page 2. The row is withheld for manual transcription/verification.
- Four historical reports have mismatched column years, and Rizal 2018 has three independent-reader digit disagreements. Preserve their visible errors for source inspection; do not silently correct their year assignments or digits.

The full extraction also regenerated unrelated workbook metadata and omitted four pre-existing `fs.total_revenue` rows. Those changes were excluded from promotion and all existing workbook observations preserved. A future unscoped regeneration requires a separate diff/review; this task does not change workbook parsing.

## Verification

- `composer data:finances:test`: 478 passed, including all stored PDFs and the historical-format regressions.
- `composer ci:check`: passed after formatting the new feature test. Frontend formatting/lint, TypeScript, all frontend suites, Pint and PHPStan passed; 392 PostGIS/Pest tests passed with 2,961 assertions, including import preservation, Pending publication rules, historical review access, page URLs, staff authorization, privacy and architecture.
- `git diff --check`: passed. The existing source-register CRLF normalization notice remains harmless.
- Browser: opened the existing local Admin demo account, confirmed all report-year tabs through 2014, historical Pending report counts and the newly collected 2025 reports. Opened Abulug 2014 and switched to comparative fiscal year 2013; every displayed value remained Pending with a stored-PDF `#page=1` link. Opened Abulug 2025 and confirmed the new values and page citations remain Pending. No Review/Verify action was submitted. Left the 2014 Pending queue open for the owner.
- Privacy review: P8, P10 and P14 hold through authorized report requests, explicit Data objects and safe source-only metadata. P1-P7, P9, P11-P13 and P15 do not apply to this government-document-only change. The separate D35 publication gate was verified by unchanged published statistics. No business records, wishes, sales, identity fields or private upload permissions changed.

No commits, pushes, deployment or financial sign-off were performed. Reviewer handoff is the Government Finances queue, report years 2014 through 2019 and the 16 new 2025 reports, using each cited source page and its visible parser issues.
