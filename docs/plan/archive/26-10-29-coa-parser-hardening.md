---
title: "COA Parser Hardening"
created: "26-10-29"
updated: "26-10-29"
status: completed
branch: "feat/business-registry"
base: staging
predecessor: ./26-10-27-finance-ledger-analytics-program.md
---

# COA Parser Hardening

## Objective

The user asked on "26-10-29" for an honest account of the COA parser's accuracy, a cell-by-cell check of the Tuguegarao City Executive Summaries against the parser, and a more reliable parser with a fallback and logging of what went wrong. Python variables use camelCase. The user chose to convert all of `tools/coa/` and to stay on the current branch.

## Scope and Acceptance

- [x] Every Tuguegarao City highlights cell for 2020 to 2025 (201 cells) is typed from page images and pinned in a test.
- [x] A second PDF engine (pdfium) rereads a summary when pdfplumber misses a core line or fails, and confirms every value's digits on its page.
- [x] One failing document records `parser_failed` and the run continues; every run writes `tools/coa/logs/extract.log`.
- [x] The wrapped "Appropriations – Current" label (Tuguegarao 2021, 2022) is no longer marked `unspecified_period`.
- [x] All of `tools/coa/` uses camelCase names, with no change in output apart from the label fix.

## Deferred or Excluded

No npm or other new package: Node PDF readers are not more accurate than pdfplumber for these text PDFs, and pdfium was already installed. No OCR: every collected summary has a text layer. No app, metric, or UI change.

## Decisions and Sources

"26-10-29": pdfium was chosen as the second engine because it is already pinned and is a different text extractor from pdfminer, which pdfplumber uses. D34 records the note. Details: `data/towns/finances/README.md`, "Two PDF Engines and the Run Log" and "Tuguegarao City Cell Check".

## Change History

| Date | Change | Reason / Reference |
| :- | :- | :- |
| 26-10-29 | Extractor version 3: fallback engine, digit confirmation, crash isolation, run log, wrapped-label fix, camelCase | User request in this session |
| 26-10-29 | A test caught `engine_failed` being dropped when the fallback reading won; it is now kept | `testFallsBackToPdfiumWhenPdfplumberFails` |

## Verification and Handoff

- `composer data:finances:test`: 77 passed (61 earlier, 16 new).
- The full extraction compared with the committed output, ignoring `extractor_version`: only the four Tuguegarao 2021 and 2022 Current Appropriations rows changed (variant cleared, label completed). Issues and documents were identical. No `engine_disagreement`, fallback or failure in 158 summaries.
- `php artisan finances:import`: 4 changed back to pending, then sign-offs replayed; 678 verified and 416 superseded, unchanged. A read-only query confirmed all twelve Tuguegarao appropriation rows have no variant.
- Not done: nothing committed; `composer ci:check` not run because no PHP or frontend code changed.
