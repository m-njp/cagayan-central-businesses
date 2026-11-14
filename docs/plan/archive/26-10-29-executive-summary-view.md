---
title: "Executive Summary View and Net Income"
created: "26-10-29"
updated: "26-11-14"
status: superseded
successor: 26-11-14-executive-summary-only.md
branch: "feat/business-registry"
base: staging
predecessor: ../archive/26-10-29-finance-trends.md
---

# Executive Summary View and Net Income

## Closure "26-11-14"

Superseded by [Executive Summary Only and Faster Tests](26-11-14-executive-summary-only.md), D47 and D49. The Executive Summary default, COA wording and Net Income behavior remain implemented; Financial Condition and Budget Execution are no longer restricted to Tuguegarao City. Current design rules replace the early table and source treatments below.

The original unchecked browser and review items are preserved as history, not claimed as newly executed. The successor records current automated verification, static design/privacy review and the remaining visual-review limitations. Do not resume this obsolete scope or restore `summary_detail_towns`.

## Objective

Answer: What did this town's government own, owe, earn, spend and budget each year, as COA printed it? On "26-10-29" the user found a plain table of the Tuguegarao Executive Summaries clearer than the finance screens and asked for a simplified view in proper accounting words.

## Scope and Acceptance

- [x] Store Assets, Liabilities, Government Equity, Current Allotments, Continuing Allotments and Continuing Obligations (M35), loaded for Tuguegarao City only (`cagayan.finances.summary_detail_towns`).
- [x] Approve the 72 new Tuguegarao figures automatically at the user's direction (`reviews.csv`), after the 201-cell image check.
- [x] COA's words everywhere: Income, Expenses, Net Income; "Government Income" on map legends. Net Income negative in parentheses, with no triangle or color.
- [x] Executive Summary is the default View on Economy and Analytics; Yearly Summary and Trends remain.
- [x] Table rows in COA's order, years as columns, sticky Item column, exact amounts up to three years and short amounts with the exact figure on hover beyond, one sorted Source line, Results of Operation and Financial Condition charts.
- [x] Other towns read Not available with "Loaded for Tuguegarao City only", never `0`.
- [x] Docs: metrics.md M19 and M35, design.md "Executive Summary View", CLAUDE.md, rules and skills, Help content and guide, finance README, roadmap.
- [ ] `composer ci:check` green.
- [ ] Capitol Analytics checked in the browser (signed-in); Sum of Reporting Towns checked in the browser.
- [ ] `/design-check` and `/privacy-check` before commit.
- [ ] Carried from Finance Trends: Trends and Yearly Summary checked at phone and desktop sizes in both themes with keyboard and touch.

## Deferred or Excluded

Other towns' new lines (their parsed rows wait in `data/towns/finances/extracted/`), Continuing Appropriations (only dashes for Tuguegarao), new metrics, packages and any change to the revenue-source, business-tax or development breakdowns.

## Decisions and Sources

- "26-10-29": user choices: COA words everywhere, parentheses only for Net Income, Executive Summary default with other views kept, Tuguegarao first, figures approved automatically, work on this branch.
- "26-10-29": Net Income stays computed as Income minus Expenses (M19). COA's printed Net Income appears in only 61 of 158 reports and disagrees in two (Amulung and Rizal 2025); for Tuguegarao it matches every year.
- "26-10-29": at desktop width only about three and a half exact-amount years fit, so longer periods use the approved Trends short form with exact amounts on hover.
- Sources: [COA Parser Hardening](../archive/26-10-29-coa-parser-hardening.md) (201-cell check), metrics.md M19 and M35, design.md "Executive Summary View".

## Change History

| Date | Change | Reason / Reference |
| :- | :- | :- |
| 26-10-29 | Plan created; supersedes Finance Trends as the default view | User request in this session |
| 26-10-29 | `ResultFigure` removed; Net Income renders like any amount | Without triangle or color it would be a one-line wrapper |

## Verification and Handoff

- Data: re-extraction added only the six new lines (plus the four relabeled Tuguegarao appropriation rows from the parser hardening). `finances:import` reported 72 new figures and 720 verified. A read-only query matched every Tuguegarao value and year-over-year change to the 26-10-29 table.
- Tests: `tests/Feature/Public/EconomicPerformanceTest.php` and `tests/Feature/Metrics/FinanceSeriesTest.php` 32 passed; `tests/Feature/Finances` and `FinanceMapTest.php` 27 passed; `composer test:analytics`, `test:trend` and `test:maps` passed; parser 77 passed. `npm run check` and `npm run types:check` passed.
- Browser (public Economy, existing dev server on port 8000): all 77 Tuguegarao cells matched; Abulug rows read "Loaded for Tuguegarao City only"; Gattaran 2024 Net Income reads (₱6.54M) with no triangle; 3Y, Back and Forward made zero network requests; no page overflow at 375 px, with a sticky Item column; light and dark rendered; no console errors. Screenshots timed out twice while the window was in the background; later ones succeeded.
