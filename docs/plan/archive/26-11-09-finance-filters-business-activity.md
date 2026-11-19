---
title: "Finance Filters and Business Activity"
created: "26-11-09"
updated: "26-11-09"
status: completed
branch: chore/maintainability-refresh
base: f55a81b3f95b4373fdfd691df7207acf60a2feae
predecessor: 26-11-09-maintainability-refresh.md
---

# Finance Filters and Business Activity

## Objective

Help readers choose a year or business group quickly, find the finance evidence on demand and distinguish uploaded report status from report filters. Continues the [Government Finances Report Redesign](../archive/26-11-03-finance-report-redesign.md).

## Scope and Acceptance

- [x] Apply shared Radix Select styles across filter and form contexts, including grouped options and bounded menus.
- [x] Keep the fiscal-year selector separate from recent upload details. Preserve the parser view, background polling and retry.
- [x] Group loaded COA citations in a collapsed inline disclosure, keeping direct page and workbook links and plain province attribution.
- [x] Rename Business Activity Over Time and use one group picker with the existing industry and category data. Preserve the URL, counts and simulated-data contract.
- [x] Run the final CI, build and browser verification. Record outcomes and known gaps.

## Decisions and Sources

"26-11-09": The owner selected one picker with sections, a compact expandable citation list, and a separate upload disclosure. Keep the existing SQL, privacy rules, routes and report access. The parent plan's completed Home work stays in its archive.

## Change History

| Date | Change | Reason |
| :- | :- | :- |
| "26-11-09" | Shared selector, disclosure and local analytics selection | Approved redesign after review of the finance and analytics screenshots |

## Verification and Handoff

"26-11-09": `composer ci:check` passed with 30 frontend tests and 377 PostGIS tests (2,724 assertions), plus frontend lint, TypeScript, Pint and PHPStan. `npm run build` passed with the existing large map chunk warning. Regression tests cover the paired classification selection, grouped report and page ordering, duplicate citations, workbook links and unlinked evidence.

Browser verification used the production build at 375 and 1280 px. Checked quiet filters and bordered form controls in light and dark appearances. The grouped selector showed both sections, kept 44 px rows on phones, scrolled inside the viewport and supported keyboard selection and focus return. Back/Forward restored the business group and the fiscal year. Public Economy and Capitol Analytics opened COA Sources inline, and a sampled cited page returned HTTP 200 as a PDF. Server logs showed zero application requests for the tested loaded selections and citation disclosure.

The Government Finances report kept Fiscal Year above the table on phones and to its right on desktop, with Recent Uploads collapsed separately. A saved failed upload reopened to Parsing Stopped and showed Retry Parsing. Browser verification did not submit a new upload or retry. The privacy review found no new data exposure. Both analytics views use the existing simulated-data gate, and citations retain the authorized source URLs and exclude unlinked evidence.
