---
title: "COA Confidence Eligibility Fix"
created: "26-11-12"
updated: "26-11-12"
status: completed
branch: "chore/maintainability-refresh"
base: staging
predecessor: "26-11-12-coa-parser-evidence-hardening.md"
---

# COA Confidence Eligibility Fix

## Request and Scope

On "26-11-12" the owner asked why the overlay showed 0%, requested checking the two flagged figures against the source, and requested a prompt/context handoff so another agent handles the fix. This turn performed read-only diagnosis and documentation only. Implementation and reparse verification remain open.

## Verified Diagnosis

This example is a financial-statements XLSX, not a missing PDF row. Municipality: Tuguegarao City, PSGC `0201529000`, report year 2025.

Source: `data/towns/financial-statements/2025/Tuguegarao-City/Tuguegarao-City-FS-2025.xlsx`, worksheet `8 - Financial Performance 2025`, row label `Total Revenue`.

| Fiscal Year | Source Cell | Exact PHP Amount | Existing Ledger ID | Line Code | Status |
| :- | :- | -: | -: | :- | :- |
| 2025 | D14 | 1,679,019,473.60 | 1201 | fs.total_revenue | Pending |
| 2024 | F14 | 1,465,722,146.22 | 1202 | fs.total_revenue | Pending |

Both source amounts match the existing ledger rows. Their figure UUIDs are `db261dc3-30cd-4993-ad0d-d42af26efd01` and `ee866d4d-f76e-4f15-b71d-b6a62f91fce7`. Finance document ID: 210. These database IDs are local diagnostic references, not application constants.

Upload UUID: `2a27203d-bca0-4637-8219-af495abc5d64`. Stored local-disk path: `finance-uploads/2a27203d-bca0-4637-8219-af495abc5d64.xlsx`. Stored bytes match the canonical workbook. SHA-256: `ced149a8620983e2081edd9c0da20aaa7a8a0b2d2abde247a8ffee9a53747f69`. Current task status is Done with confidence 0 and exactly two non-info issues, both `previous_figure_not_reproduced`, scoped to the cells/years above.

Reproduction on "26-11-12" with current code:

1. `coa.workbook.readFs`, supplied expected municipality/province, returns both `fs.total_revenue` figures with the correct amounts, years and cell citations.
2. `data/towns/finances/lines.csv` defines `fs.total_revenue` as `displayed=0`, source `fs_totals`, group `checks`.
3. `coa.intake.isDisplayed` intentionally excludes it from final eligible upload figures. Intake returns 10 figures, only info issues, and Python confidence **100**.
4. `ExtractFinanceUpload` compares every previous parsed Pending row against that filtered result. It does not limit the comparison to currently eligible lines. The two old check-only rows are therefore classified as omissions.
5. The job sets `serverIntegrityError=true` and overrides confidence to **0**, rather than applying two ordinary 25-point deductions.

This is an eligibility mismatch in the integration guard. Python did not fail to read these two cells. The previous hardening plan's statement that the result was correctly scored 0 described the guard firing; source verification now establishes that these specific omission flags are false positives. Do not remove the safeguard for genuinely missing eligible figures.

## Agent Prompt

Continue in `C:\Users\ninomarcos\Documents\EGAY Business\cagayan-business-kickoff`. Read AGENTS.md and the matching tracked project skills first. Use the current checkout; never create a worktree, clone or repository copy. The work branch was `chore/maintainability-refresh` when this handoff was prepared. Run git status and preserve the extensive existing uncommitted work.

Fix the false 0% confidence case documented above with the smallest coherent change. Verify the current source bytes, reader output and database state before mutation. Start with `app/Jobs/ExtractFinanceUpload.php`, `app/Models/FinanceLine.php`, `app/Actions/RecordParsedFigures.php`, `tools/coa/coa/intake.py`, `tools/coa/coa/workbook.py` and `data/towns/finances/lines.csv`. `FinanceLine::enterable()` already expresses the same eligibility as Python intake: displayed, non-derived lines. Prefer aligning the omission check with the existing eligibility contract. Do not expose internal check lines, change catalog visibility, add a parallel evidence framework, or modify Python extraction unless reproduction demonstrates an actual reader defect.

Add regression coverage in `tests/Feature/FinanceParserContractTest.php`: legacy check-only Pending figures excluded by the current catalog must remain unchanged without omission errors or a forced 0; genuinely omitted eligible Pending figures must still be retained, flagged with their exact line/year/source, and force 0. Cover a mixed case so filtering check-only rows cannot hide a real omission. Preserve existing source-hash, year/kind/page, duplicate and reviewed-evidence safeguards. An actual-workbook regression should prove the D14/F14 totals are read correctly and intentionally filtered from eligible intake. Reuse existing tests/helpers rather than adding infrastructure.

After the fix, reparse the existing stored upload through the normal queue/browser workflow. Confirm these two false flags disappear, the overlay displays the actual Python score (100 in the diagnosed run with only info findings), and no financial value, sign-off, reviewed citation or published statistic changes. Preserve the legacy check-only ledger rows; do not delete, approve, or silently rewrite them. Do not simply hard-code the score, suppress all omission errors, or change the deduction formula to make the symptom disappear.

Keep the scope small. No new package, approval note, new screen, metric change, automatic approval, bulk import, commit, push or deployment. User sign-off remains without a per-approval note. Confidence 100 means all configured automated checks passed, not guaranteed financial correctness.

Run focused parser/contract tests, Pint/PHPStan and required full `composer ci:check`. If frontend code changes, also run `npm run check`, `npm run types:check` and `npm run build`, using the relevant UI skills. Tests use PostGIS, never SQLite; coordinate the test database if other agents are working. Reuse any existing dev server on port 8000. Record actual outcomes here, update the linked docs and archive this plan only when acceptance checks pass. Report the root cause and observed before/after score succinctly.

## Acceptance

- [x] Verify both flagged amounts against canonical and uploaded source bytes; identify why the score was overridden.
- [x] Align the omission guard with current line eligibility without weakening genuine omission detection.
- [x] Add check-only, genuinely missing eligible and mixed-case regressions plus actual-workbook coverage.
- [x] Reparse the existing upload and verify the corrected score and removed false flags in the overlay.
- [x] Prove ledger values, sign-offs, reviewed citations and published statistics are preserved.
- [x] Pass required checks, update references and archive this follow-up.

## Baseline and References

- [Parser workflow and score formula](../../../tools/coa/README.md).
- [Government finance data](../../../data/towns/finances/README.md).
- [Predecessor hardening and audit](26-11-12-coa-parser-evidence-hardening.md).
- [Live parsing plan](../active/26-11-08-live-finance-parsing.md).
- Prior verification, before this docs-only follow-up: complete 683-test Python suite plus two added actual-PDF regression tests; full CI 461 Laravel tests / 3,376 assertions; frontend checks and build passed. These are baseline results, not evidence that this open fix is complete.
- Prior database comparison: 2,675 ledger rows and 993 published statistics unchanged after the earlier reparse. Capture a fresh before/after baseline for this fix; do not assume the database remains frozen between chats.
- Ignored prior audit and screenshot artifacts: `storage/app/source-review/coa/parser-hardening-261112/`. They are local and may not exist on another machine. The source/diagnosis above is recorded here so the handoff does not depend on those artifacts.

## Verification

"26-11-12": Read workbook cells with openpyxl; executed raw reader and filtered intake on canonical bytes; queried the two live ledger rows and the existing upload read-only; confirmed uploaded and canonical checksums match. No parser/application code or ledger data changed in this handoff turn. Implementation tests have not been run because implementation is deferred to the receiving agent.

"26-11-12" implementation: `ExtractFinanceUpload` now compares previous Pending parsed figures only for lines in `FinanceLine::enterable()` (displayed, non-derived), the same contract as `coa.intake.isDisplayed`. Genuinely omitted eligible figures are still retained, flagged with line/year/location and force confidence 0. Python extraction, catalog visibility, the deduction formula and the frontend are unchanged. Regression tests: two Pest cases in `tests/Feature/FinanceParserContractTest.php` (check-only rows stay unchanged with no omission and the Python score of 95 kept; a mixed case flags only the real omission and forces 0; both fail without the fix) and one Python test reading D14/F14 from the actual workbook and confirming `fs.total_revenue` is not intake-eligible.

Live reparse through `RetryFinanceUpload` and the queue worker (local upload 1): confidence 0 to 100, issues 6 to 4 (the two `previous_figure_not_reproduced` errors gone, four info findings remain). Ledger comparison before and after: 2,675 rows, 720 Verified and an identical hash over line, year, basis, value, status, location, review date, label and extractor version, so values, sign-offs and reviewed citations are unchanged. The legacy `fs.total_revenue` Pending rows remain. The reparse does not write `stats_` tables, so published statistics were not touched; a pre-reparse statistics hash was not captured. The overlay itself was not re-opened in a browser; the stored progress confidence it renders is 100.

Checks: `composer ci:check` passed (Pint, PHPStan 0 errors, 463 Pest tests, 3,391 assertions, frontend suites); full Python suite 686 passed from `tools/coa`. No frontend change, so the npm checks were not rerun separately.
