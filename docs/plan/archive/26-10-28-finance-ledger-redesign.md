---
title: "Finance Ledger Table Redesign and No Simulated Data"
created: "26-10-28"
updated: "26-10-28"
status: completed
branch: "feat/analytics-clarity"
base: staging
predecessor: "26-10-28-analytics-clarity.md"
---

# Finance Ledger Table Redesign and No Simulated Data

## Objective

The user rejected the Analytics Clarity result on "26-10-28": simulated data cannot be what the app shows, sources were repeated everywhere (rows, footer, a Source Details sheet, tooltips), the Help was a text wall placed beside the title, and the Yearly Summary was "horrendous". They asked for a complete redesign and relayout, one source of truth for sources, a link to the PDF, and clear Tuguegarao City coverage. Answers given in the session: do not seed simulated data but keep the code for presentations; serve our local copy of the PDF at the cited page; keep the demo wishes with realistic inputs; one clear table layout.

## Scope and Acceptance

- [x] Simulated business records are off by default behind `CAGAYAN_SIMULATED`: the seeder is skipped, Business Trends and the label disappear, old simulated rows are never shown while the flag is off.
- [x] Demo wishes kept, rewritten with realistic counts and business types that match `BusinessTypeSeeder`, shared by `WishSeeder` so Economy and Suggestions agree, and labeled "Sample Data".
- [x] One evidence entry per figure in `catalog.sources`; no source in rows, footers, tooltips or charts; no Source Details sheet, Year Details control or Compare control.
- [x] New public route serves the stored COA PDF inline for verified figures only; the citation link opens it at the cited page; workbooks download; unknown, pending, entered and out-of-folder files answer 404; throttled.
- [x] Yearly Summary is one ledger table (item, selected year, year before, change) with one Source line of distinct citations; Sum of Reporting Towns shows the plain line; Trends shows the chart and the same table with year columns.
- [x] Help moved into the Government Finances header, short, with native disclosure groups; guide content cut and mirrored in `docs/product/analytics-guide.md`.
- [x] Measures a town lacks are disabled in Show with "Tuguegarao City Only" or "Not Loaded for This Town"; Home About the Data states the Tuguegarao City coverage.
- [x] Docs rewritten in one voice (design.md, frontend.md, decisions D37, privacy P13, CLAUDE.md, rules, mvp-scope, roadmap, data READMEs).
- [x] Zero application requests for view, year and Back/Forward measured in the browser (closes the open item of the predecessor plan).
- [x] `composer ci:check`, `npm run check`, `npm run types:check`, `npm run build` all green.
- [x] Design and privacy self-review against the diff (grep checks and P1 to P14 reading); the `/design-check` and `/privacy-check` skills were not run as separate passes.
- [x] Capitol Analytics checked visually as the local demo Admin: the same Yearly Summary and Trends, with no overflow at 375px.
- [x] Polish, same day: About the Data loads (the compiler memoized a module-cache read), the Yearly Summary reads the year before then the selected year, Surplus or Deficit is one shared green up and red down display, abbreviated amounts and Change cells show the exact amount on hover, focus or tap, Trends shows the change under each year, and M26's percent divides by the size of the prior value.

## Deferred or Excluded

- Dropping the business tables, `PrivacyGuard` or the simulated seeder (kept dormant for presentations and the real registry).
- A "view on COA" secondary link and any COA site dependency.
- Sample wishes drawn from the database instead of `demo.php`.
- Changes to metric definitions M19 to M31, COA parsing, sign-offs or migrations. No packages added.
- A Capitol redesign beyond sharing the same panels and the Help placement.

## Decisions and Sources

- "26-10-28": `CAGAYAN_SIMULATED` defaults to false and gates seeding, the business query and the label together; the query check keeps P13 true even with stale rows. Recorded as D37 in `docs/engineering/decisions.md`.
- "26-10-28": link our stored copy of the report. COA's site answered 403 with a Cloudflare challenge to a plain request, so an inline PDF at a page cannot be relied on. The 158 Executive Summary PDFs are already tracked in `data/towns/`.
- "26-10-28": a single Source line under the table (one link per distinct citation) instead of a Source column, following the user's earlier map-panel rule "never print a run of identical citations" (design.md, "Economic Map Cleanup"). The previewed Source column would have repeated "Executive Summary p. 2" on every row. Reasons for missing figures print once under the table.
- "26-10-28": the Compare control is removed because the previous year and change are always shown.
- Canonical docs: `docs/engineering/design.md` ("Government Finances Ledger Table"), `docs/engineering/frontend.md`, `docs/engineering/privacy-rules.md` (P13).

## Change History

| Date | Change | Reason / Reference |
| :- | :- | :- |
| "26-10-28" | Simulated flag default false; seeder, business query and label gated | User: no simulated data by default; D37 |
| "26-10-28" | `catalog.sources` reshaped (`kind`, `label`, `page`, `url` to the report route); `FinancialHistory.source` removed; `AnalyticsMeasure` with `towns`; `compare` removed | One source of truth; Tuguegarao City clarity |
| "26-10-28" | `FinanceSourceController`, `public.finance-sources.show`, `finance-sources` throttle | Link the PDF at the cited page |
| "26-10-28" | `finance-panel.tsx` rewritten as a ledger table; `source-line.tsx` added; `observation-details.tsx` deleted; chart tooltip reduced to values | Redesign |
| "26-10-28" | `analytics-help.tsx` rewritten; guide JSON and mirror cut to about 550 words | Help was a text wall |
| "26-10-28" | Sample wishes rewritten; `WishSeeder` reads `demo.php`; "Sample Data" label | Realistic inputs, one source |
| "26-10-28" | Polish: `barangay-atlas.tsx` keeps the loaded census in state; `lib/trend.ts`, `trend-mark.tsx` (`TrendMark`, `ResultFigure`) and `exact-amount.tsx` added; `finance-panel.tsx` summary columns run year before then selected year; Trends cells carry the change; map panel and chart tooltip use the shared marks; percent in `20_stats_finance_year.sql` over the prior magnitude, M26 amended | User corrections: Home About the Data never loaded, estimated amounts, inconsistent surplus and deficit, reversed year order |
| "26-10-28" | Plans consolidated: this plan completed and archived; [Make Analytics Understandable](26-10-28-analytics-clarity.md) archived as superseded | One record per screen |
| "26-10-28" | Browser pass: Economy at 375px and desktop, Help sheet, Trends, year from the table, Back, other town picker | Verification |

## Verification and Handoff

Recorded as run on "26-10-28":

- `php artisan test --compact` on `tests/Feature/Public`, `Seeder`, `Privacy/BusinessTrendsPrivacyTest.php`, `Metrics`, `SimulatedLabelTest.php`: passed after the test updates (the earlier failures were the intended shape changes). New: `FinanceSourceTest` (inline PDF, workbook download, pending, entered, missing and out-of-folder files, throttle), measure coverage, simulated-off privacy case, seeder default.
- `composer test:analytics`: 7 passing tests, including citation dedupe and measure fallback.
- `composer ci:check` on a clean database: Pint passed, PHPStan 0 errors, Pest 295 passed (1,949 assertions), Node tests passed. An earlier overlapping run failed only because my own parallel test command shared the test database.
- `npm run check`, `npm run types:check` and `npm run build` pass. An earlier build failed in `business-categories.tsx` while a concurrent session renamed a Capitol route; it settled and the rerun passed.
- Browser (dev server, flag off): table, single Source link, Help sheet, Trends, a year chosen from the Trends header, Back, the Show list for Abulug and a 375px width with no horizontal overflow (table 317px in 317px). Network recorder: the three document loads only; choosing Trends, choosing 2024 and Back added no application request. The PDF route answered `application/pdf` for a real signed-off figure and 404 for an unknown id.
- Limitations: Capitol Analytics was not viewed in the browser (shared panels, covered by tests); light mode not rechecked after the final table padding change; the PDF opening at the exact page depends on the browser's PDF viewer honoring `#page=N`.
- The local `.env` was changed to `CAGAYAN_SIMULATED=false`; existing local simulated rows are hidden but remain until `composer db:reset`.

Polish verification, recorded "26-10-28":

- Full Pest suite 306 tests (2,103 assertions) passed, including a new M26 case (a deeper deficit -100.0, a smaller one +75.0, -5 to +10 is +300.0). `composer test:trend` (3), `test:categories` (8), `test:analytics` (7) and `test:maps` (3) pass. PHPStan zero errors, Pint, `npm run check`, `types:check` and `build` pass.
- Browser: About the Data loads on a cold visit (2,363 dots) and after changing to Abulug. Economy and Capitol Analytics for Alcala 2025 read 2024, 2025, Change, with a green up mark on the 2024 surplus and red down marks on the 2025 deficit and the -247.1% change. Hovering the change shows "-₱58,651,101.96 from 2024". Trends reads 2023, 2024, 2025 with a change under each year. The Home map panel shows the same red mark. No horizontal overflow at 375px.
- Limitations: the tap behavior of `ExactAmount` is covered by the same open-on-click code as `FieldHelp` and was not exercised on a real touch device; light mode was not rechecked. M26's percent definition changed on the user's correction, so `metrics:refresh` must be run on other machines.
