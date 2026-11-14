---
title: "Workbook Ingestion for All Governments"
created: "26-11-13"
updated: "26-11-13"
status: completed
branch: "chore/maintainability-refresh"
base: staging
predecessor: "../archive/26-11-13-historical-expense-differences.md"
---

# Workbook Ingestion for All Governments

## Objective and Authorization

On "26-11-13" the owner explicitly requested populating the application with workbook data, then selected **2014–2025 workbooks for all 29 governments**, superseding the former Tuguegarao-only workbook ingestion restriction. New figures enter the finance ledger as Pending, without automatic sign-offs. This follows [historical staging](../archive/26-11-13-historical-xlsx-staged-parsing.md), [2020–2025 hardening](../archive/26-11-13-xlsx-parser-hardening.md) and the [four expense source reconciliations](../archive/26-11-13-historical-expense-differences.md).

## Scope and Acceptance

- [x] Use current improved readers for all 521 collected originals; reconcile eligible observations, zero outcomes and duplicate document identities.
- [x] Prepare and inspect an additive import diff, source mappings, checksums and findings. Preserve Executive Summary data, existing reviewed source evidence and sign-offs; explicitly withhold conflicting source replacements.
- [x] Expand configured workbook scope to all 29 canonical government identities, with no metric-definition or Executive Summary-detail scope changes.
- [x] Register immutable source copies and merge eligible candidates/findings through the existing canonical CSV and finance import contracts, retaining check-only evidence separately.
- [x] Import through the existing transactional workflow with rollback guards; reconcile new Pending counts, preserve existing figures/review decisions and published statistics.
- [x] Verify review visibility and source citations, run relevant checks and full CI, document exact outcomes and limitations.

## Deferred or Excluded

No automatic verification, corroboration, metric-definition changes, PDF parser changes, source workbook edits, database schema changes, packages, commits or deployment. Public published totals remain unchanged. Expanding workbook intake does not expand Executive Summary detail lines. Unsupported or conflicting workbook layouts do not become fabricated data.

## Decisions and Sources

"26-11-13": the owner selected the all-29, 2014–2025 scope in response to the explicit `cagayan.finances.workbook_towns` restriction question. This authorizes changing that configuration and updating canonical workbook extraction/source metadata for ingestion. Originals remain unchanged. Retain all existing records outside the promoted candidates; identity collisions and reviewed-source mismatches must be documented rather than overwritten.

## Change History

| Date | Change | Reason / Reference |
| :- | :- | :- |
| "26-11-13" | Started guarded all-government workbook ingestion | Explicit owner authorization |

## Verification and Handoff

Completed "26-11-13", including the owner's later nine-line scope correction. Final new intake is **2,177 Pending observations** across all 29 governments and report years 2014–2025 (1,800 FS, 377 General Fund Annex). The initial 2,385-row transaction was followed by removal of only the 208 newly inserted, still-unreviewed Other Shares from National Taxes observations. All 2,670 original ledger observations/review decisions and 1,702 published statistic rows remain unchanged; no new sign-offs occurred.

Both 221 historical and 300 modern originals were processed with extractor 1.5.1. The modern rerun had no candidate amount changes against its earlier final baseline. There are 516 registered workbook identities; 482 new registered copies were added, two old not-published placeholders filled, and all current 864 workbook/PDF paths/checksums verified. Duplicate Lasam originals, alternative zero-eligible Peñablanca Annexes and three AAPSI files are explicitly reconciled. Check-only data stays outside new intake. The 64 unpromoted old CSV observations (58 retired/check-only sector lines and six unsupported property-tax candidates) had never been loaded into the ledger; their earlier evidence remains archived. No existing canonical amount changed; metadata was updated for 237 previously unimported retained candidates. Retained CSV row order was restored to reduce unrelated diffs.

The live Admin Government Finances queue, historical report years, Tuguegarao reference fields and Analytics exclusions were inspected. New observations remain Pending in review, not published Analytics. The local server is running at `http://localhost:8000`; port 8000 returned HTTP200 when the owner asked to check the included data.

Full `composer ci:check` passed: 481 Laravel tests, 3,519 assertions, frontend checks, Pint and PHPStan. The initial configuration line-ending failure was corrected, then full CI passed. Final dataset/path changes passed the focused import regression (1 test, 11 assertions). Composer validation, source/hash checks, canonical Executive Summary preservation and documentation checks passed. Parser Python modules were unchanged during ingestion; their 802-test suite had passed in the preceding historical hardening work.

Closed operational evidence now lives in `storage/app/source-review/coa/archives/26-11-13-ingestion-and-cleanup-evidence.zip`; readable summaries and indexes are in `storage/app/source-review/coa/summaries/`. See the [developer/AI handoff](../../engineering/coa-workbook-handoff.md) and [completed cleanup record](26-11-13-coa-workbook-cleanup-handoff.md). The generic reader still supports the broader catalog; a maintained current-inventory, nine-line promotion gate remains explicitly deferred rather than falsely claimed implemented.


## Owner Clarification and Cleanup Follow-Up

On "26-11-13" the owner clarified that all-government intake must use only the fields actually present in the Tuguegarao reference, not expand to every catalog field. The all-29/year scope remains authorized. The field audit found nine Tuguegarao lines and one extra catalog line, `revenue.other_national_share`, accounting for 208 newly inserted Pending observations. Correct that scope by removing only those 208 still-unreviewed new observations; preserve existing observations/sign-offs and raw check evidence. No financial metric definition is changed. Subsequent [cleanup and handoff](26-11-13-coa-workbook-cleanup-handoff.md) owns this correction, human-readable source paths, artifact cleanup and final handoff.

The first guarded import committed 2,385 Pending observations, preserving all 2,670 existing observations and 1,702 published statistic rows. It registered 482 source copies and filled two old not-published placeholders. The focused import test passed (1 test, 11 assertions); full CI is running after fixing configuration line endings. Final completion counts must report the narrower scope after the 208-row correction, not repeat 2,385 as the final count.
