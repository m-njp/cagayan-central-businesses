---
title: "COA Accuracy and Reporting Differences"
created: "26-11-12"
updated: "26-11-12"
status: completed
branch: "chore/maintainability-refresh"
base: staging
predecessor: "26-11-12-historical-finance-extraction.md"
---

# COA Accuracy and Reporting Differences

Read this whole record before changing `tools/coa/`, the finance import, corroboration or the Report Differences page. Every amount quoted here was checked against the PDF page named.

## 1. Request and Owner Decisions

All on "26-11-12", in order.

1. **Request.** Review the Python COA parser and related scripts for overengineering, underuse, performance and accuracy, especially inconsistent formatting across years. Run the parser, update the map data, and find why the Home map ignores the new 2014 to 2020 data. Keep one source of truth. Research the web, learn from archived plans, ask questions, keep the code maintainable.
2. **Decisions asked and answered:**
   - Publish historical years by **auto-approving only figures two COA reports corroborate**, and add a UI tracker for year-to-year problems. The owner stressed certainty, "because essentially, we are flagging a Government's inconsistency".
   - `extract` **always writes the committed CSVs and prints a diff** (instead of refusing and staging).
   - Analytics **derives its first year from published data**.
3. **Planning pause.** The owner asked to plan first, "so I would properly understand the flags, and ingestion of data". The plan was approved, then implemented.
4. **Correction: decimals.** "Decimal is fine. Don't flag it as wrong." The `one_decimal_amount` warning was removed.
5. **Correction: evidence.** Show screenshots for evaluation, with a guide to what to check. Page images of Alcala 2020, 2021, 2022 and Sanchez Mira 2016 were rendered and sent.
6. **Correction: vague findings.** The findings were too vague ("what do we mean 2017 is different from 2017"). Explain 27, 27 and 26 of 29 on the Home map.
7. **Correction: header years.** "Are you sure wrong year? Perhaps it is just the wrong print … to a point that their recorded values are incorrect." Accepted. A heading that does not match the report is a contradiction; the parser must not say which side is wrong without evidence from another report.
8. **Correction: guide.** Build a full, well-documented guide in the **blur overlay, not a right side panel**, worded for an office or government audience (economic, accounting or legal terms).
9. **Finalize.** Document every finding, change, research item and problem in detail for the next agent, then commit.

## 2. Why the Home Map Ignored 2014 to 2019

- **No sign-off.** Every figure from the 2014 to 2019 reports (1,429 at the start) was **Pending**. `database/sql/metrics/20_stats_finance_year.sql` publishes `verified` figures only (D35), so `stats_finance_year` started at 2019. That year came only from the 2020 reports' prior-year column.
- **Fixed Analytics floor.** `cagayan.performance.first_year = 2019` bounded the year validation in `AnalyticsFiltersRequest` and the year range in `AnalyticsCatalogQuery`. The last year was already derived from data (`FinanceYears::publishedLastYear`); the first year was not.
- **Not a single source of truth.** `extract` refused to write the canonical CSVs whenever any non-arithmetic error existed. COA reports always contain some, so every run went to `storage/app/source-review/coa/parser-runs/` and the 26-11-12 historical batch needed a one-off promotion script.

## 3. Research

Web searches on "26-11-12":

- [Future Nexus, why PDF table extraction fails in production](https://www.heyfuturenexus.com/posts/why-pdf-table-extraction-fails-in-production-and-what-banks-need-to-do-about-it) and [Runpulse, silent failures in financial document extraction](https://www.runpulse.com/blog/the-silent-failures-in-financial-document-extraction). Templates drift between years and filers reuse old templates, so headings and values can disagree. The advice: flag conflicts instead of guessing, normalize headings and units, validate totals and cross-period consistency, and send mismatches to review. These became the stale-header proof, the cross-report corroboration and the "never decide which side is wrong" wording.
- [pdftext](https://pypi.org/project/pdftext/) and the [rpdfium raw-text benchmark](https://retsef.github.io/rpdfium/benchmarks/raw-text) put pypdfium2 far ahead of pdfplumber in speed. The parser already uses both (pdfplumber primary, pdfium for confirmation and fallback). A full run of 348 reports took **3m39s**, so performance was left alone: the Executive Summaries are four or five pages.
- Archived plans read first: [Historical Finance Extraction](26-11-12-historical-finance-extraction.md), [COA Confidence Eligibility Fix](26-11-12-coa-confidence-eligibility-fix.md), [COA Parser Evidence Hardening](26-11-12-coa-parser-evidence-hardening.md) (why confirmation was made row-strict), [COA Parser Hardening 26-10-29](26-10-29-coa-parser-hardening.md) (two engines, the digit confirmation design), and the D34 and D35 decisions.

## 4. Parser Review: What Was Wrong

**Overengineering found:**

- **A second staging pipeline.** The staging refusal in `__main__.extract` plus `coa/audit.py` (208 lines, with its manifests, markers, `comparison.json` and tests) formed a pipeline beside the real one. Replaced for normal use by `extract` writing the committed CSVs and printing a value diff (`reportChanges`, which reuses `audit.compareObservations` and `audit.observations`). `audit.py` and its tests remain: removing them deletes tests, which needs the owner's approval (open item).
- **Unused outputs.** `run.json` and `evidence/*.json` had no reader in PHP. They are now written only for staged `--output` runs, which `audit.py` uses, never into `data/towns/finances/`.

**Accuracy problems found.** The current parser (1.3.0, uncommitted at the time) differed from the committed CSVs by **163 removed, 94 added and 8 changed values**. Each was opened against the PDF page; section 5 lists the outcome. Root causes:

1. **Printing errors triggered the reader fallback.** A COA misprint counted as a reading error, so the parser switched to pdfium, which can miss page 2, and lost good figures (Alcala 2018: `101,724,726,81`).
2. **Row-strict confirmation.** It required pdfium to rebuild each whole row. pdfium breaks lines differently, giving **368 false `engine_disagreement` errors** across 44 reports, including reports people verified on 26-10-28.
3. **Section start.** The first "Financial Highlights" match ended immediately at "Operational Highlights" (a table of contents), giving no rows (Lal-Lo and Santa Teresita 2014).
4. **Audit scope text trusted over the title.** It was trusted over the title and introduction, so whole reports were withheld (Lasam and Pamplona 2017).
5. **Label punctuation read as amounts:**
   - `Appropriations-` read as a minus sign.
   - `Allotments – 58,465,219.29`: a spaced en dash read as a sign.
   - `Total Appropriations - 59,…`: the hyphen read as a not-reported dash cell, which shifted columns. This produced a **wrong committed value** (Santo Niño 2019).
6. **Misattribution.** Every unreadable row was called `malformed_amount`, even when only the PDF text was split.
7. **Text-layer artifacts:**
   - pdfium writes U+FFFE for a soft hyphen.
   - Solana 2014's text spaces a digit off each amount (`1 41,203,192.33`), which was blamed on COA.
8. **Headings taken from prose.** The committed data had Camalaniugan, Baggao, Ballesteros and Lasam rows under the wrong fiscal years because a sentence was read as column headings.
9. **Technical messages.** Findings were unreadable for reviewers ("current minus prior is 26039217.37, stated …").

## 5. Every Report Finding, Checked Against the Page

| Report | Committed or 1.3.0 | Now (1.4.0) | Evidence |
| :- | :- | :- | :- |
| Lal-Lo, Santa Teresita 2014 | No rows (1.3.0) | Read | Page 1 "Financial Highlights" then "A. Operational Highlights"; the table is under the second heading on page 2. Lal-Lo 2014 Income 149,256,835.22 matches page 2 |
| Lasam, Pamplona 2017 | Withheld as another municipality (1.3.0) | Read, `identity_text_conflict` warning | Lasam's introduction names Lasam; its scope paragraph says "Municipality of Abulug". Pamplona's says "Sanchez Mira". COA text carried over |
| Alcala, Baggao, Gattaran 2018; Alcala 2021, 2022; Peñablanca 2022 | Figures lost (1.3.0) | Read | Misprints no longer trigger the fallback; the fuller reading wins |
| 44 reports, 368 `engine_disagreement` (1.3.0) | Errors | 20 | Gattaran 2020: pdfium prints `Total Liabilities 82,006,419.87` on a line its own row parser misses. Iguig 2017: pdfium puts each amount below its label |
| Peñablanca 2022 Allotments Continuing | Withheld | Read | `Allotments – 58,465,219.29` with "Continuing" on the next line |
| Santo Niño 2019 | 2018 Current Appropriations **59,825,645.91** (committed, wrong) | **143,382,611.80** | Page 2 interleaves "Total Appropriations –" and its "Current" label below another row. 59,825,645.91 is the 2019 **Continuing** amount |
| Camalaniugan 2015 | 2014 column held the 2015 amounts (committed) | 2014 Income **67,494,453.45** | The sentence "for CY 2014 compared with that of CY 2013" was read as headings; the table is headed 2015 and 2014 |
| Baggao 2015, Ballesteros 2016, Lasam 2016 | Wrong fiscal years (committed) | Re-keyed | Lasam 2016 Assets 157,794,655.75 equals the 2017 report's 2016 column |
| Alcala 2021 budget rows | Read (committed); withheld (1.3.0) | Read, `header_years_stale` | Page 3 heads the budget rows "2020 2019" but prints 211,666,466.12 and 197,549,721.31. The 2020 report (page 2) shows 197,549,721.31 for 2020; the 2022 report (page 3) shows 211,666,466.12 for 2021 |
| Alcala 2022 Appropriations Continuing | Withheld as a misprint | Withheld, `incomplete_row` (Extraction Limits) | Printed correctly on page 3 (24,874,000.91 / 22,238,928.89 / 2,635,072.02); only the PDF text splits it |
| COA misprints (withheld, `malformed_amount`) | Some salvaged (committed) | Withheld | Tuao 2014 and 2015 `118,321.925.45`; Enrile 2016 `P187.328,242.14`; Baggao 2018 `347,374.045.73`; Santa Teresita 2014 `17,530,755,09` and change `8.744,878.55`; Alcala 2018 `101,724,726,81`; Gattaran 2018 `231,382,293,32`; Santa Praxedes 2016 `48,014,545,75` |
| Rizal 2018 | Read | Read, `engine_disagreement` | The published table cuts its 2017 column off (`69,619,374.5`, `101,853,144.`, `73,876,848.9`); the hidden text holds other digits. Rizal's 2017 report prints .51 and .99. Never corroborated |
| Solana 2014 | Withheld as misprints | Read; 6 figures corroborated | Text layer `1 41,203,192.33`; pdfium prints 141,203,192.33; the change column (15,966,069.10) proves the join |
| Aparri 2015 Liabilities | Read wrongly during this session's first join rule | Withheld | Text `2 5,117,390.91 36,443,935.62 145.09%` lacks the 2015 cell; joining would shift 2014 into 2015. The change-column proof rejects it |
| Sanchez Mira 2016 | Flagged | Flagged, reworded | Page 1: Assets 190,816,666.66 − 164,777,449.29 = 26,039,217.37, but the change column shows 21,271,960.61 (the same for Liabilities and Equity). 2015 Expenses: the 2015 report shows 65,588,576.68, the 2016 report 65,372,576.88 |
| Iguig 2017 | 14 false disagreements | One `header_years_missing` | Year headings split across lines; years assigned from the report, a reviewer confirms |
| Tuguegarao City 2024, 2025 `fs.total_revenue` | Rows | Removed | Check-only lines (26-11-12 confidence fix) |

Other examples used in the guide, all from the issue CSVs:
- Gattaran 2014 (for 2013): Assets 172,874,738.39 against Liabilities plus Equity 159,595,296.19.
- Amulung 2025 (for 2024): Income less Expenses 6,256,992.08 against a printed Net Income of (6,588,299.25).
- Piat 2015: Appropriations Continuing 23,361,451.34 repeats the 2014 report's 2014 amount.
- Alcala 2014: change printed as `(5732,109.29)`; two Total Expenses rows.
- Baggao 2014: Government Equity not found.

**Home map coverage, explained.** Net Income needs both Income and Expenses verified for the same town and year:

| Fiscal year | Income towns | Expenses towns | Net Income towns |
| -: | -: | -: | -: |
| 2014 | 28 | 28 | 27 |
| 2015 | 25 | 24 | 22 |
| 2016 | 20 | 18 | 15 |
| 2017 | 23 | 24 | 22 |
| 2018 | 27 | 27 | 26 |
| 2019 to 2024 | 29 | 29 | 29 |
| 2025 | 13 | 13 | 13 |

The towns missing from 2014:
- **Tuao:** COA misprints Income (`118,321.925.45`).
- **Alcala:** the report prints two disagreeing Expenses rows, so its Expenses are pending.

## 6. Changes by File

### Python parser (`tools/coa/`, extractor 1.3.0 to 1.4.0)

- `coa/__init__.py`: `EXTRACTOR_VERSION = "1.4.0"`.
- `coa/summary.py`:
  - **Families.** `FAMILIES` and `family()` give each issue kind one family (`report`, `between_reports`, `reading`); unknown kinds default to `reading`.
  - **Fallback choice.** `readingErrors()` counts only reading-family errors. `readingQuality()` ranks missing core lines, then figure count, then reading errors. The fallback runs only for missing core lines or reading errors.
  - **Two-tier `confirmDigits()`:**
    - A row the other reader read whole (label and amounts on one line, `readWhole()`) must match exactly in value, definition and page.
    - Otherwise `printedRow()` requires the row's signed amounts within three lines of that page, starting at a line whose label (with wrapped lines above) names the same catalog line. An unqualified "Appropriations" may confirm its Current or Continuing sibling.
  - **Section restart.** `parseText()` tries each highlights heading until one yields rows; the body is now `parseSection()`.
  - **Identity.** `documentContext()` keeps a report whose title and introduction (the first non-scope government named) match, with `identity_text_conflict`. A neighbour mentioned later never counts.
  - **Label punctuation:**
    - A hyphen joined to a word is not a minus.
    - A dangling `–` or `-` label, or a leading dash cell under an unqualified Appropriations or Obligations label, withholds the row as `incomplete_row`.
    - `.rstrip` strips en and em dashes.
  - **Kind of withheld row.** `withheldKind()` picks `malformed_amount` only when `hasMisprintedAmount()` proves the line misprinted, otherwise `incomplete_row`.
  - **Cross-reader text clean-up.** `parseSummary()` reads raw pages once per engine (`rawPages`). `cachedPages` joins split digits using the other engine's page text. pdfium U+FFFE and soft hyphens become hyphens.
  - **Messages.** All rewritten in formal language with `lineLabel()` and `peso()`.
- `coa/numbers.py`:
  - `_LEFT` lets a spaced dash after a letter be label punctuation.
  - `hasMisprintedAmount()` looks for digits or marks left after removing valid amounts, trailing percentages and list numbering.
  - `joinSplitDigits(text, otherReading)` joins a spaced-off digit only when the joined amount is grouped correctly, printed by the other reader on that page, and proven by the row's change column (current less prior).
  - `peso()` formats amounts for messages.
- `coa/checks.py`:
  - `staleHeaderFigures()` restores rows under a header one year behind only when the previous report proves the second column and the first column differs.
  - Messages rewritten.
- `coa/lines.py`: `CATALOG`, a cached `catalogLabels()`, and `lineLabel()`, the one source for line names in messages (`data/towns/finances/lines.csv`).
- `coa/__main__.py`:
  - Always writes canonical CSVs. `reportChanges()` prints and logs counts per document and every value change.
  - `ISSUE_COLUMNS` gains `family`.
  - Stale-header restoration runs beside the swapped-header check.
  - `evidence/` and `run.json` are written only for staged runs.
  - Unused `datetime` and `uuid4` imports removed.
- `coa/intake.py`: uploaded-report issues carry `family`.
- `tests/test_evidence.py`: real-PDF regressions for Lal-Lo 2014, Alcala 2018, Gattaran 2020, Peñablanca 2022, Santo Niño 2019, Lasam and Pamplona 2017, Alcala 2021 (stale header, with a contradicted case), Alcala 2022 (split row), Iguig 2017 (split pdfium rows), Solana 2014 (kerning), Santa Teresita 2014 and Alcala 2018 (misprints). Also unit tests for misprint detection, split-digit joins (including "Note 2 1,234.56" and the Aparri case), one-decimal amounts and message wording. Existing hardening tests were kept; the code was changed to satisfy them.

### Laravel

- `app/Actions/CorroborateFinanceFigures.php` and `app/Console/Commands/CorroborateFinancesCommand.php` (`finances:corroborate`, `--dry-run`).
  - **Pairing.** A pending Executive Summary figure is paired with the next report's prior-year column for the same town, fiscal year, line, basis and definition. The values must be equal; both use the `decimal:2` cast.
  - **Blocking.** A figure is blocked by any `FinanceReportIssues::blocksVerification()` finding, or by any error on that line or across the document.
  - **Sign-off.** Done through `FinanceSignOff::verify()` with no reviewer and the note "Corroborated: the Y and Y+1 COA reports print the same amount". Metrics refresh, and the activity log records `finance_figures_corroborated`.
- `app/Services/FinanceYears.php`: `publishedFirstYear()`, the minimum of the config value and `stats_finance_year`. Used by `app/Http/Requests/AnalyticsFiltersRequest.php` and `app/Queries/AnalyticsCatalogQuery.php`. The `config/cagayan.php` comment was updated.
- `app/Services/FinanceReportIssues.php`: `yearRows(int $year)` reuses the strict CSV reader.
- `app/Queries/ReportDifferencesQuery.php`: counts per municipality and report year by family. Errors and warnings count; of the notes, only Between Reports ones count. Executive Summary rows only. `detail()` returns one report's findings with stored-PDF `#page=N` links and figure status counts.
- `app/Data/ReportDifferences*.php` (five classes), `app/Http/Requests/ReportDifferencesRequest.php`, `app/Http/Controllers/Capitol/ReportDifferenceController.php`. `detail` is `Inertia::optional`.
- `routes/capitol.php`: `capitol.report-differences.index` (`/capitol/report-differences`), open to all three roles.
- `app/Models/ActivityLog.php`: the `finance_figures_corroborated` label.

### Frontend

- `resources/js/pages/capitol/report-differences/`:
  - `index.tsx`: table, family tabs, local history, detail cache, focus return, Guide button.
  - `difference-detail.tsx`: the findings view in the focus overlay; finding names open the guide.
  - `finding-guide.ts`: one source for category names, finding names, checks, examples and effects.
  - `report-differences-guide.tsx`: the Report Differences Guide in the focus overlay.
- `resources/js/lib/workspace-navigation.ts` (sidebar item), `resources/js/lib/icons.ts` (`differences`), `resources/js/types/finance.ts` (types).

### Tests

- `tests/Feature/Finances/CorroborateFinancesTest.php` (12 cases):
  - an agreeing pair, with statistics and activity log
  - one centavo apart, restated, another definition, a single report
  - errors on either report, source arithmetic and a document-wide finding
  - an error on another line
  - an already verified partner
  - the dry run

  The stricter error rule was broken once on purpose to confirm a failure.
- `tests/Feature/Capitol/ReportDifferencesTest.php` (5): counts by family, the optional detail with a stored-PDF link, validation, and workspace access (guests get 404, accounts without a role 403).
- `tests/Feature/Public/EconomicPerformanceTest.php`: verified years before the configured first year extend the catalog and the year filter.

### Data

- `data/towns/finances/extracted/*.csv`, `issues/*.csv`, `documents.csv`: regenerated by extractor 1.4.0. Issues include `family` and formal messages.
- `data/towns/finances/reviews.csv`: 1,166 earlier sign-offs unchanged plus **1,142 corroborated** (2,308 rows). The export quotes notes that contain an apostrophe, which changes the text of earlier rows but not their values (checked by parsing both versions).

### Documentation

- `docs/engineering/decisions.md` D45.
- `docs/engineering/design.md` "Report Differences 26-11-12".
- `data/towns/finances/README.md`: diff workflow, families, corroborated sign-off.
- `tools/coa/README.md`: version 1.4.0.
- `docs/team/roadmap.md`, `docs/plan/README.md`, this record.

## 7. Problems During the Session and How They Were Resolved

- **The import refused** to drop a reviewed figure (Alcala 2021 Appropriations, signed off 26-10-28), because 1.3.0 withholds rows under a stale header. This is the intended safeguard. It was resolved with the proof-based `staleHeaderFigures()`, not by forcing the import.
- **Eight 1.3.0 hardening tests** failed against the first relaxed confirmation. They encode deliberate protections (sign, line, year, definition, page, neighbour mentions, malformed cells), so the code was changed to satisfy them and no test was weakened.
- **First split-digit rule.** It joined "Note 2 1,234.56" into 21,234.56, and later Aparri 2015's shifted columns. Fixed by requiring both the other reader and the row's change column.
- **One-decimal warning.** It was added (Solana 2022 prints `.1` for .10; Rizal 2018 clips `.99` to `.9`) and then removed at the owner's direction. Exact two-report agreement still prevents a clipped value from being corroborated.
- **Mixed fallback rule.** The first "fewer reading errors wins" rule swapped Alcala 2022's 16 figures for pdfium's 10. Completeness now ranks first.
- **A second dev server.** `preview_start` by name launched one on port 8001. `php artisan dev:guard` refused it and it was stopped; the existing server on 8000 was reused (memory rule "one dev server").
- **Wayfinder.** `php artisan wayfinder:generate` without `--with-form` dropped the `.form` helpers that `wish-form.tsx` uses. Regenerate with `--with-form`, as `vite.config.ts` does.
- **Shell escaping.** Shell heredocs mangled `￾` and `\d` in Python edits. Use the Edit tool or a script file for such edits. A stray `cat > file` with no input hung a command.
- **Report Differences UI defects:**
  - The detail cache matched by town only, not year.
  - The status dot used color alone; Pending is now a hollow ring.
  - Closing the findings view did not return focus to its cell.
  - At 375px the findings table overflowed the dialog (fixed with `min-w-0` and a scrolling table).
  - Opening the guide over the findings view stacked two overlays wrongly; the findings view now steps aside while the guide is open.
- **Browser pane:**
  - A dark-mode check at 375px was not possible: the pane forces the app theme.
  - The demo session expires after 30 idle minutes (404 on Capitol pages); sign in again at `/staff-sign-in`.
  - A pre-existing SSR message ("renderToString does not support Suspense") appears in the console. It was not introduced here.

## 8. Data State and How to Reproduce It

The database is local. The committed CSVs reproduce it:

```bash
composer data:finances
```

That replays `reviews.csv`, including the 1,142 corroborated sign-offs, and rebuilds `stats_finance_year`. `composer db:reset` does the same from scratch.

After a parser change:

1. `composer data:finances:test`
2. `composer data:finances:extract`, then read the printed diff and check every value change against its PDF page.
3. `composer data:finances`
4. `php artisan finances:corroborate --dry-run`, then compare the count with a plain SQL count of value-equal pairs.
5. `php artisan finances:corroborate`
6. `php artisan finances:export`

Pending and verified Executive Summary figures by report year at the end of the session:

| Report year | Pending | Verified | Superseded | Carrying a corroborated note |
| -: | -: | -: | -: | -: |
| 2014 | 116 | 0 | 114 | 114 |
| 2015 | 17 | 114 | 105 | 219 |
| 2016 | 49 | 105 | 82 | 187 |
| 2017 | 63 | 82 | 99 | 181 |
| 2018 | 37 | 99 | 102 | 201 |
| 2019 | 35 | 102 | 105 | 207 |
| 2025 | 37 | 127 | 0 | 31 |

115 of the 2014 report's 116 Pending figures are its 2013 comparative column, which no collected report can corroborate (the 2013 reports were not collected); the other is one 2014 figure held back by a finding.

Final extraction (1.4.0): 348 reports, 5,511 figures, and 88 errors, 227 warnings and 270 notes in all. Executive Summary findings by family:

| Family | Errors | Warnings | Notes |
| :- | -: | -: | -: |
| Within the Report | 37 | 72 | 35 |
| Between Reports | 11 | 1 | 191 |
| Extraction Limits | 35 | 125 | 0 |

## 9. Verification

- **Python:** 714 COA tests pass (`composer data:finances:test`).
- **Laravel:**
  - The full `composer ci:check` passed before the guide work: frontend suites, Pint, PHPStan 0 errors, 481 Pest tests, 3,517 assertions.
  - After the guide work: `ReportDifferencesTest` (5) passed; `npm run check`, `npm run types:check` and `npm run build` passed.
  - A final `composer ci:check` runs before the commit; its result is in the commit handoff.
- **Ledger safety:** the Verified figures (720) and the 993 published statistic rows were hashed before and after the 1.4.0 import, and the fingerprints were identical. All 1,166 earlier sign-offs are kept.
- **Corroboration cross-check:** the first dry run counted 1,136; a plain SQL count found 1,175 value-equal pairs, and the difference is figures blocked by findings. A later run added Solana 2014's 6.
- **Browser (port 8000, local demo accounts):**
  - Home lists fiscal years 2014 to 2025; 2014 shows Income ₱3,257,653,788.89.
  - Report Differences: tabs, closing, reopening and Back/Forward made zero application requests. Focus returns to the opening cell. A direct link loads one report once. At 375px there is no page overflow.
  - The findings view shows the formal wording; finding names open the guide at their entry; the header Guide opens the guide.
- **Page images** for the owner's review are in ignored `storage/app/source-review/coa/review-261112/screens/`. The six canonical run logs are in the same folder.

## 10. Open Items for the Next Agent

1. **Remove `tools/coa/coa/audit.py`?** It, its tests and `composer data:finances:audit` duplicate the diff now in `extract`. Removing them needs the owner's approval because it deletes tests.
2. **Reviewer queue.** About 350 Pending Executive Summary figures in the 2014 to 2019 and 2025 reports need a person (Government Finances review). The Report Differences page lists why each is held.
3. **Province Not Stated** (123 warnings) is the largest Extraction Limits group. It is informational and could become a note if the owner prefers a quieter tab. Not changed without asking.
4. **Unchecked views.** The guide at phone width and in dark mode, and the findings view in dark mode at 375px.
5. **Second reviewer.** No second person has reviewed the corroboration rule (D45). The owner directed it.
6. **Scope.** Report Differences covers Executive Summaries only. Workbook findings stay on the Government Finances report screen.
7. **SSR console message.** The pre-existing "renderToString does not support Suspense" message remains.
