# Roadmap

The plan from today to the submission, and the status of every feature. Update the status lines in the same pull request that finishes the work.

Finance filters and business activity redesign completed, 26-11-09. Shared dropdowns, separate Recent Uploads, grouped COA citations and a single business activity picker. [Scope and verification](../plan/archive/26-11-09-finance-filters-business-activity.md).

Maintainability refresh completed, 26-11-09. Home focus and toolbar search, page folders, dependency and code cleanup, project skills, and one frontend test command. Final CI and browser checks passed. [Scope, follow-ups and verification](../plan/archive/26-11-09-maintainability-refresh.md).

Executive Summary view, 26-10-29: Economy and Analytics open on COA's Executive Summary lines (Financial Condition, Results of Operation, Budget Execution) as rows with fiscal years as columns, plus two charts. COA's words Income, Expenses and Net Income replace Government Revenue, Government Expenses and Surplus or Deficit, and a loss reads in parentheses. Assets, Liabilities, Government Equity, Allotments and Continuing Obligations load for Tuguegarao City only (M35). See [the plan](../plan/active/26-10-29-executive-summary-view.md).

Local preview integration, 26-10-28: quick sign in, wish-cloud polishing, and the finance-review screens are available together on port 8000 using the existing finance preview database. Combined CI passes (201 tests). See [Client Facing Polishing](../plan/archive/26-10-27-client-facing-polishing.md).

Government Finances correction, 26-10-28: one aligned report table, right-hand fiscal-year selector, compact linked citations, on-demand review controls, centralized breadcrumbs/required markers, and quiet shared pagination. Web and manual CSV input checks are strict and share configured limits. CI passes 224 tests (1,343 assertions); desktop light/dark flows are checked. See [Finance Ledger and Analytics Program](../plan/archive/26-10-27-finance-ledger-analytics-program.md).

Status words: `Not started`, `In progress`, `Done`.

## Dates that do not move

- **Friday, October 30, 2026:** submission by email. Abstract of at most 250 words, list of team members, adviser's name, and a prototype video of 3 to 5 minutes.
- **By November 30, 2026:** qualified teams are notified.
- **December 10, 2026:** booth and final presentation. Five minutes to present, ten minutes of questions, and a deck of 5 to 10 slides.

The prototype must be at least 50 percent functional in the video and at the booth. The MVP has 19 features, so the floor is 10 working features. The plan below reaches 12 before the video and aims for more.

The team's own target is to submit on **Thursday, October 29**, one day early.

## What the video must show

From the challenge guidelines: problem statement, proposed solution, target users, the working prototype, existing or competing solutions, team composition, and traction if any. The prototype part follows the ten-step demo walkthrough in the product document.

Other submission items to prepare alongside the build, owned by the data and content member: the abstract, the dean's recommendation letter, and the adviser's name. The email subject format is in `docs/product/challenge-guidelines.md`.

## Schedule

### Kickoff: October 3 to 4

- Repository set up (done October 3)
- All three laptops pass `composer doctor`
- Branch protection on, first pull request merged by each member
- The boundary files in `data/boundaries/` are checked against PSA's current listing for Tuguegarao City

### Week 1: October 5 to 11. Foundation

Goal: real boundaries on a map, simulated businesses in the database, one true number on screen.

- F1 Core schema: towns, barangays, business types, businesses, business years
- F2 Boundaries loaded from `data/boundaries/`, plus urban or rural class and population from PSA, for 49 barangays and all towns
- F3 Seeder with the fixed seed and the required demo cases
- F4 `metrics:refresh` filling `stats_area_year`, with M1 to M4
- F5 Privacy guard with its tests
- F6 Shared map component drawing barangays from PostGIS
- F7 Layouts for both sides, the simulated label, administrator login, public registration off

### Week 2: October 12 to 18. The core of both sides

Goal: the first five demo steps work.

- C1, C2, C3, C4 on the Capitol side
- P1, P2 on the public side
- M5, M9

### Week 3: October 19 to 25. Depth

Goal: every tier 1 feature done, tier 2 started in priority order.

- C7, C8, C12, C13
- P3, P4 with the first verified path
- M10, M17
- Then tier 2, starting with C9

### Week 4: October 26 to 30. Freeze and submit

- **Monday, October 26:** feature freeze. Only `bug/` branches merge into `staging` after this.
- Release `staging` to `main` and tag `v1.0.0` before recording.
- `/demo-check` passes on a clean clone on two laptops
- **Tuesday and Wednesday, October 27 to 28:** record and edit the video
- **Thursday, October 29:** submit
- **Friday, October 30:** spare day

### After the submission: November

- Finish tier 2
- The other two checklist paths
- Deploy the online version
- The slide deck and the answers to likely questions
- Follow up on the items under "To verify before the pitch" in the product document

## If the plan slips

Cut from the top of this list down. Never cut the privacy rules, the simulated label, or tests for metrics.

1. P5 Local snapshot
2. C6 Compare
3. C11 Peer comparison
4. C14 PDF brief, keeping CSV
5. C5 Dominance
6. C10 Early warning
7. C9 Outlook

## Feature status

### Foundation

- F1 Core schema: In progress (towns and business types done)
- F2 Boundaries and population: In progress (29 town and 820 browser barangay populations verified against the PSA 2024 workbook; database barangay populations cover Tuguegarao's 49 barangays. Reference figures are imported from `data/towns/`, but economic rows still need source verification and replacement. COA figures describe government finances, not budgets or private-business revenue. Shapes in PostGIS and urban or rural class remain)
- F3 Seeder: In progress (simulated Tuguegarao businesses 2020 to 2026 with the fixed seed, requirements 3 to 6 and 9 tested; early-warning and peer cases 7 and 8 wait on C10 and C11)
- F4 Metrics refresh: In progress (`metrics:refresh` runs `database/sql/metrics/`; `stats_area_year` is built. M1 to M4 screens remain)
- F5 Privacy guard: Not started
- F6 Map component: In progress (public SVG and protected MapLibre population maps work. The PostGIS-backed business map remains unbuilt)
- F7 Layouts, label, login: Done (public shell and compact Capitol workspace, three roles, discreet login, protected sessions, and private profile)

### Tier 1: in the video

- C1 Province at a glance: Not started
- C2 Drill down: Not started
- C3 Barangay profile: Not started
- C4 Rankings and movers: Not started
- C7 Status and growth trends: Not started
- C8 Survival rate: Not started
- C12 Citizen wishes: In progress (restricted aggregate town/category page implemented for Admin/Super Admin. Comparison with registered business supply remains unbuilt)
- C13 Business directory with view log: In progress (registry 26-10-29: directory tabs, full record with view log, Staff add and edit with pin and documents, Admin approve or return; export not built)
- P1 Explore map: Not started
- P2 Business listing: In progress (public list, storefront pin map and profile with BIR and permit badges, 26-10-29)
- P3 I wish there was this business here: In progress (form, cooldown, edits, category/company choices, 255-character reason, and count-only background updates done. The registered count waits on F3 and F4)
- P4 Start a business guide, first path: Not started

### Tier 2: as many as time allows, in this order

- C9 Outlook with backtest: Not started
- C10 Early warning: Not started
- C5 Dominance: Not started
- C14 Filters, export, and brief: Not started
- C11 Peer comparison: Not started
- C6 Compare: Not started
- P5 Local snapshot: Not started

### Metrics

- M1 Net growth: Not started
- M2 Business density: Not started
- M3 Total declared sales: Not started
- M4 Sales per business and share: Not started
- M5 Rank and rank change: Not started
- M6 Dominant type: Not started
- M7 Concentration: Not started
- M8 Specialization: Not started
- M9 Renewal rate: Not started
- M10 Survival rate: Not started
- M11 Outlook, businesses: Not started
- M12 Outlook, sales: Not started
- M13 Backtest error: Not started
- M14 Early warning: Not started
- M15 Public sales band: Not started
- M16 Peer group: Not started
- M17 Wish count: In progress (count done. The registered count beside it waits on F4)

## Handoff for the Next Session

October 23, 2026. The user's UI corrections are recorded in the design guide and summarized in the shared project instructions and `.claude/rules/frontend.md`. Read the design guide before proposing further public UI changes. [Working on Existing Screens](../engineering/design.md#working-on-existing-screens), [shared project instructions](../../AGENTS.md).

- Home has one shared icon style control, three measures in the main panel, a cached town/barangay explorer, Geist typography, and a muted blue slate dark theme. The shared SVG map supports both dotted and solid presentation.
- The population datasets cover Cagayan only: 29 towns and 820 barangays from the workbook's Cagayan sheet. Database barangay population currently covers Tuguegarao's 49 barangays. Browser geometry covers all 29 towns; loading PostGIS boundaries for the future business map is still F2/F6 work.
- The wish form shows Business Type first and reveals Specific Business on request. Town and map selection preserve drafts; only category counts load in the background. The registered-business count remains "Not available" until F3/F4 supplies records and metrics.
- COA revenue and expenditure rows without a `checked_on` date still require a team member's source review, as recorded in `data/towns/README.md`.
- The production and SSR builds pass with the existing Inertia sourcemap warning. Refresh a running development preview after rebuilding before checking count requests, because an old asset version can cause a discarded background response.
- Continue with a focused task and commit by concern. Do not restore removed helper copy, modals, map CTAs, legends, or duplicate controls as part of unrelated work.


## October 24 Workspace Foundation

Implemented the map-first three-role workspace with economic context, explicit barangay drill-down, private profile, restricted aggregate Community Wishes, and Super Admin Activity Logs. Existing demo business metrics and approval workflows remain unavailable, not zero. This foundation does not mark C1/C2/C3 business intelligence complete. Role management screens, Staff account management, taxonomy editing, and business requests/publication remain follow-up work. C12 has a working separate aggregate wishes page; comparison with registered supply still waits on business records/metrics. The last approved privacy rule is that Staff sees no Community Wishes and no wishes data appears on any dashboard.

## Future Phase: Historical Local Government Finances

Status: completed as the [Finance Ledger and Analytics Program](../plan/archive/26-10-27-finance-ledger-analytics-program.md), started 26-10-27. Completed 26-10-28. Phases 0 to 4 merged through PRs #22 to #25; Phases 3, 5 and 6 travel in one combined pull request. Every loaded COA figure is signed off after a reliability review; workbook breakdowns load for Tuguegarao City only. Collecting the remaining COA workbooks and loading other towns' breakdowns are follow-ups (see the archived record).

A Python parser reads the COA Executive Summaries and audit-report workbooks into a reviewed finance ledger. Admins can also enter and correct figures in the Capitol. A figure is published only after a person signs it off (D34, D35). Live upload progress and retry are being verified under [Live Finance Parsing](../plan/active/26-11-08-live-finance-parsing.md). The Analytics pages show these figures over fiscal years 2019 to 2025:

- Government Revenue, Government Expenses, and Surplus or Deficit
- budget utilization
- revenue sources and local revenue detail
- business taxes and fees
- development spending (the 20% Development Fund and Economic Services)

Definitions are M19 to M27 in metrics.md, approved 26-10-28. The view stays separate from business revenue, resident income, measured demand, and investment recommendations.

- Do not assume Income, Revenue, Expense, Expenditure, Appropriations, Obligations, and cash flows are interchangeable. Each line names its source and basis.
- Show change only between consecutive comparable years (M26). Missing years stay unavailable, and a zero prior value has no percentage change. Restatements are kept. An increase is not automatically an improvement.
- Fiscal year 2026 waits for its released report. Never substitute a budget or partial-year figure for audited annual actuals.
- A sum of municipal accounts is labeled Sum of Reporting Towns (M27). It is never labeled as the provincial government's finances.
- Audit opinions, audit findings, and infrastructure project lists are excluded from extraction and display (user, 26-10-27).

## October 24 Handoff: Direction Recorded, Implementation Deferred

The user approved Revenue + Economic Status as an information direction, without pivoting to auditing. Retain source-verified government revenue with an explicit government label alongside separate economic, household, business, and community-interest indicators. Preserve each source's geographic coverage and reference year. The full definition is in docs/product/mvp-scope.md; it does not authorize inventing a composite economic-status score.

Further document collection will take place in another session. That earlier turn was documentation only. The user subsequently authorized finishing the dashboard foundation and its outstanding UI/data reviews, recorded in docs/plan.

Outstanding work, checked against the current routes, models, migrations, and existing workspace:

- Foundation UI-copy and data-accuracy review is complete: redundant page descriptions removed, four 2024 COA fiscal pairs verified with exact source references and cents, unchecked figures withheld, and explicit Government Revenue/Government Expenses labels applied. Remaining economic indicators and COA 2021–2026 histories are deferred.
- Build account-management screens, Super Admin role changes, and Staff assignment management. Editable Business Categories and Business Types are built (D39), with a read-only Businesses directory. Current authorization and assignment policies are foundations, not management UIs.
- Complete the business/business-year schema, records, entry/editing forms, strict field contracts, and assignment-scoped operations. No working business-record management exists yet.
- Build record publication: Staff prepares/submits; Admin/Super Admin reviews and publishes, with 1 Prepare, 2 Review, 3 Publish. This is publication, not permit processing.
- Finish PostGIS boundary loading and remaining geographic attributes; implement the business map, business directory, full-record view logging, and public business listings.
- Implement business metrics, the sales privacy guard, rankings, trends, survival, and wishes-versus-registered-supply comparisons. Existing population shading and wish counts do not constitute these features.
- Implement the verified start-a-business guide and later comparison, outlook/backtesting, early-warning, export/brief, and local-snapshot features. Deployment, clean-clone demo verification, and submission materials still need their own completion checks.

Implemented foundations are the public landing/population explorer and wishes submission flow, plus the protected workspace's map/geographic navigation, three-role access and session controls, private profile, restricted aggregate Community Wishes, and Super Admin security/account Activity Logs. Business transaction history remains unavailable until business workflows exist. Do not describe those foundations as completion of business intelligence or investment analysis.

## Foundation Completion Record

The accepted dashboard foundation, later UI corrections, source verification limits, and final checks are recorded in [the plan register](../plan/README.md). Business entry/publication, account/category-management screens and historical economic/financial series remain future phases. Runtime patch updates are isolated on `chore/security-runtime-updates`; foundation changes use `feat/capitol-workspace-foundation`, both based on staging. Public landing PR #15 is already merged; no duplicate landing PR was created.

## October 25 Public Economic Performance

Completed 26-10-25: dedicated public Tuguegarao-only synthetic Government Finances and Community Wishes page, 2021 through 2025 finance history, curated type/specific-business cloud with bars/table alternatives, and stable Laravel Data contracts. Other towns show unavailable states. See the [completed plan](../plan/archive/26-10-25-cagayan-economic-performance.md) and data/economic-performance/README.md. Final-only verification passed: 150 tests, 914 assertions, CI static checks, production/SSR builds and desktop/phone browser interactions. Partial clouds keep complete counts in bars/table; import and live review workflows remain deferred.

Explicitly deferred: Population, registered-business counts/trends and leading types, real COA history collection/import, live wish normalization/public-label review, reason themes, spatial analytics and Python predictive analysis. No placeholder charts or forecasts. The existing deferred finance-source plan remains open; this demo does not mark the broader business intelligence metrics complete.

## October 25 Economy Polish

Completed 26-10-25: compact Cagayan Economy overview with stacked finance/wishes sections, independent icon view controls using local URL history, stronger chart grid and a shared town-picker map. The obsolete topic/shared-view branches and unused navigation override were removed. See the [polish record](../plan/archive/26-10-25-cagayan-economy-polish.md). Final-only verification passed: 150 tests, 931 assertions, CI/static checks, production/SSR builds and desktop/phone interactions. No additional data collection, population/business metrics or predictive work is included.


### Economy Local Selections Completed

26-10-25: Removed repeated server visits for period, wish mode, view and town/map selection. Load the bounded public catalog once; all selections use local Inertia history. Durable no-refetch guidance is recorded in CLAUDE, engineering docs and UI skills. Access-log browser checks confirmed zero backend requests for loaded-data interactions. Final CI: 151 tests, 948 assertions; production/SSR builds passed. [Completed fix](../plan/archive/26-10-25-local-economy-selections.md). Deferred finance-source collection remains separate.


### Home Economy and Wishes Completed

26-10-25: Home now offers live town-aware wishes and Economy actions. Public wishes use a single combined curated ranking with bounded full-label bars, cloud/fallback counts and actual town tooltips. Removed public Economy tables, mode filtering, repeated disclaimers/captions and Home placeholder/protection sections. Skills and docs record the no-dangling-copy correction. CI passed with 151 tests and 951 assertions; production/SSR builds and phone/desktop/local-history checks passed. [Completed record](../plan/archive/26-10-25-home-economy-wishes.md).


### Compact Home Session Handoff

26-10-25: Final Home correction removes action-card icons/shadows, reduces padding to 16px and gaps to 12px, and uses Explore Your Town's Economy. The complete Economy session is being prepared in focused review batches; no merge or deployment is authorized. [Handoff plan](../plan/archive/26-10-25-compact-home-session-handoff.md).

Published review batches: foundation PR #16 targets staging; Economy PR #17 depends on #16 and contains this session in focused commits. Required hooks passed; remote CI and human review are pending. No merge/deployment performed.

26-10-28: The finance follow-up moves entry into the filter panel, reorders and explains required inputs, removes entry notes and shared greetings, and compacts grouped sidebar navigation with a bottom account menu. Activity Logs and Community Wishes reuse the right-hand filter panel; Profile uses the shared required marker. Session security and permissions remain active. Latest follow-up browser testing is explicitly waived by the user; automated results are recorded in the active finance plan.


## October 28 Analytics Clarity

Implemented on `feat/analytics-clarity`, with the layout, sources and Help redesigned the same day (see October 28 Ledger Table below): annual government summaries on public Economy and Capitol Analytics, optional trends/comparisons, relative periods that also filter Business Trends, General Fund labels, source lineage and shared Help/learning material. All 263 PHP tests and six frontend selection/formatting tests pass, along with format/lint, type checks and the production build. Browser layouts and navigation were checked; zero application requests for view, year and Back were measured after the redesign. Scope and verification: [Make Analytics Understandable](../plan/archive/26-10-28-analytics-clarity.md). This does not add new COA collection, alter metric definitions or remove private-business sales protections.

## October 28 Dynamic Economic Map

Completed on `feat/analytics-clarity`: public Home and Capitol maps share annual finance results, one absolute Latest fiscal year, revenue/expense/surplus shading, source evidence and reporting coverage. Imports and review batches rebuild finance results transactionally. Existing Analytics changes and census explorer are preserved. Verification: [Dynamic Economic Map](../plan/archive/26-10-28-dynamic-economic-map.md).

Dynamic Economic Map verification: final CI passed 270 PHP tests (1,827 assertions), nine frontend tests, formatting/lint, TypeScript and PHPStan; production build passed. Browser checks covered public/Capitol at 375px and 1440px, light/dark, public styles, exact-year gaps, source evidence, local history and barangay drill-down. No horizontal overflow or final browser console errors.

## October 28 No Simulated Data and Ledger Table

On `feat/analytics-clarity`: simulated business records are off by default behind `CAGAYAN_SIMULATED` (D37), so Economy and Capitol Analytics show only real COA figures and labeled sample wishes. The Yearly Summary becomes one ledger table with one Source line linking the stored PDF at the cited page (new public route), a short Help sheet, and Tuguegarao City only measures marked in the Show list. Plan: [Finance Ledger Table Redesign](../plan/archive/26-10-28-finance-ledger-redesign.md). Completed the same day with polish: the Home About the Data map loads again, the Yearly Summary reads the year before then the selected year, Surplus or Deficit is one shared green up and red down display, abbreviated amounts and Change cells show the exact amount on hover, Trends shows the change under each year, and the M26 percent divides by the size of the prior value.

## October 30 Home Navigation and Data Labels

Text-only desktop navigation with a filled active tab, no navbar simulated label, and red badges at the upper right of the wish cards. Public business labels remain with the content. Frontend checks and build pass; full CI stops in Pint at an unreadable Python cache directory. Verification: [Home Navigation and Data Labels](../plan/archive/26-10-30-home-navigation-data-labels.md).

26-10-30 achievement follow-up: replaced rejected icon panels with the initial illustrated trophy and medal assets (superseded by Kenney Medals at the user's direction) in the shared profile Achievements card. Local assets and license recorded; frontend checks/build passed and public desktop dark/phone light reviewed. See the active Business Registry Records plan for verification limits.

26-10-30 latest achievement correction supersedes the external-asset follow-up: restored the original compact hexagonal medals with small outline and number-spacing refinements. Both external badge packs are removed from the project. Existing profile placement, directory marks and recognition rules remain.
