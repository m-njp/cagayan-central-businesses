---
title: "Government Finances Report Redesign"
created: "26-11-03"
updated: "26-11-14"
status: superseded
successor: 26-11-14-executive-summary-only.md
branch: feat/finance-report-redesign
base: feat/data-ingestion-rebuild at 869cf87 (baseline 2b96ff4)
predecessor: 26-10-29-executive-summary-view.md
---

# Government Finances Report Redesign

## Closure "26-11-14"

Superseded by later owner corrections in `design.md`, D47 and D49 and [Executive Summary Only and Faster Tests](26-11-14-executive-summary-only.md). The current interface keeps the polished Executive Summary, header controls and Capitol figure strip. Later decisions replace the trailing Change column, synchronized bar charts and Help sheet with per-year arrows, area charts and the shared tabbed guide. Workbook upload, workbook breakdowns and the old 12-workbook-figure sign-off blocker are retired, not unfinished work to restore.

Original acceptance items and failed-run evidence remain below as history. Current tests and review limits belong to the successor; no missing historical browser check is retroactively marked passed. Live upload work continues separately under [Live Finance Parsing](../active/26-11-08-live-finance-parsing.md).

## Objective

Step 1 of 4 agreed on "26-11-03" after the [abandoned rebuild](../archive/26-11-02-data-ingestion-rebuild.md) was reverted. Make Government Finances on public Economy and Capitol Analytics easier to read by polishing the existing Executive Summary, not replacing it. Public Economy shows citizens how their town government did year after year; Capitol Analytics adds a compact figure strip for staff. Reuse the baseline components and formatters; no new layout system.

Later steps get their own plans: (2) finance data fixes with one new migration, (3) Continuing Appropriations in the parser, (4) approvals, population in the database and clearer entry forms on the existing separate Capitol pages.

## Scope and Acceptance

**Finance data fixes (added "26-11-03" at the owner's request to keep the rebuild's good backend work)**
- [x] Parser 1.1.0 from the rejected stash: per-year cell citations, every contributing SCBAA cell, the signed Real Property Tax discount, `input_values`.
- [x] Migration `2026_11_02_222442_add_inputs_to_finance_figures`: `inputs` jsonb and `location` 255 (`cagayan.finances.location_max`), with a working rollback (up, down, up checked).
- [x] Source-reported Shares, Grants and Donations and Other Income replace the calculated Other Revenue; revenue lines use the statement's labels. 2025 Tuguegarao lines sum to Income, ₱1,679,019,473.60.
- [ ] Independent sign-off of the 12 newly loaded figures by a team member (pending; `FinanceImportTest` "imports the committed COA data signed off" fails until then).
- [x] Source evidence carries each figure's contributing cells and amounts (`catalog.sources.*.inputs`).

**Run the parser from the Capitol (added "26-11-03")**
- [x] Upload a report on Capitol Finances (PDF, XLSX or ZIP); `ExtractFinanceUpload` runs `coa.intake` on that file only; figures arrive Pending credited to the uploader, who cannot sign them off (D42).
- [x] Same file read once (sha256); a different report already on record is never overwritten; failures show a short message and manual entry stays available.
- [x] Uploaded documents stay on the private disk and out of the CSV import and export; `composer doctor` reports the parser venv; deployment.md, database.md and decisions.md D42 updated.
- [x] Tests: 5 Pest upload tests (authorization proven by breaking it once), 5 Python intake tests. A real run on the committed Tuguegarao 2025 workbook read every figure, matched the ledger and kept 4 parser warnings.

**Table (both pages, `components/charts/executive-summary.tsx`)**
- [x] Executive Summary stays the first view: rows are COA lines, columns are fiscal years, oldest to newest.
- [x] Visible vertical column lines and alternating row shading from tokens (`bg-muted` family), readable in light and dark.
- [x] Section rows (Financial Condition, Results of Operation, Budget Execution) in Title Case Geist, not uppercase `type-label`.
- [x] A trailing Change column (latest year against the year before) with up and down arrows replacing the triangles in `lib/trend.ts`. Arrows mark direction only, never a rating; Net Income loss keeps parentheses with no color.
- [x] No "2019 to 2025" range caption anywhere; years appear only in column headers and chart axes.
- [x] Missing values read Not available, never 0; abbreviated amounts keep `ExactAmount` on hover, focus and tap.

**Controls (one row in the card header)**
- [x] Range chips 3Y, 5Y, All (All replaces Max), styled as one compact segmented group.
- [x] A Year select for one fiscal year, which opens the existing Yearly Summary for that year.
- [x] Icon toggle Table or Charts (lucide icons with accessible names), replacing the View dropdown; Charts is the existing Trends content.
- [x] Icon Help button opening the existing Help sheet.
- [x] All selections in the URL through the existing `pushFilters`, with zero application requests, including Back and Forward.
- [x] Capitol Analytics uses the same header row; its sidebar keeps only the town and Business Trends pickers.

**Charts (below the table, existing components)**
- [x] Results of Operation: Income and Expenses as grouped bars with Net Income as a line over a zero line (`finance-history-chart.tsx`, Recharts 3 `ComposedChart`).
- [x] Financial Condition: keep the stacked Liabilities and Government Equity bars (`financial-condition-chart.tsx`).
- [x] Missing years stay gaps (`financeChartSeries`, `connectNulls={false}`), never zero bars.
- [x] Better tooltips: one compact card with the year, a color swatch, label and exact amount per series, and Net Income in parentheses for a loss. No source text, no paragraphs. Charts share one `syncId` so hovering a year highlights it in both.

**Capitol only**
- [x] A compact figure strip above the table: Income, Expenses, Net Income and Assets for the newest year in range, short amount with `ExactAmount`, arrow change against the year before. Not shown on public Economy.

**Retired indicators**
- [x] Per Resident, Locally Generated Share, Budget Utilization and Development Fund and Economic Services Utilization are no longer computed or shown: derived blocks removed from `database/sql/metrics/20_stats_finance_year.sql`, rows set `displayed` 0 in `data/towns/finances/lines.csv` so the existing import hides them without a migration, config groups, `censusYear`, Help content and `metrics.md` updated with retirement history.

**Copy and rules**
- [ ] No em dashes, en dashes or middots in any changed interface text.
- [x] `design.md` "Executive Summary View" updated with these decisions; superseded lines recorded, not erased.

## Deferred or Excluded

- Revenue Sources and Business Taxes and Fees ranked bars, and Budget and Actual panels: considered in the "26-11-03" wireframe, deferred until step 2 corrects their source rows.
- Any performance score, rating or official-by-official comparison. The report shows yearly history; readers draw conclusions.
- Maps, Home, sidebar, entry forms, approvals, population and parser work (later steps).
- New packages, migrations, Docker changes.

## Decisions and Sources

- "26-11-03" Owner reverted the rebuild; keep the Executive Summary table as the anchor because it is historical and easy to read.
- "26-11-03" Owner asked for visible column lines, alternating row color, better tooltips, arrows instead of triangles, no em dashes, and no figure strip on the public side. Supersedes the triangle and `type-label` section rules in [design.md](../../engineering/design.md), "Executive Summary View".
- "26-11-03" Recharts 3.10.1 is installed: grouped bars plus line in `ComposedChart`, `ReferenceLine` at zero, `syncId` shared tooltips. Docs: https://recharts.github.io/en-US/api/ComposedChart/ and https://github.com/recharts/recharts/wiki/3.0-migration-guide.
- "26-11-02" The four indicators were retired for unverified formulas (archived rebuild plan, Financial Decisions); that decision is kept.

## Change History

| Date | Change | Reason / Reference |
| :- | :- | :- |
| "26-11-03" | Plan created as step 1 after the revert | Owner review of the wireframe in this session |
| "26-11-03" | Added the finance data fixes, accounting labels from the reports, and Capitol parser upload | Owner: a frontend-only pass would not fix the original data entry and transparency problems |
| "26-11-03" | Owner review of the Charts view: removed the figure tiles, one area chart for Results of Operation, stacked areas for Financial Condition and Revenue Sources, grouped bars for Budget Execution, no synchronized tooltips, one tooltip for an amount and its change, table lines and alternating rows as the shared default, Budget and Actual Amounts withdrawn from display | design.md "Dense Data Rule" and "Finance Charts"; metrics.md M23 |

## Verification and Handoff

"26-11-03" so far: 83 parser tests passed. Finance Pest files: all pass except two. The committed-data sign-off check waits for a human sign-off. `FinanceReviewTest` expects the old "Government Totals" label and also fails on the untouched baseline (owner to confirm the fix). 17 frontend unit tests passed. `npm run check`, TypeScript and build passed. Browser: Economy at 1280px dark and 375px light, Capitol Analytics at 1280px dark; table, chips, Year, Table or Charts toggle, synced tooltips. Toggle, toggle back and Back made zero application requests. An earlier "full CI passed" claim in this session was wrong: a pipe hid the exit code.

Full CI ("26-11-03", real exit code): frontend format and lint, TypeScript, Pint, PHPStan pass; Pest 332 of 334. The two failures are the owner decisions below. Privacy review: no business data or uploader identity in public responses. Fixed after review: uploads stay private (no public file or link), workbook inputs keep only cell and amount, a re-read never rewrites a signed-off citation, PDF reader exception text is replaced. Upload, economy and source tests rerun: 36 passed. Design check: the strip repeated the year on every label; it now shows once.

Owner decisions "26-11-03": FinanceReviewTest now expects Results of Operation (33 passed); uploaded reports stay private (D42). Still open: sign off the 12 new figures; inputs in the review row and an upload rate limit are deferred.

Remaining planned checks: affected Pest and frontend tests, `npm run check`, `npm run types:check`, `npm run build`, `composer ci:check`, `/design-check`, `/privacy-check`, browser at desktop and 375px in both themes with keyboard and touch, Back and Forward with no application requests.
