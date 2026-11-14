# Government Finances

Workbook reading is retired, "26-11-14" (D49): the Executive Summary is the only source of figures. `extracted/`, `issues/`, `documents.csv` and `reviews.csv` hold Executive Summary rows only, and `lines.csv` has no `intake` column. Workbook rows are frozen under `history/` and never imported. Paragraphs below that describe workbook intake are history.

Current scope "26-11-14" (D48): Executive Summary parsing is unchanged. Active workbook intake/display is only Tax Revenue, Share from National Collections and Service and Business Income. Grants and Other Income remain internal reconciliation evidence. Annex and SCBAA financial parsing are deleted. Historical non-intake observations, including Pending rows, replay from checksum-verified `history/26-11-14-workbook-retirement/`; promotion never rewrites that history. Originals, source registers, reviews and all individual/ZIP downloads remain intact. The earlier nine-field handoff below is historical, superseded by D48.

Current workbook handoff, "26-11-13": **2,177 new Pending observations** for all 29 governments, report years 2014–2025, restricted to the nine extracted Tuguegarao reference fields. The initial extra 208 Other Shares from National Taxes observations were removed while still unreviewed. No pre-existing figures/sign-offs or published statistics changed. Source copies now use municipality folders; [the workbook index](../financial-statements/index.csv) and [developer/AI handoff](../../../docs/engineering/coa-workbook-handoff.md) explain current scope, UI exclusions, formats and safe commands. Avoid unscoped legacy extraction over this curated batch.

Figures read from COA reports for Cagayan's 28 municipalities and Tuguegarao City. The parser in `tools/coa/` writes most of these files (D34). `composer data:finances` (`php artisan finances:import`) loads them into the finance ledger (D35) as **pending** figures, then replays the sign-offs in `reviews.csv` and `manual-figures.csv`. Nothing is published until a person signs it off, except the explicitly owner-directed batches recorded under D45 and in the plan history. `composer db:reset` runs the import too.

2025 Income and Expenses verification completed "26-11-12": all 29 municipalities/city now have verified pairs. At the owner's explicit direction, the agent checked the remaining 32 amounts against rendered COA PDF pages, including year columns and labels, and saved the sign-offs in `reviews.csv`. Published Income, Expenses and derived Net Income each cover 29/29. Existing change-column warnings remain, including Camalaniugan's printed arithmetic discrepancies. This batch does not approve other years or lines. [Page evidence, source hashes and verification](../../../docs/plan/archive/26-11-12-coa-2025-verification.md).

Every Executive Summary loads for all 29 towns. Public citations open the stored copy of the document (`file` in `documents.csv`) through `public.finance-sources.show`, at the page parsed from the figure's `location` ("page 2"); the COA address in `source_url` stays here as provenance only. Workbook breakdowns load for all 29 governments in `cagayan.finances.workbook_towns`, explicitly approved by the owner on 26-11-13 (D46), using the nine Tuguegarao reference fields listed in the handoff and replacing the former Tuguegarao-only municipality restriction. New workbook figures enter Pending review; source/year/fund safeguards and publication gates remain in force. Unsupported or absent statements remain unavailable.

The 26-10-28 sign-offs remain in `reviews.csv`; see "Reliability Review, 26-10-28" below for that historical batch. Later extractions, including the batch below, remain Pending until independent verification.

Definitions: `docs/engineering/metrics.md`, M19 to M27.

Known parsing follow-up, "26-11-12": the two flagged Tuguegarao City 2025 workbook totals at `8 - Financial Performance 2025!D14` and `F14` match their stored amounts. They are internal `fs.total_revenue` check lines, correctly read by Python but excluded from eligible intake. The upload omission guard incorrectly treats them as missing and forces confidence to 0. No amounts or sign-offs were changed. See the [active fix and handoff](../../../docs/plan/archive/26-11-12-coa-confidence-eligibility-fix.md).

## Corroborated Sign-Off "26-11-12"

`php artisan finances:corroborate` (D45) signed off 1,142 Pending Executive Summary figures that a year's own report and the next report's prior-year column print identically to the cent, with no blocking or error finding on that line or document in either report. Each carries the note "Corroborated: the Y and Y+1 COA reports print the same amount" in `reviews.csv`. A plain SQL count found 1,175 equal pairs on the first run; the others are held back by findings. Six more (Solana 2014) followed once a text-layer gap was read correctly. Published fiscal years now start at 2014, with 18 to 28 municipalities in each of 2014 to 2018. Restated, single-report and flagged figures stay Pending; the Capitol Report Differences page lists them with their PDF pages. Verified figures and published statistics from before the run were unchanged by the import (fingerprints compared).

Run it after `composer data:finances`, read the `--dry-run` counts first, then `php artisan finances:export`.

## Historical and Remaining 2025 Batch "26-11-12"

Extracted the 174 Executive Summaries for 2014 through 2019 and the 16 newly collected 2025 reports. The 190 reports produced 2,919 catalog observations with exact amounts, PDF page locations, source labels and document SHA-256 values. Both current and comparative fiscal years are retained. Original reports remain under `../executive-summaries/`.

The existing import scope loads 1,497 new observations into the finance review queue as Pending. The remaining 1,422 financial-condition/allotment/continuing-obligation detail observations stay in the extraction CSVs because `cagayan.finances.summary_detail_towns` still enables those lines for Tuguegarao City only. Workbook scope and metric definitions are unchanged.

| Report year | Reports | Extracted observations | New Pending observations |
| :- | -: | -: | -: |
| 2014 | 29 | 450 | 232 |
| 2015 | 29 | 457 | 238 |
| 2016 | 29 | 461 | 236 |
| 2017 | 29 | 472 | 244 |
| 2018 | 29 | 456 | 238 |
| 2019 | 29 | 457 | 241 |
| 2025 | 16 | 166 | 68 |

All 190 reports have figures in the queue. Each figure links to the registered stored PDF with its `#page=N` citation. Review access starts at fiscal year 2013 because the 2014 reports include that comparative column. This does not extend the published series or approve any figures.

Source exceptions remain visible in `issues/<year>.csv` and the report review screen. In particular, Santa Praxedes 2016 prints its current Income as `48,014,545,75` on page 1, and Gattaran 2018 prints the comparative Income as `231,382,293,32` on page 2. These malformed main rows are not guessed; reviewers must transcribe/resolve them from the source. Other arithmetic discrepancies, conflicting rows, four year-header mismatches and three second-reader digit disagreements remain flagged for review.

Extractor 1.2.0 supports older `P=` currency marks, amounts printed without thousands separators, percentages without `%`, longer wrapped qualifiers and readable amounts beside malformed change/percentage columns. Malformed trailing columns generate warnings; malformed main amounts remain unavailable.

The full parser output was staged and only the requested source batch was promoted programmatically. All 2,541 pre-existing extracted observations were preserved, including workbook rows outside this task. The import transaction confirmed that all 1,178 pre-existing ledger observations and review decisions remained unchanged, along with all 993 published statistic rows. No new sign-offs were added to `reviews.csv`. Local baseline, target inventory, full output, promotion script and transaction receipts are retained under ignored `storage/app/source-review/coa/finance-extraction-261112/`.

See the [extraction plan](../../../docs/plan/archive/26-11-12-historical-finance-extraction.md) for checks and handoff. A future full extraction must be diffed before import; it also regenerates workbooks and can change source metadata outside a selected PDF batch.

## Files

| File | What it holds | Written by |
| :- | :- | :- |
| `lines.csv` | The line catalog: code, label, group, source, whether it is displayed, and the source-label aliases the parser matches | People. Change it with a metric definition change |
| `documents.csv` | One row per town, fiscal year, and document kind: `available` with its file, URL, and SHA-256, or `not_published` when COA had no report on the date checked | Parser |
| `extracted/<year>.csv` | Every stored figure read from the reports and workbooks for that year, including the prior-year column | Parser |
| `issues/<year>.csv` | Every failed check, unreadable header, or missing line, with severity | Parser |
| `reviews.csv` | Sign-offs of parsed figures: the report, line, year, basis, the value signed off, `verified` or `rejected`, the date, a note | `php artisan finances:export` |
| `manual-figures.csv` | Figures entered by hand in the Capitol, with their status | `php artisan finances:export` |

The parser rewrites `documents.csv`, `extracted/` and `issues/` on every run and prints the values it added, removed or changed against the previous files. Read that diff before importing. Do not edit them by hand. Corrections go through the Capitol review screen (Phase 3), or through `lines.csv` aliases followed by a new run.

After signing off or entering figures in the Capitol, run `php artisan finances:export` and commit `reviews.csv` and `manual-figures.csv`. Otherwise the next `composer db:reset` loses them. A sign-off applies only to the value it names: if a new parser run changes that value, the figure goes back to pending and the import prints a warning.

## extracted/<year>.csv

| Column | Meaning |
| :- | :- |
| `reported_in_year` | The report the figure was read from. The year a file is named after |
| `document_kind` | `executive_summary`, `fs_workbook` or `annex_workbook`; with the town and year it names the document in `documents.csv` |
| `fiscal_year` | The year the figure describes. A 2020 report also gives 2019 |
| `line_code` | A code from `lines.csv` |
| `basis` | `actual`, or `budget_final` for the SCBAA final budget |
| `value` | Pesos with cents, negative for a deficit |
| `definition_variant` | Set when the report's own label states a different definition. Two examples: `includes_subsidy_from_other_funds`, and `unspecified_period` when a summary says only "Appropriations" without current or continuing. Change is not computed across a variant (M26) |
| `source_label`, `location` | The label as printed, and the page or `sheet!cell`, for the reviewer |
| `document_sha256`, `extractor_version` | Which file, and which parser rules, produced the row |

## Checks

Each Executive Summary is checked on its own:

- Assets = Liabilities + Equity, within ₱1.00.
- Revenue minus Expenses = Net Income, when the report states Net Income.
- The stated change column equals current minus prior.

Each report is also compared with the previous year's report:

- A prior-year figure that differs from that report is `restated`. This is informational, and the latest report wins (D35).
- A current-year figure equal to the previous report's own figure is `repeats_previous_year`, an error. It usually means the column headers are swapped.
- **Mislabelled column headers (`header_years_swapped`, a warning, extractor version 2):** when the column headed with the report year repeats the previous report's own revenue and expenses to the cent, and the stated change column only adds up with the columns the other way round, the parser reads the columns in printed order. Both proofs are required for revenue and expenses, so a report that only prints its change as prior minus current (Abulug 2021) is never swapped. Gattaran 2024 is the only report that meets them.

Each statement workbook is checked too:

- Every revenue line in the Statement of Financial Performance adds up to its Total Revenue (`revenue_sources_mismatch`, an error).
- The workbook's revenue and expense lines are compared with the same town's Executive Summary (`differs_from_summary`, a warning). A difference often means a different definition, such as transfers counted as revenue.
- A statement with no national share line marks Local Taxes `may_include_national_share` (`national_share_not_separate`).
- A sheet titled with another year, a missing sheet, and spending under headings outside the catalog are reported too.

Only the lines in `lines.csv` outside the `checks` group are written. The `checks` lines (assets, liabilities, equity, net income, allotments, other revenue lines, expense types, other sector headings) are read for these checks and to find where sections end.

Each issue also has a `family`, shown on the Capitol Report Differences page (D45):

| Family | Meaning |
| :- | :- |
| `report` | Within the Report: the document disagrees with itself as printed, such as a misprinted amount, a change column that does not add up or a header naming the wrong year |
| `between_reports` | Between Reports: two COA reports print different amounts for the same year (`restated`) |
| `reading` | Extraction Limits: a limit of the parser, such as a row split across lines or two readers disagreeing. Never a statement about the government's report |

Severity tells the reviewer what to do:

| Severity | Meaning |
| :- | :- |
| `error` | The figures disagree with each other, or may be in the wrong year. Check the page before signing off |
| `warning` | Something unusual that does not change the stored values, such as a typo in the change column or a repeated header year |
| `info` | Expected source behaviour: restatements, or a change column printed as prior minus current |

## Running

```bash
composer data:finances:test
```

```bash
composer data:finances:extract
```

The first run creates `tools/coa/.venv` and installs the pinned `tools/coa/requirements.txt`. The tests read the committed PDFs. Their expected values were read from the PDFs by hand, and they include the four 2024 pairs verified against COA workbooks (`../verified-finances-2024.md`) and every cell of the six Tuguegarao City highlights tables (`tools/coa/tests/test_tuguegarao.py`).

Python in `tools/coa/` names variables, parameters, fields and functions in camelCase (user rule, 26-10-29). Constants stay UPPER_CASE, and CSV column names stay snake_case because the PHP import reads them.

### Two PDF Engines and the Run Log

Since extractor version 3 (26-10-29), each Executive Summary is read with two independent text engines, pdfplumber and pdfium (`pypdfium2`, already pinned as a pdfplumber dependency):

- **Read:** pdfplumber reads the table. When it misses a core line or fails, the parser rereads the PDF with pdfium and keeps that reading if it finds more core lines (`read_with_fallback_engine`, a warning; `engine_failed` records the failure).
- **Confirm:** every value must also be printed on its page as the other engine reads it. A value the other engine cannot find is `engine_disagreement`, an error. This proves digits, not rows; the arithmetic checks and the tests cover rows.
- **Isolate:** a document that crashes the parser gets a `parser_failed` error row, and the run continues with the next document.
- **Log:** every run rewrites `tools/coa/logs/extract.log` (not committed): each document's stored figure count, every issue, fallbacks, and full tracebacks. Warnings and errors also print to the terminal.

The 26-10-29 run had no `engine_disagreement`, fallback or failure in any of the 158 summaries. For Tuguegarao City both engines read the same 22 figures from each of the six reports.

## Current Run, 26-10-28

Scope was trimmed to a business focus on 26-10-28 (M21 to M23). On 26-11-03 the parser (version 1.1.0) began citing every contributing cell with its amount in `input_values`, and the reported Shares, Grants and Donations, Other Income and Total Revenue rows replaced the calculated Other Revenue for Tuguegarao City 2024 and 2025. Those rows load as Pending and need an independent sign-off. The run reads 158 Executive Summaries for 2020 to 2025 and the 17 workbook pairs in `../financial-statements/`.

Reports and workbooks containing each stored line, current year only:

| Line | Source | Reports with the line |
| :- | :- | -: |
| Government Revenue, Government Expenses | Executive Summary | 158 of 158 |
| Current Appropriations | Executive Summary | 108 |
| Current Obligations | Executive Summary | 109 |
| Local Taxes, Fees and Business Income | FS workbook | 17 of 17 |
| National Tax Allotment Share | FS workbook | 16; Enrile 2025 combines it with Local Taxes |
| Economic Services, with budget | FS workbook | 17 |
| 20% Development Fund, with budget | FS workbook | 16 |
| Business Tax, Permit Fees | Annex workbook | 15; Abulug and Allacapan 2024 have no General Fund detail |
| Real Property Tax | Annex workbook | 14 |
| Market Operations | Annex workbook | 13 |

Before the trim, the independent `pdftotext` (Xpdf) text of each PDF contained every Executive Summary value.

Notable errors:

- **Gattaran 2024:** the summary heads its columns "2024 2023" but prints 2023 first. Its first column repeats the 2023 report's own figures, and its change column (₱18,941,613.27 revenue) equals 401,727,932.51 minus 382,786,319.24. Since extractor version 2 the parser reads printed order (`header_years_swapped`), giving 2024 revenue ₱401,727,932.51 and expenses ₱408,270,884.16, which the 2025 report confirms.
- **Amulung 2025 and Rizal 2025 (`net_income_mismatch`):** they state a net income that is not revenue minus expenses. Their revenue and expenses match the PDFs to the cent, and their percent and change columns agree, so only COA's own net income line is inconsistent. Net income is not stored; Surplus or Deficit is computed from revenue and expenses (M19).
- **Santo Niño 2025 (`revenue_sources_mismatch`):** its revenue lines miss an unlabeled ₱22,392.82 row, in both years. Its workbook is not loaded yet.

Warnings cover change-column typos, repeated header years, workbook-to-summary differences, and Gattaran's 2025 budget sheet titled 2024. Counts are printed at the end of every run.

Apart from reading mislabelled columns in printed order when both proofs hold, the parser never corrects a source error. The reviewer decides.

## Reliability Review, 26-10-28

Before signing everything off, each report's current-year revenue and expenses were compared with the next year's report, which repeats them as its prior-year column (2020 to 2025 reports, all 29 towns):

| Result | Pairs |
| :- | -: |
| Same to the cent | 217 |
| Columns swapped in the later report (Gattaran 2024, now read correctly) | 2 |
| Different: a later report restated the year | 39 |
| No later report to compare | 32 |

Most restatements are under 1 percent. The largest were opened in the PDFs and are printed that way by COA, not misread: Enrile 2021 revenue (₱181.0 million, restated as ₱232.2 million in 2022), Rizal 2024 expenses, Calayan 2022 expenses, and Santa Ana 2020 revenue. The latest report wins (D35), and the screens say "Restated in the 2022 report" (M26). Aparri 2020 revenue differs by ₱0.63 between reports, a digit swap in one of COA's documents.

Reading a value correctly does not make COA's accounts correct: the figures are transcriptions of the reports, which are themselves audited with qualified opinions in many towns.

## Executive Summary Lines, 26-10-29

Since 26-10-29 the parser also stores Assets, Liabilities, Government Equity, Current Allotments, Continuing Allotments and Continuing Obligations (metrics.md M35) for every town. `finances:import` loads them only for the towns in `cagayan.finances.summary_detail_towns`, Tuguegarao City for now; the other towns keep them in `extracted/` until they are added there.

The 72 Tuguegarao figures were approved automatically at the project owner's direction on 26-10-29 (`reviews.csv`), after every cell of the six highlights tables was checked against page images (below). No second person reviewed them, as D35 normally requires.

## Tuguegarao City Cell Check, 26-10-29

Every cell of the six Tuguegarao City highlights tables (2020 to 2025 reports: 12 rows in 2020 and 11 in later years, each with current, prior and change columns, 201 cells) was typed from rendered page images and compared with the parser. All 201 agree to the cent, and pdfium reads the same values.

One label was wrong. The 2021 and 2022 reports wrap "Appropriations – Current" over two lines, and extractor version 2 stopped at "Appropriations", marking those four rows `unspecified_period`. That variant blocked the annual change for 2021 and 2022 (M26). Version 3 reads the wrapped qualifier. A scan of all 158 summaries found no other report with this layout. The values did not change, so the import replayed their sign-offs.

The 2022 report prints Obligations – Continuing as ₱1,044,554,592.87, the same amount as Obligations – Current, and the 2023 report repeats it. It is COA's figure as printed. Continuing lines are read for checks only and are never stored.

## Economic Maps "26-10-28"

Public Home and Capitol economic maps read the same `stats_finance_year` output as Analytics. Successful imports and completed review batches rebuild finance statistics automatically in their transaction; `metrics:refresh` remains available for explicit rebuilds. Latest is the newest verified fiscal year with a revenue/expense pair for any town, globally. Missing towns stay unavailable in that year. The year list comes from verified observations, not a fixed calendar range. Report collection and independent human sign-off remain required. Revenue, expenses, surplus/deficit, financial shades, source evidence and reporting-town coverage share the existing definitions.
