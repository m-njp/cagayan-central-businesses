# Cagayan Business

A map-based dashboard of registered businesses for the Provincial Government of Cagayan, built for the PGC Digital Innovation Challenge. Prototype video due October 30, 2026.
Two audiences: a role-protected Capitol workspace (Super Admin, Admin, and Staff) and the public (no login, sales as bands only, a start-a-business guide, citizen wishes).
MVP: Tuguegarao City's 49 barangays. Real boundaries, population and COA finances. No simulated business records by default (decisions.md, D37); a simulated set is opt-in for presentations and labeled "Simulated data".

## Stack

Laravel 13 (PHP 8.3), Inertia 3, React 19 with TypeScript, Vite 8 (through vite-plus, `vp`), Tailwind CSS 4, shadcn/ui on Radix, Fortify for login, Wayfinder for typed routes, PostgreSQL 17 with PostGIS 3.5 in Docker, Pest 4. There is no separate API: controllers render React pages through Inertia.

## Start every session like this

0. Work only in this folder, `cagayan-business-kickoff`. Never create a git worktree, a second clone, or a copy of the repository, and never use worktree isolation for agents (user rule, 26-10-28; `docs/team/workflow.md`). Switch branches here instead.
1. Run `git branch --show-current`. If it says `main` or `staging`, create a work branch inside `staging` before changing anything: `git switch staging`, `git pull --ff-only`, `git switch -c feat/short-description` (or `bug/`, `data/`, `docs/`, `chore/`). `main` is production, `staging` is development, and work branches merge back into `staging` only. Rules: `docs/team/workflow.md`.
2. Read the doc for the area you will touch (table below). The team has a frontend developer, a backend developer, and a data and content member (`docs/team/workflow.md`, "Roles"). The Data object is the contract between frontend and backend.
3. Read `docs/plan/README.md` and resume the matching active plan. Use `/plan-history` for agreed work: record scope, dated changes and verification using `yy-mm-dd`; archive completed plans and reference them in later improvements. Then build the smallest agreed slice that works end to end.

## Commands

All work in PowerShell. Add new tasks as Composer scripts, never as bash or PowerShell scripts.

- PowerShell does not expand wildcard path arguments for `rg`. Search an existing directory and use `-g` to filter filenames instead of passing paths such as `app/Queries/*Finance*`.

- `composer setup`: first-time setup on a fresh clone. Safe to rerun.
- `composer dev`: run the app at http://localhost:8000 (server, queue, Vite). One dev server per machine: it refuses to start when port 8000 already answers (`php artisan dev:guard`) and clears a stale `public/hot`. Reuse the running server; never start a second one from another chat or terminal.
- `composer ci:check`: frontend formatting and lint, TypeScript, frontend file tests, Pint, PHPStan, and Pest.
- `composer test:frontend`: run all existing Node frontend suites without starting PostGIS.
- For every task that changes `resources/js/`, `resources/css/`, or Vite configuration, run `npm run check`, `npm run types:check`, and `npm run build` before reporting the work done. `npm run check` formats and lints but does not type-check; the TypeScript command catches unresolved names and imports. The build catches bundling errors. If formatting needs fixes, run `npm run check:fix`, then rerun all three checks.
- `php artisan test --compact tests/Feature/PostgisTest.php`: one file. Add `--filter="name"` for one test.
- `composer lint`: fix PHP formatting. `npm run check:fix`: fix front end formatting and lint.
- `composer db:up`: start the database. `composer db:reset`: rebuild the demo dataset.
- `composer data:towns`: reload town figures (`data/towns/`) and Tuguegarao barangay population (`data/barangays/`).
- `composer data:finances`: load COA finance figures and sign-offs into the ledger (`data/towns/finances/`). `php artisan finances:export` saves sign-offs and manual figures back to those CSVs.
- `composer data:finances:extract` and `composer data:finances:test`: rerun or test the local Python COA parser (`tools/coa/`, D34).
- `php artisan metrics:refresh`: rebuild every `stats_` summary table.
- `composer doctor`: check this machine and the database, with a fix for each failure.

Git hooks run checks on their own: fast checks on `git commit`, the full `ci:check` and a build on `git push`. Never bypass them with `--no-verify`.

## How a request flows

```
route -> Form Request -> controller -> Query (read) or Action (write) -> Data object -> Inertia page -> components
```

- **Routes:** named `public.*` or `capitol.*`. Capitol routes live in one group with `auth` and `can:capitol`.
- **Controllers:** thin. Call one Query or Action, render one page or redirect. No business rules, loops, or `DB::` calls.
- **Queries** (`app/Queries/`) read `stats_` tables and return Data objects. **Actions** (`app/Actions/`) do one write each. **Services** (`app/Services/`) hold rules shared by several of them, such as the privacy guard.
- **Data objects** (`app/Data/`): plain readonly classes with exactly the fields a page needs. Pages never get models.
- **Pages** (`resources/js/pages/`) receive typed props and arrange the screen. Each page has an `index.tsx` and keeps its private sections and hooks beside it. Shared UI goes in `resources/js/components/`.

Details and examples: `docs/engineering/architecture.md` (back end) and `docs/engineering/frontend.md` (front end).

## How to write code here

- **Simple first.** Write the shortest clear code that solves today's task. No layer, interface, abstraction, or option "for later".
- **Performance by default.** Eager load with `with()` (lazy loading throws outside production), paginate lists, stream exports with `lazyById()`, aggregate in SQL, send only the props a page renders.
- **Reuse before writing.** Search `app/Queries`, `app/Services`, `app/Data`, model scopes, `resources/js/components`, and `components/ui` first. Extract shared code on the third copy, not the first.
- **Split at useful boundaries.** Keep short, page-specific markup inline. Extract a self-contained section or workflow when it makes the page easier to read. Skip pass-through wrappers and barrels.
- **Trace before deleting.** Check actual consumers, including Laravel conventions, registrations, dynamic imports, build entries, and published asset paths. A text search can locate candidates.
- **Comments only when the reason is not obvious from the code.** One short line, saying why, not what. No commented-out code. PHPDoc only where PHPStan needs types (array shapes, generics).
- **Python in `tools/` uses camelCase** for variables, parameters, fields and functions (user rule, 26-10-29). Constants stay UPPER_CASE; CSV column names stay snake_case.
- **Follow sibling files** for naming and structure. Use `php artisan make:*` with `--no-interaction` to create files.
- **Login remains Fortify for the prototype. The approved workspace profile is separate from the removed starter settings.** The rest of the starter kit was removed (decisions.md, O8). Do not add public registration or new identity providers without a decision. Future Microsoft/Google sign-in must identify existing local users without granting roles.
- **No new packages** without asking. A new package needs a line in `docs/engineering/decisions.md`.

## UI rules

- Start UI work with `/ui-builder`. The design system is `docs/engineering/design.md`: tokens, Geist for words and Geist Mono for numbers, 2px corners, halftone dots for map data.
- In the Capitol workspace, use Municipality in place of Town or "Town or City". Public pages keep their existing wording. Code and routes keep `town`. Every filter tab row is `components/capitol/tab-row.tsx`. Full-page blur views use `components/ui/focus-dialog.tsx` (`design.md`, "Focus Overlay").
- Title Case for titles, headings, buttons, tabs, and badges. Sentence case for sentences. Proper nouns right.
- No em dashes, en dashes, or middots anywhere in the interface.
- Titles and headings are one name with no separators: no commas, dashes, middots, or pipes.
- No gradients. Colors from tokens only (`bg-primary`, `bg-map-3`, `text-status-closed`), never raw hex or palette classes.
- Dark mode uses muted blue slate surfaces that complement the map's river ink blue, with a lighter base than the original near-black theme. Keep text and control borders readable.
- Every data view handles loading, empty, not available, hidden, and error. "Not available" and "hidden" are never shown as `0`.
- Filters and tabs live in the URL. URL changes do not require server visits: sorting, views, loaded-data filters and cached map selections MUST use local Inertia history. Fetch only missing/stale authorized datasets, deduplicate and cache them by data identity, never by presentation state. Verify zero application requests for loaded-data interactions, including repeated selections and Back/Forward. See frontend.md, "Local Selection Contract". After a write, redirect and show a toast. Links use Wayfinder imports, never hard-coded URLs.
- Public Home and Capitol economic maps share Income, Expenses and Net Income for one absolute fiscal year. Latest alone retains population context; explicit fiscal years omit it. Year uses shared finance statistics and local history; the maps have no Measure selector and shade by Income (legend "Government Income"). Net Income is Income minus Expenses; a loss reads in accounting parentheses with no arrow or color (26-10-29). Up and down arrows from `TrendMark` and `lib/trend.ts` mark changes only (arrows replaced triangles 26-11-03). The separate public census explorer stays population-only. Do not add other measures or sources without an explicit request.
- Home maps share one icon-only style control beside the main town selector. Do not repeat it in About the Data. Reuse cached census data and geometry when revisiting towns; style changes must not reload data or clear selection.
- Wish form selections update locally. Fetch only the optional tally in the background, never the entire form payload. Business Type is visible by default; reveal Specific Business only through its control or when editing an existing company wish.
- Preserve existing layouts when adding information. Do not add instructions, legends, containers, or explanatory copy unless requested or necessary for the requested interaction.
- Finance review follows `design.md`, "Government Finances Simplification": one aligned table, right-hand fiscal filter, compact linked sources, on-demand selection and quiet pagination. Do not restore source-card strips, repeated year subtitles, 0 selected, permanent bulk controls or an inner width cap that leaves an empty side strip.
- Government Finances on Economy and Analytics opens on the Executive Summary view: COA's lines (Financial Condition, Results of Operation, Budget Execution) as rows, fiscal years as columns, then a Results of Operation chart and a Financial Condition chart (`design.md`, "Executive Summary View"). Header controls sit in one row (`design.md`, "Finance Report Polish"): 3Y 5Y All chips, a Year select that opens the Yearly Summary, an icon Table or Charts toggle (Charts is Trends) and an icon Help. The table has column lines, alternating row shades, Title Case section rows and an arrow in each year cell against the year before, no Change column and no scrolling (26-11-04); no year-range caption. Capitol Analytics alone adds a compact figure strip. Each view has one collapsed COA Sources disclosure grouped by report year, with links to the stored COA PDF at the cited page (province totals keep a compact plain attribution), and a short Help sheet in the card header (`design.md`, "Government Finances Ledger Table"). The Yearly Summary reads the year before, then the selected year with its arrow. Abbreviated amounts show the exact peso amount, the change and its percent on hover through `ExactAmount`. No Source Details drawer, Compare control, source text in tooltips, or repeated citations. State Tuguegarao City only coverage; do not hide it.
- Surfaces (26-11-04, `design.md`, "Surfaces and Page Headers"): every table, tab row, filter row and form sits in `components/ui/surface.tsx`, every Capitol page starts with `components/capitol/page-header.tsx`, row edit is a pen icon button and toolbar search is a magnifier. A verified figure in an entry grid is locked behind its pen. Never hand-roll `rounded-sm border bg-card`.
- Dense data (26-11-03, `design.md`, "Dense Data Rule"): never grids of padded figure tiles; figures go in table rows. Every table uses `components/ui/table.tsx` (border, column lines, alternating rows by default). An amount and its change arrow share one tooltip. Finance charts follow `design.md`, "Finance Charts": Results of Operation is one area chart with a Net Income line, Financial Condition and Revenue Sources are stacked areas, no synchronized tooltips. Budget and Actual Amounts is withdrawn from display.
- Do not add dangling metric/privacy disclaimers, automatic snapshot captions, chart-size explanations, or fallback instructions that reference removed views. Keep definitions in docs and secondary provenance/date in an accessible contextual tooltip. Home has working wish/Economy actions; do not restore placeholder cards or the removed protection section.
- Keep interface copy concise. Do not add helper descriptions that repeat a heading, label, placeholder, or button, or examples that restate what a field already accepts. Add explanatory text only when it clarifies an action, requirement, or consequence the control itself does not communicate.
- Before changing an existing screen, follow `docs/engineering/design.md`, "Working on Existing Screens". It records the user's layout, copy, map, form, source, and validation corrections. Do not reintroduce removed statistics, helper paragraphs, legends, modals, map CTAs, or duplicated controls under another label. Use underlined links without decorative arrows.

Full rules: `docs/engineering/design.md`.

- Showcase reels use full-screen motion, new campaign visuals, quick edits that follow interaction state, and sound feedback. Keep official Kita artwork. The rejected screenshot and drifting camera treatments are documented in the plan archive.
- Achievements use the original compact hexagonal medals. Keep factual eligibility and labels. Limit refinements to outline, number spacing, and alignment.

## Capitol Workspace Decisions

- Approved Revenue + Economic Status direction: retain verified city/municipal government revenue as explicitly labeled context alongside economic activity, household conditions, business records, and community interests. Government revenue is not private-business sales, GDP, or a prosperity/investment score; national transfers also affect it. GDP and survey figures retain their source geography and period, so province figures never become town/barangay figures. Keep the business-information purpose; no auditing pivot. See docs/product/mvp-scope.md, "October 24, 2026 clarification: Revenue and Economic Status". The foundation labels government Income and Expenses explicitly (COA's words since 26-10-29, always under Government Finances or as "Government Income") and withholds unchecked figures. Every loaded COA figure is signed off after the 26-10-28 reliability review (data/towns/finances/README.md); workbook breakdowns load for Tuguegarao City only (`cagayan.finances.workbook_towns`).

- Finance Ledger and Analytics Program, approved October 27, 2026 (docs/plan/archive/26-10-27-finance-ledger-analytics-program.md, completed 26-10-28). It covers six parts: a local Python COA parser (D34); one finance ledger for parsed and manually entered figures, with sign-off by someone other than the person who entered the figure (D35); revenue sources, business taxes and fees, and development spending from the audit-report workbooks (trimmed to a business focus 26-10-28); industries plus Business Categories (D36, D39); and a public and Capitol analytics engine. COA records describe the local government, not private business revenue or measured demand. Definitions (metrics.md M19 to M31, approved October 28, 2026), source verification and restatements accompany every series. Audit opinions, audit findings and infrastructure project lists are excluded. Full-year 2026 actuals wait for a released report.

- Fixed labeled desktop sidebar with no collapse control or selected-tab accent bars; compact mobile menu and breadcrumb strip.
- Map selections and geographic breadcrumbs use client-side Inertia history, not server visits. Cache each town dataset and keep the map mounted during selection.
- Do not add descriptions below page titles. Do not restore removed explanatory paragraphs about expressed interest, unavailable workflows, or boundary limitations on workspace screens. Keep provenance and limitations in data documentation.
- Use the [Capitol Dashboard pattern](docs/engineering/design.md#capitol-dashboard) when editing the map, finance tables or review queue. Keep counts and rank annotations tied to the loaded, authorized figures.
- Community Wishes is a separate Admin/Super Admin page. Never send wishes to Staff or any dashboard response, including the map.
- Province-wide economic summaries are available to all three roles. Staff editing is restricted to assigned towns; business editing remains future work.
- Only Super Admin sees Activity Logs and may change roles. Admin manages Staff accounts when that screen is built. Admin and Super Admin manage Business Categories and Business Types on Business Categories (D39): each Type has exactly one Category, names are stored as typed with a Title Case suggestion, category colors come from the `--category-N` palette with no color package, and Category sales are bands. All three roles open the read-only Businesses directory (public fields only, server-paged); adding businesses and permit records belong to the registry plan.
- Compact greeting with stored display name, date, and 24-hour Philippine clock. Keep the role in the sidebar account area only. Birthday month/day is optional and private; never collect birth year.
- Token styles only. Shared Capitol pieces in components/capitol, maps in components/map, semantic icons in lib/icons.ts, hooks and formatters outside pages.
- Session: 30 minutes idle, 8 hours absolute, 2 minute warning. Background requests never extend idle time. Verify expiry on the server before ending a shared session. Encrypt Inertia history and clear it on sign-in/logout/expiry.
- Input constraints live in config/cagayan.php and are supplied to forms. Display names are 1–100 Unicode characters without markup/control characters. Future business names use VARCHAR(55). Never silently truncate or force name capitalization.
- Contact and other future business-field formats require approval when those forms are planned.
- New private records use random UUID public identifiers; geographic PSGC codes remain public. Every protected action still requires server authorization.
- No individual business sales or protected field values in audit events. Activity Logs is append only, with safe event codes and actor keys.

## Rules that are never broken

- No single business's sales ever leaves the server.
- Every sales figure comes from the privacy guard.
- Pages get Data objects, never models, including shared props.
- Admin fields only behind login, and every full record view is logged.
- Every screen and export with simulated business records shows the "Simulated data" label. Simulated records are off unless `CAGAYAN_SIMULATED` is true (D37).
- Thresholds live in `config/cagayan.php`, never as literals.
- Metrics are defined in `docs/engineering/metrics.md` and computed in SQL. A definition changes only with a human-approved doc change.
- Checklist content needs an official source and a verification date from a team member. Never write a requirement from memory.
- Tests run on PostGIS, never SQLite.
- Never edit a migration that has been merged. Add a new one.
- Docker (`compose.yaml`, `docker/`) is shared by the whole team and CI. Change it only when the task is about Docker, as its own `chore/` pull request. Per-laptop settings such as the port go in `.env`. Never run commands that delete the database (`docker compose down -v`, `docker volume rm`).
- Never commit or push directly to `main` or `staging`. Work pull requests go into `staging`. `main` changes only by a release from `staging`. Never force push a shared branch, never skip hooks.
- No attribution trailers or generated-by text in commits or pull requests.

If a product rule, metric, or privacy rule looks wrong, or two docs disagree, stop and ask. Do not resolve it quietly.

## Read before you touch

| Working on | Read first |
| :- | :- |
| Any feature | `docs/product/mvp-scope.md` (features C1 to C14, P1 to P5, the demo walkthrough) |
| Routes, controllers, models, queries, services | `docs/engineering/architecture.md` |
| Pages, components, styling, copy | `docs/engineering/frontend.md`, `docs/engineering/design.md` |
| Migrations, models, factories, seeders | `docs/engineering/database.md` |
| Any number on screen | `docs/engineering/metrics.md` |
| Anything showing business data | `docs/engineering/privacy-rules.md` |
| Tests | `docs/engineering/testing.md` |
| Adding a package or making a decision | `docs/engineering/decisions.md` |
| Branches, commits, pull requests | `docs/team/workflow.md` |
| Feature status | `docs/team/roadmap.md` |
| Boundary files | `data/boundaries/README.md` |
| Town figures (population, budgets) | `data/towns/README.md` |
| Barangay population | `data/barangays/README.md` |

## Skills

Use the versioned project skills under [`.claude/skills`](.claude/skills). For a project-owned skill, read its tracked `SKILL.md` even when a local `.agents/skills` copy is discovered. Laravel Boost owns the six framework skills listed in [boost.json](boost.json) and the tagged guidance in [CLAUDE.md](CLAUDE.md). For framework work, consult the matching skill and installed-version docs. Repository rules take precedence over framework defaults.

Start each task from one:

- `/plan-history`: maintain agreed plans, changes, checks, and archive under `docs/plan/`.
- `/feature-builder C3`: build one feature as a tested vertical slice.
- `/metric-builder M10`: build one metric in SQL, test first.
- `/db-change <what>`: change the schema with its model, factory, seeder, and doc.
- `/checklist-content <item>`: add a start-a-business guide item from an official source.
- `/commit`: run the checks, review the diff, commit, and print the pull request description.
- `/open-pr`: push and open the pull request into `staging`.
- `/privacy-check` or the `privacy-reviewer` agent: before committing anything that touches business data.
- `/ui-builder <screen>`: build or restyle a screen from the design system.
- `/design-check`: before committing a UI change.
- `/demo-check`: before recording or the booth.

## Done means

Acceptance checks met. Tests pass, including privacy cases. `composer ci:check` green. Loading, empty, not available, hidden, and error states handled. Simulated label shown wherever simulated records are on. Wording follows `design.md` and `privacy-rules.md`. Roadmap status updated. When an agent gets something wrong twice, add the correction here or in the matching rule in `.claude/rules/` in the same pull request.
