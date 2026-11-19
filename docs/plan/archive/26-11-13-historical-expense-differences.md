---
title: "Historical Expense Source Differences"
created: "26-11-13"
updated: "26-11-13"
status: completed
branch: "chore/maintainability-refresh"
base: staging
predecessor: "../archive/26-11-13-historical-xlsx-staged-parsing.md"
---

# Historical Expense Source Differences

## Objective

On "26-11-13" the owner requested investigation of the four 2014 expense differences found by the [historical XLSX staged run](../archive/26-11-13-historical-xlsx-staged-parsing.md), against original workbooks and original Executive Summary PDFs, with documented explanations and unchanged sources. No imports are authorized.

## Scope and Acceptance

- [x] Verify both original source hashes and the staged citations for Calayan, Camalaniugan, Claveria and Santa Praxedes.
- [x] Read and render the cited original PDF pages; inspect original worksheet cells, cached formulas, statement headers and relevant components without recalculating or saving workbooks.
- [x] Establish each difference's arithmetic and supported cause; explicitly distinguish proven explanations from unresolved hypotheses.
- [x] Produce reproducible per-case evidence and a readable review report; preserve original staging artifacts.
- [x] Verify unchanged original collections, PDF sources, canonical finance/source registers and parser code before and after; check documentation links and whitespace.

## Deferred or Excluded

No parser changes, imports, sign-offs, metric changes, recalculation, source edits, publication or database changes. Read-only PDF extraction/rendering is review evidence, not a canonical reparse.

## Decisions and Sources

"26-11-13": use the four existing `differs_from_summary` findings in `storage/app/source-review/coa/xlsx-historical-261113-complete/cross-checks.json` as case identifiers. Store new evidence separately under ignored source-review storage; retain source hashes and exact cell/page references. Explain a difference only to the degree supported by the original documents.

## Change History

| Date | Change | Reason / Reference |
| :- | :- | :- |
| "26-11-13" | Started original-source investigation | Owner's explicit follow-up |

## Verification and Handoff

The read-only original-source investigation reconciles all four differences exactly. The readable report and reproducible evidence are in `storage/app/source-review/coa/expense-source-review-261113/REPORT.md`, `reconciliation.json`, `visual-review.json`, `sources.json` per municipality, and `verification.json`. The earlier staged run and findings remain intact; this is a subsequent explanatory record.

### Source Conclusions

All four sources explicitly identify 2014 consolidated performance, and the cited PDF financial-highlight tables explicitly distinguish 2014 and 2013. Original SHA-256 values match collection checkpoints, staged citations and the PDF document register. Workbook amounts and original PDF table digits agree with their staged transcriptions. The differences reflect the rows included in the totals, rather than a demonstrated numeric extraction failure.

| Municipality | Workbook Total Expenses | Summary Expenses | Exact 2014 Explanation |
| :- | -: | -: | :- |
| Calayan | 70,269,065.82 | 72,090,092.85 | `IS!D21` excludes `D23` Financial Expenses 1,821,027.03, which the summary includes. |
| Camalaniugan | 62,527,727.96 | 61,960,978.96 | Consolidated `IS!C16` MOOE includes the 156,000.00 subsidy and 410,749.00 donations displayed separately in the fund schedules. The summary matches combined operating expenses before those deductions. |
| Claveria | 63,358,293.97 | 63,396,793.97 | `IS!I19` excludes `I24` Bank Charges 38,500.00, which the summary includes. |
| Santa Praxedes | 34,559,911.33 | 35,076,211.33 | `Financial Performance!E19` excludes `E25` Donations 516,300.00, which the summary includes. |

**Calayan:** `IS!D19 + D20 = D21`; `D21 + D23 = 72,090,092.85`, printed on original summary PDF page 2 (printed page 1), in both the table and narrative. The prior-year bridge also holds: `F21` 73,694,805.53 + `F23` 2,072,599.33 = 75,767,404.86. Subsequent subsidies to local agencies/other funds (`D30` 5,014,360.80) are outside summary expenses; subtracting them from income after summary-equivalent expenses matches workbook net income (`D31` 18,990,083.47). Do not add all below-subtotal deductions indiscriminately.

**Claveria:** original summary PDF page 1 includes bank charges for 2014: `IS!I19 + I24 = 63,396,793.97`, and income less this amount equals `I25` net income 27,211,264.61. But the summary's 2013 expenses equal `K19` 64,610,870.85 alone, excluding `K24` bank charges 4,800.00. Including those would give 64,615,670.85. Current and prior summary columns therefore have different finance-cost coverage relative to the workbook. The printed change is arithmetic on the printed amounts, not proof of like-for-like coverage. The source does not state why the treatment differs by year.

**Santa Praxedes:** original summary PDF page 1 equals `Financial Performance!E19 + E25 = 35,076,211.33`; income less this amount equals `E27` net income 2,625,464.38. Its 2013 summary expenses equal `F19` 32,094,271.00 alone, excluding `F25` donations 441,800.00. Including donations would give 32,536,071.00. The current/prior summary columns have different donation coverage relative to the workbook; the compiler's reason is not established. Do not automatically normalize either year.

**Camalaniugan:** `IS!C18` is `=SUM(C15:C17)`, cached as 62,527,727.95999999. Independent cent-precision addition of Personal Services 41,976,811.90, MOOE 20,550,916.06 and zero Financial Expenses gives 62,527,727.96. The floating-point tail is immaterial; this is not a missing cache or an observed cache-versus-input discrepancy.

The original 2014 Annex, `11-Camalaniugan2014-Part4-Annexes.xlsx`, worksheet `Annex 1.2a-b`, contains explicitly dated General Fund and Special Education Fund schedules. Use **both** funds for the diagnostic consolidation bridge; no GF-only M22 figure is substituted for a consolidated measure:

- GF Personal Services `E85` 41,593,736.90 + SEF `E209` 383,075.00 = consolidated `IS!C15` 41,976,811.90.
- GF MOOE `E164` 18,633,163.11 + SEF MOOE `E244` 1,351,003.95 = 19,984,167.06.
- GF PS/MOOE `E165` 60,226,900.01 + GF finance cost `E168` zero + SEF operating expenses `E245` 1,734,078.95 = **61,960,978.96**, exactly original summary PDF page 1.
- GF subsidy to NGAs `E170` 156,000.00 + donations `E171` 410,749.00 + SEF donations `E249` zero = **566,749.00**, exactly the discrepancy. Adding that to combined MOOE gives consolidated `IS!C16` 20,550,916.06.
- GF net income `E174` 4,824,591.28 + SEF net income `E251` 142,134.21 = consolidated `IS!C26` 4,966,725.49. The consolidated statement shows zero separate donations/subsidies at `C23:C24`, with the deductions already reflected in MOOE.
- The 2013 bridge independently repeats this pattern. GF PS/MOOE `F165` 55,798,856.31 + finance cost `F168` 94,287.87 + SEF operating expenses `F245` 1,057,302.57 = summary expenses 56,950,446.75. GF subsidy `F170` 156,000.00 + GF donations `F171` 2,210,500.00 + SEF donations `F249` 20,000.00 = 2,386,500.00, exactly consolidated `IS!D18` 59,336,946.75 minus summary expenses. The MOOE bridge also holds: `F164` 15,917,523.69 + `F244` 843,392.57 + those deductions = consolidated `IS!D16` 19,147,416.26.
- The 2015 original summary retains 61,960,978.96 in its explicitly labeled 2014 comparative expense column on PDF page 2; the corresponding headers/income are on page 1. Page 2 also lists 2014 subsidy to NGAs 156,000.00. Introductory prose contains stale years, so this corroboration uses the labeled table, not that prose. The 2015 FS has only a 2015 performance amount column and does not independently restate 2014.

These exact bridges support a presentation/classification explanation; they do not establish the preparer's intent or authorize either amount as a preferred canonical series. Neither a universal add-finance-cost rule nor a universal add-donations rule is supported. In Claveria and Santa Praxedes, the differing comparative component coverage remains a documented limitation. The original staged `differs_from_summary` observations remain unchanged; no numeric repair or automatic finding dismissal was performed.

### Reproduction

From the repository root in PowerShell:

```powershell
tools/coa/.venv/Scripts/python.exe storage/app/source-review/coa/expense-source-review-261113/inspect_sources.py
tools/coa/.venv/Scripts/python.exe storage/app/source-review/coa/expense-source-review-261113/reconcile.py
```

These review helpers use existing libraries, read-only workbook formula/cache views, PDF text extraction and PDFium renders. Decimal checks independently add displayed source values; they never evaluate formulas in or save a workbook. Full source member names, official source URLs, original checksums, cell contents, caches and PDF page references are retained in the review evidence. `reconciliation.json` has 24 successful source-cell equations; the report links six visually inspected PDF renders.

### Preservation and Verification

All **915 protected files** are byte-identical before and after: 521 original XLSX, 348 original Executive Summary PDFs, 31 canonical/source-register CSVs and 15 parser Python modules. The completed historical stage's existing artifact manifest remains valid. No parser/module/version changes, imports, sign-offs, refreshes, publication, database changes or source recalculations/saves occurred. Code was unchanged, so this follow-up uses source inspection, independent arithmetic assertions, preserved-source and staged-manifest checks, local documentation links and `git diff --check`; it does not claim a new full application test/CI run.

A future automated comparison must establish year-specific component scope and avoid adding costs already included in a total. That implementation is outside this investigation and remains deferred.


Closure on "26-11-13": both documented review commands reran successfully; all 24 equations and 915 preserved-file hashes passed, six PDF page renders were inspected, 74 local Markdown links and `git diff --check` passed. Subsequent owner authorization to ingest 2014–2025 workbooks for all 29 governments is separate work; it does not retroactively sign off these four expense comparisons.
