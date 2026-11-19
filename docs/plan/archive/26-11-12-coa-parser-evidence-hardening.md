---
title: "COA Parser Evidence Hardening"
created: "26-11-12"
updated: "26-11-12"
status: completed
branch: "chore/maintainability-refresh"
base: staging
predecessor: "../archive/26-11-12-historical-finance-extraction.md"
---

# COA Parser Evidence Hardening

## Objective

On "26-11-12" the owner requested senior engineering research, subagent audits and tested improvements to financial extraction and error detection for staff use. A passing parser suite must not be presented as proof that every source figure is correct.

## Scope and Acceptance

- [x] Audit numeric, row/year/context, engine fallback and import/review failure paths using independent subagents; research primary package documentation and record the decision.
- [x] Reject malformed or ambiguous monetary tokens instead of stripping punctuation into an apparently valid number; preserve exact signs and fiscal-year context.
- [x] Strengthen independent-engine confirmation beyond a matching absolute number anywhere on a page. Flag disagreement/insufficient evidence and missing or image-only content explicitly.
- [x] Apply the same arithmetic/structural checks to staff uploads and repository extraction; keep actionable code, page and source context on issues.
- [x] Protect existing reviewed observations from loss or citation changes after incomplete/failed re-extraction; enforce appropriate error checks before quick sign-off.
- [x] Add adversarial and actual-PDF regression cases that detect wrong signs, rows, years, malformed tokens, partial/no-text documents and failed re-parses.
- [x] Run a staged corpus audit, compare outputs with the current canonical data and leave published values/sign-offs unchanged. Report remaining source anomalies and limits honestly.
- [x] Pass relevant Python, Laravel, privacy/architecture and full repository checks; update staff workflow and handoff.

## Constraints

No new packages without owner approval. Preserve original PDFs and raw anomalies. No guessed corrections, automatic sign-off, metric definition or municipality scope expansion, commits or deployment. Existing Pending records remain unverified; changed candidates must be reconciled explicitly rather than silently replacing reviewed evidence.

## Research and Decisions

"26-11-12": The owner requested less engineering and a simple confidence deduction formula in the existing Python blur overlay. Finish the existing parser safeguards and tests; add no packages, new review screens, approval notes, OCR pipeline or learned scoring system. Score starts at 100, deducts 10 per distinct warning and 25 per distinct error, floors at 0, and is 0 with no figures or a failed/integrity-blocked save. It is explicitly an automated-check score, not a probability of financial correctness. Source checking remains the reviewer's responsibility.

"26-11-12": The owner reaffirmed that sign-off remains the user's responsibility and needs no per-approval note. Preserve the existing no-note workflow. Source-arithmetic anomalies remain visible for human judgment; hard extraction/evidence failures cannot be approved as parsed figures. No new approval-note requirement is authorized.

Keep installed [pdfplumber](https://github.com/jsvine/pdfplumber) and [pypdfium2](https://pypdfium2.readthedocs.io/en/stable/python_api.html). pdfplumber uses pdfminer, so those two are not independent engines. PDFium provides the separate reading, with no threaded PDFium calls. Research checked primary documentation for Camelot, pypdf, PyMuPDF, OCRmyPDF and Docling; no alternative was benchmarked or installed. [Docling's conversion confidence](https://docling-project.github.io/docling/concepts/confidence_scores/) does not supply a calibrated probability that a financial amount is correct. Geometry/OCR/package replacement is deferred rather than adding infrastructure to this slice.

The final corpus audit exposed a false jurisdiction conflict from Solana's neighboring Province of Kalinga. Province evidence now comes from government headers/identity context; unconfirmed identity is a warning, while positive conflicts remain errors. A second independent review found an entirely unreadable workbook sum component bypassing completeness checks; it is now flagged and affected totals withheld, including when all components are unreadable.

## Verification and Handoff

Follow-up correction, "26-11-12": source inspection now confirms that the two omissions in the browser example below were false positives. Both `fs.total_revenue` cells are correctly read, then intentionally excluded as check-only lines; Laravel's omission guard compares them with eligible output and forces 0. The earlier description of 0 as correctly scored is superseded for this specific case. The [active confidence eligibility fix](26-11-12-coa-confidence-eligibility-fix.md) contains verified amounts, source cells and the receiving agent's prompt. Preserve this completed hardening history; the follow-up was implemented 26-11-12.

- Python: 683 tests passed in the complete suite, followed by two additional passing actual-PDF regression cases. The suite includes 348 archive structure checks; these are not 348 financial accuracy certifications.
- Staff integration: 47 focused Laravel tests / 204 assertions passed, including strict result context, source mutation, stale omitted rows, reviewed evidence preservation and confidence validation. Import and source-bound review gate suites also passed during implementation.
- Frontend formatting/lint, TypeScript and production build passed. The existing parsing overlay displays the final score automatically; older uploads omit the score until reparsed. Browser verification covered a real saved-workbook reparse, 375px phone layout, desktop light/dark, and the cross-check text. The reparse retained two older Pending rows it could not reproduce and correctly scored the integrity-blocked result 0. Original appearance and viewport were restored. Screenshot: `storage/app/source-review/coa/parser-hardening-261112/confidence-overlay.png`.
- Final read-only audit: `storage/app/source-review/coa/parser-hardening-261112/corpus-final-v2/`. All 348 PDFs and registered workbooks were read. Canonical CSVs and registered source checksums remained unchanged. Staged observations: 5,391 versus 5,460 baseline; 94 added, 163 withheld/removed candidates, 8 changed amounts, 7 definition changes and 157 location changes. Extractor version changes account for most other differences.
- The eight changed amounts are seven prior-year cells in Camalaniugan 2015, where narrative years previously displaced the printed table's 2015/2014 columns, and Santo Niño 2019's prior-year Current Appropriations, previously confused with Continuing Appropriations. Both source layouts have actual-PDF regressions. These candidates were not imported or signed off.
- Audit findings: 435 errors, 244 warnings and 265 info findings. These include source arithmetic, reader disagreement and unresolved extraction evidence; do not describe every finding as a wrong financial amount. Required current/prior Income and Expenses: 1,358 candidate cells of 1,392, with 1,277 clear of applicable extraction blockers. This is coverage, not a correctness percentage.
- Read-only database comparison after browser reparse: all 2,675 ledger values/sign-offs unchanged, all reviewed metadata unchanged, and all 993 published statistics unchanged.
- Final `composer ci:check` passed: 461 Laravel tests / 3,376 assertions, frontend suites, formatting/lint, TypeScript, Pint and PHPStan. Privacy/architecture checks passed as part of the full suite. An initial run exposed a random municipality-code collision in the existing Population outsider fixture; that test now explicitly uses Abulug instead of a random code that could duplicate its fixed Tuguegarao fixture. Independent privacy review found no disclosure blockers: P1/P8/P10/P14 hold; remaining rules do not apply to government-finance-only changes.

Remaining source reconciliation is human verification work, not an automatic correction step. New batch findings remain in the staged audit, and existing uploaded sources gain the new findings and score when reparsed. No bulk import, approval note, publication, package installation, commit or deployment was performed. Staff instructions and the score formula are in `tools/coa/README.md`.
