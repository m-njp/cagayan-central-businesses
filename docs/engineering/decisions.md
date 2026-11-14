# Engineering Decisions

What the team decided and why. Change a decision only through a pull request that edits this file, so the reason travels with the change.

**Decided** means build on it. **Open** means a default is in place and a named person still has to confirm it.

## D1. Laravel with Inertia and React in one application

**Decided.** Laravel serves the pages, React renders them, Inertia connects the two. One codebase, one deploy, no separate API, no token login. Created from the official Laravel React starter kit.

**Why:** the video is due October 30. One codebase removes a whole category of work, and PHP is common in Philippine government IT, which helps the Technical Feasibility score and makes handover to the Capitol realistic.

## D2. PHP and Node on the laptop with the database in Docker

**Decided.** Patched PHP 8.3 (8.3.36 or newer) with `pdo_pgsql` and `pgsql` on the command line (WAMP, Laravel Herd, or php.new). Node 22.18 or newer in the 22 release line. Docker Desktop runs only the database, from `compose.yaml`. All three members use the same PHP minor version, and CI uses it too. Composer also accepts patched PHP 8.4 and 8.5 versions, with security floors recorded in `composer.json`.

**Why:** the team knows WAMP. Moving only the database into Docker teaches Docker without changing the daily workflow a month before the deadline. After the video, PHP may move into Docker with Laravel Sail.

## D3. PostgreSQL 17 with PostGIS 3.5

**Decided.** Image `postgis/postgis:17-3.5`. Two databases in one container: `cagayan_business` and `cagayan_business_test`. Tests always run on PostGIS, never SQLite.

**Why:** PostGIS is the standard for map systems, QGIS connects to it directly, and it has the spatial functions more towns and layers will need. Do not bump to PostgreSQL 18 without reading the image notes: its data volume path changed.

## D4. Commands are Composer scripts and Artisan commands

**Decided.** No bash or PowerShell setup scripts. Everything is `composer <script>` or `php artisan <command>`, written as arrays of single commands with no shell operators.

**Why:** the team is on Windows, and PHP runs the same everywhere.

## D5. MapLibre GL JS through react-map-gl for the map

**Decided, updated October 3, 2026.** Shapes come from PostGIS as GeoJSON and are drawn as filled, clickable layers. Shapes are simplified before loading. The basemap (streets and place names under the shapes) is OpenFreeMap's `positron` style: free, no API key, no account. When the basemap cannot load, for example at the booth without internet, the map falls back to a plain background and the shapes still draw, so the demo never depends on it. A self-hosted basemap (VersaTiles or PMTiles) is optional polish after the video.

**Why:** a basemap helps people find their barangay, and OpenFreeMap costs nothing. The fallback keeps D5's promise that the booth demo runs offline.

## D6. Supporting libraries

**Decided, updated October 3, 2026.** Charts: Recharts, through shadcn's Chart component. Tables: TanStack Table. PDF: `@react-pdf/renderer`, built in the browser (replaces `barryvdh/laravel-dompdf`, see below). CSV: a streamed Laravel response, no package. Boundary preparation outside the app: QGIS and mapshaper. Fonts: IBM Plex Sans and IBM Plex Mono (replaced Instrument Sans and Source Serif 4 on October 19, 2026, see D22), both through the Vite fonts plugin, so no npm package. Each is installed with the first feature that needs it, and its version recorded below.

**Why `@react-pdf/renderer`:** dompdf supports only old CSS, so a brief laid out with it looks dated and is hard to style. `@react-pdf/renderer` lays out pages with flexbox in React, which the frontend developer already writes, and the server stays free of a PDF engine. The PDF is built from the props the page already has, so it carries the same guarded data as the screen (privacy-rules.md, P11).

## D7. Metrics are computed in SQL and stored in summary tables

**Decided.** `php artisan metrics:refresh` rebuilds every `stats_` table from raw records. Screens read summary tables, never compute metrics in PHP loops or React. Definitions are in `metrics.md`.

**Why:** one definition per number, testable with a small fixture, and the exact query can be shown to a judge.

## D8. Privacy rules are enforced in one place on the server

**Decided.** One service applies the small-group, dominance, and band rules. Pages receive Data objects whose fields were chosen on purpose, never models. Rules are in `privacy-rules.md`.

**Why:** with Inertia, everything passed to a page can be read in the browser. Hiding a value in React protects nothing.

## D9. Login and roles

The original single-role decision below is superseded by D28.

**Decided.** Login from the starter kit (Fortify). Public registration is off. Capitol administrator accounts come from the seeder or an Artisan command. One role in the MVP, `capitol_admin`, checked by a `capitol` gate on the Capitol route group. The public side has no login.

## D10. Simulated data

**Decided.** Seeders with a fixed seed, so every laptop shows the same numbers. Data years 2020 through 2026, outlook year 2027. Since 26-10-28 the simulated business seeder is optional and off by default (D37). Every simulated record has `is_simulated = true`, and every screen and export that shows business records carries a visible "Simulated data" label. Requirements are in `database.md`, "Seeder requirements".

## D11. Quality gates

**Decided.** Pest for tests. `composer ci:check` runs the front end check, TypeScript, Pint, Larastan, and the tests. CI must be green to merge.

## D12. CI and CD

**Decided for CI. Open for CD (O5).** GitHub Actions on every pull request and every push to `main` or `staging`, with a PostGIS service container on the same image as local. Deploy runs on a version tag over SSH and stays off until `DEPLOY_ENABLED` is set. See `deployment.md`.

## D13. Git workflow

**Decided, updated October 3, 2026.** `main` is production. `staging` is development and the default branch. Every task is a short-lived branch inside `staging` (`feat/`, `bug/`, `data/`, `docs/`, `chore/`), squash merged back into `staging`. `main` changes only through a release pull request from `staging`, merged with a merge commit so the two keep sharing history, then tagged. Both branches are protected. Conventional Commits, no attribution trailers. See `docs/team/workflow.md`.

## D14. Laravel Boost

**Decided.** Boost is a development dependency. It gives Claude Code documentation and tools that match the installed versions. It rewrites the `<laravel-boost-guidelines>` block in `CLAUDE.md` and the skills listed in `boost.json` on `composer update`. Claude imports the shared project guidance from `AGENTS.md`. Project-owned skills live under `.claude/skills/`. [Shared project instructions](../../AGENTS.md).

## D15. Keep Inertia and React

**Decided October 3, 2026.** Reviewed after kickoff. Replacing Inertia with a React single-page app and a JSON API would add an API layer, token auth, client routing, and duplicated validation, for no gain in this project. Switching React to Vue would mean rewriting every starter page for no gain. Vite stays either way: it is the build tool, not an alternative to Inertia.

## D16. Git hooks check every commit and push

**Decided October 3, 2026.** `.githooks/` runs for people and AI agents alike.

- `pre-commit`: refuses `main` and `staging`, secrets, build output, and debugging leftovers, then runs the fast checks for the file types staged (Pint, PHPStan, front end format and lint, TypeScript).
- `commit-msg`: strips AI attribution and checks the Conventional Commit format.
- `pre-push`: refuses `main` and `staging`, then builds and runs `composer ci:check`, the same as CI.

**Why:** commits stay quick, and nothing broken leaves a laptop. Claude Code is denied `--no-verify` in `.claude/settings.json`.

## D17. Back end structure

**Decided October 3, 2026.** Controllers stay thin. Reads go in Query classes (`app/Queries/`), writes in Action classes (`app/Actions/`), shared domain rules in Services (`app/Services/`), and page payloads in plain readonly Data classes (`app/Data/`). No repository layer, no data-object package. Lazy loading of relationships throws outside production. See `architecture.md`.

**Why:** each piece has one job, pages get only the fields we chose, and nothing is added before it is needed.

## D18. Progressive Web App later

**Decided October 3, 2026.** Not before the video. Manifest and icons may be added any time. A service worker that caches static assets only may follow before the booth. See `pwa.md`.

## D19. Docker is shared setup

**Decided October 3, 2026.** `compose.yaml` and `docker/` belong to the whole team and are owned by the backend developer. They are not locked: a change is its own `chore/` pull request, reviewed by the backend developer, with the CI image kept in sync. The pre-commit hook warns when they change. Per-laptop settings live in `.env`. Claude Code is denied only the commands that delete the local database.

**Why:** every laptop and CI depend on the same setup, so a change must be deliberate and announced. Locking it would block the team from fixing it when needed.

## D20. Discreet admin login

**Decided October 3, 2026.** The login page lives only at the path in `CAGAYAN_ADMIN_LOGIN_PATH` (`config('cagayan.admin.login_path')`). No public page links to it. `/login` does not exist, and guests who open any login-only page get a plain 404 instead of a redirect that would reveal the path. The login responses carry `X-Robots-Tag: noindex, nofollow` and `Referrer-Policy: no-referrer`, and the path is not listed in `robots.txt`. Login is limited per email and address per minute, per address per hour, and per account per hour from any address (a lockout). Under D28, only active accounts assigned Super Admin, Admin, or Staff can sign in (`Fortify::authenticateUsing`), with the same failure message as a wrong password. Every Capitol route sits in `routes/capitol.php` behind `auth` and `can:capitol`, and a test checks each one. Production refuses to serve web requests with the example path. Console commands skip the check, because CI and `composer setup` run them before `.env` exists.

- Sessions: passwords are hashed (bcrypt), the session id is regenerated at sign-in, cookies are http-only and SameSite=Lax, CSRF is checked on every web form, and production sets `SESSION_SECURE_COOKIE=true` and `SESSION_ENCRYPT=true` (deployment.md).

- Fortify's own routes are off (`Fortify::ignoreRoutes()`). `routes/web.php` registers Fortify's controller at the secret path for GET and POST, named `admin.login` and `admin.login.store`, plus `logout`. The Capitol home is `capitol.dashboard` at `/capitol`.
- The login form posts to the URL it was served from, not a Wayfinder import, and the route names sit outside the `@/routes` index, so the path never reaches the public JavaScript bundle. `LoginPathOutOfBundleTest` checks the build.

**Why:** the people who need the login are a handful of Capitol staff, so there is no reason to advertise it. A hidden path is one layer, not the protection. The protection is the server-side auth check, the rate limits, strong passwords, and HTTPS.

## D21. Wishes recognize a browser, not a person

**Decided October 3, 2026, updated October 5, 2026.** Replaces the daily hash in privacy-rules.md, P12. Wishes are called suggestions on screen. One new suggestion per browser every 3 days, and up to 3 edits in any 7 days. A browser is recognized by a random token in an encrypted, signed, http-only cookie, stored only as a SHA-256 hash. The token enforces the cooldown and the edits.

- **Network hash (October 5):** each suggestion also stores an HMAC of the network address and the date, keyed with the app key. It changes every day, so it never links one day to the next, and the raw address is never stored. It caps how many new respondents (browsers with no earlier suggestion) one network can bring in per day (`wishes.new_respondents_per_network_per_day`), so clearing cookies stops being a way around the cooldown, while a school or the booth wifi still works.
- **Bot checks:** a hidden honeypot field, and a signed timestamp issued with the form (`FormTimer`) that must be at least `wishes.min_fill_seconds` and at most `wishes.max_fill_minutes` old when the form returns.
- **Input:** reasons are stripped of tags and control characters, spaces are collapsed, links are rejected, and the length is capped. The hourly rate limit per address stays.

**Why:** a cooldown and weekly edits need to recognize the same submitter for a week, which a daily hash cannot do. Keying the cooldown on the network address would put everyone behind one mobile carrier address, or the booth wifi, under one cooldown, so the network hash only caps new respondents. Suggestions are counted as interest, not as proof of demand.

## D22. Design system: halftone, Plex, square corners, Title Case

**Decided October 19, 2026.** The interface follows one written design system, `docs/engineering/design.md`, built from tokens in `resources/css/app.css`. Claude starts UI work with `/ui-builder` and checks it with `/design-check`.

- **Fonts:** IBM Plex Sans for words and IBM Plex Mono for numbers, labels, and the map, through the Vite fonts plugin. They replace Instrument Sans and Source Serif 4 (D6). Plex is open-license, very legible, and reads as institutional, which suits a provincial government.
- **Corners:** `--radius` is 2px, and every `rounded-*` step resolves to about that.
- **Halftone:** map values are drawn as same-size SVG dots on one shared grid, shaded by value. Dots carry data only, so this keeps the "no decoration" rule. No CSS gradients are used to draw them. Sizing dots by value was tried and dropped, because small towns vanished.
- **Capitalization:** Title Case for titles, headings, buttons, tabs, badges, labels, and legends. Sentence case for sentences. This replaces the sentence case rule.
- **Shell:** public pages have no top bar. A slim line with the app name, the "Simulated data" tag, and the light and dark switch scrolls away with the page. Phones keep the bottom bar.
- **Home keeps its layout:** the ink hero now holds the shaded town map. A click picks a town, a double click opens its population, revenue, and expenditure.

**Why:** the team lead asked for a technical, trustworthy look for government users, less empty space, and a map that invites exploring, built on the existing design rather than replacing it.

## D23. Town figures from a CSV, on a hand-drawn SVG map

**Decided October 19, 2026.** Population and economic figures for the 29 towns live in `data/towns/town-facts.csv`, one row per town, measure, and year, with a source for each. `towns:import` loads it into `town_facts`. The Home map stays our own SVG (`province-map.tsx`) instead of a map package.

**Why:**
- PSA and DOF publish no public API, and their sites block automated reads, so "live" updates mean editing one reviewed file and rerunning one command.
- Long format means a new census year is new rows, not new columns or code.
- A package such as react-simple-maps is unmaintained and does not support React 19. MapLibre stays for the business map (D5, F6), where a basemap and zoom are needed.
- The SVG map is a few kilobytes with no dependency and draws the halftone directly.

## D24. Geist typography, simple town details, and flexible wishes

**Decided October 23, 2026.** Geist is the interface face and Geist Mono is used for numbers and labels. Both variable fonts are self-hosted from `resources/fonts/` with the OFL license from `drSabine/personal-fonts-repo`, so pages work offline. Clicking a town shows all its figures beside the map; there is no double-click action or details dialog. The wish form accepts a business type, a specific company, or both, and shares one canvas with the map.

**Why:** the UI should use the user's Geist collection, keep map details visible in place, and let residents express a wish at the type or company level.

## Open decisions

Each has a default so work is not blocked.

- **O1. Boundary source.** Owner: data member. Default: the files in `data/boundaries/` from `faeldon/philippines-json-maps` (MIT), cross-checked against `bendlikeabamboo/barangay-boundaries-repository`. Still open: the terms for the underlying NAMRIA boundaries, checking the 49 codes and names against PSA's current listing, and whether to use full-detail shapes.
- **O2. Public sales band edges.** Owner: team with adviser. Default: under 10 million, 10 to 50 million, 50 to 100 million, 100 to 500 million, 500 million to 1 billion, over 1 billion pesos.
- **O3. Dominance threshold.** Owner: team with adviser. Default: 70 percent.
- **O4. Peer comparison margin.** Owner: team with adviser. Default: 20 percent below the peer median.
- **O5. Hosting for the online version.** Owner: back end member. Default: one small VPS. Not needed for the video or the booth.
- **O6. Size of the simulated dataset.** Owner: data member. Default: about 4,000 businesses. Replace with a figure grounded in a public count if one is found, and cite it.
- **O7. Outlook middle estimate.** Default: the average renewal rate of the three most recent years. Confirm with the adviser. See `metrics.md`, M11.
- **O8. Unused starter kit features.** Decided October 3, 2026: removed before the build starts, so the design system starts from a clean slate. Kept: Fortify login and logout, the shadcn primitives in `components/ui/`, and the hooks they need (`use-mobile`, `use-appearance`, `use-flash-toast`). Removed: registration, password reset, email verification, two-factor login, passkeys, profile, security, and appearance settings, account deletion, the starter layouts and app components, and the `@laravel/passkeys` package. The `passkeys` table and two-factor columns are dropped by a new migration. October 5, 2026: the starter kit's own non-shadcn files in `components/ui/` (`placeholder-pattern.tsx`, `icon.tsx`) and its icon rail were removed. Every shadcn primitive stays, including `sidebar` for the Capitol layout.

## Installed versions

Updated October 24, 2026, from `composer.lock`, `package-lock.json`, and the running database. Update this when a package is added, removed, or upgraded.

- PHP 8.3.36 locally (WAMP and standalone CLI), latest patched 8.3 in CI
- Laravel 13.34.0, Fortify 1.40.0, Wayfinder 0.1.21, Boost 2.10.1, Larastan 3.12.2, Pint 1.32.1
- inertia-laravel 3.5.1, @inertiajs/react 3.9.1
- React 19.3.1, TypeScript 5.9.3, Tailwind CSS 4.3.3
- vite-plus 1.4.0 (`vp`) with the matching `@voidzero-dev/vite-plus-core` Vite alias and npm override. `npm run check` is `vp check`, which formats and lints. Updated to address the frontend tooling security advisories; install scripts remain disabled.
- Node 22 in CI. Node 24.22.1 locally.
- PostgreSQL 17.5, PostGIS 3.5.2
- Pest 4.7.8, Paratest 7.20.0
- maplibre-gl 6.15.0, react-map-gl 8.1.3, recharts 3.10.1. The PDF renderer has no current consumer and returns with C14 (26-11-09, D5).
- Not installed yet: TanStack Table (first table)

## Changes from the starter kit

- `AGENTS.md`, `.claude/`, `CLAUDE.md`, `.mcp.json`, and `boost.json` are shared team files. Keep `.claude/settings.local.json` and `CLAUDE.local.md` local.
- The formatter in `vite.config.ts` skips Markdown, `data/`, `.claude/`, and the Docker and Boost config files.
- `composer types:check` runs PHPStan with `--memory-limit=1G`, because WAMP's default limit crashes it.
- Public registration is off: the Fortify feature, its page, and its links were removed. `tests/Feature/Auth/RegistrationDisabledTest.php` checks it.
- Boost's `infer-conventions` skill was removed. The team records conventions in these docs instead.

## D25. Preserve Map Panel Scope and Expand Census Explorer

October 23, 2026. The Home map panel is restricted to population, revenue, and expenditure and their sources, following the user’s correction. Barangay count, income class, and poverty incidence are excluded. Remove the unsolicited right-panel legend and zoom instruction. Sources are stacked one per line, with readable foreground text. About the Data offers all 29 Cagayan towns, loading one local geometry/population chunk on selection. The PSA 2024 Cagayan workbook provides population; existing documented 2023 boundary sources provide shapes. This extends D24 without changing the wish form layout.

## D26. Map Presentation and Explorer Caching

October 23, 2026. The user requested an alternative to the dotted map and removal of repeated explorer reloads. Dotted remains the default; Solid uses the same local geometry, blue population scale, brass selection, tooltip, and keyboard interactions. Both Home map toolbars expose the shared browser preference beside their town selectors. Visited census datasets, pending imports, SVG projections, and dot grids are reused rather than rebuilt on every change. Cached towns do not replay their entry animation. Public data and privacy copy states existing disclosure and hashing practices formally, without asserting legal compliance. Population references use the PSA 2024 release supplied with the Cagayan workbook.

Follow-up: the user requested one central icon control beside the main town selector, replacing the duplicated text controls. The hero map is taller with reduced bottom padding. The unused starter Sidebar, its sole mobile hook, and its unused CSS tokens were removed. The form selection reload was audited only: both selectors and map clicks call an Inertia GET for the entire `page` prop, with a count skeleton while pending; optimizing that request remains separate work.

The next cleanup lightens dark mode to soft neutral charcoal with progressively lighter card and control surfaces. Nine unused starter primitives (avatar, breadcrumb, checkbox, collapsible, dialog, dropdown menu, one-time-code input, navigation menu, and sheet) were removed after checking their references. The shared map no longer bundles or prepares a second static Tuguegarao map: its barangay geometry now comes exclusively from the existing on-demand census dataset. The redundant `shapes` selector prop was removed from callers.

Color correction: replace the neutral charcoal with muted blue slate surfaces sharing the river ink hue. Retain the lighter base and readable text and field borders, so dark mode complements the blue map without returning to near-black.

The form reload fix keeps selections and drafts local, requests only an optional category tally, and lazily resolves the full form payload. Repeated selections make no request; recent counts are reused, superseded requests are cancelled, and submission invalidates cached counts. Business Type is shown first, with an explicit control to reveal Specific Business. Either field or both remain valid, and editing a company wish opens its field automatically.


## D28. Super Admin Workspace Foundation

Approved October 24, 2026. Supersedes D9's single-role limitation and O8 only for the new private workspace profile. Roles are Super Admin, Admin, and Staff. Dashboard economic summaries are province-wide for all roles. Community Wishes is separate and restricted to Admin/Super Admin; logs and role changes are Super Admin only. Staff's future editing is assigned-town scoped. User management, taxonomy editing, business entry and publication remain later work.

MapLibre and react-map-gl implement the admin map using existing dependencies, local documented boundaries, on-demand barangay datasets, population shades, and optional OpenFreeMap positron tiles. The landing page retains its SVG map and approved structure. Dashboard is map-first with compact details. Later user corrections remove the requests/approvals placeholder until those workflows exist. No community wishes or sample business records on that dashboard.

Existing password sign-in is retained for prototype reliability; Microsoft/Google integration is deferred. Maintain local identity and permissions independently of a future verified provider subject. No Auth.js, Clerk, or OAuth dependency is added. Existing accounts migrate to Admin; promotion to Super Admin is explicit through the trusted console command. Sessions expire after 30 idle minutes or 8 hours, warn two minutes before expiry, and cannot be kept alive by background reloads. Encrypt private browser history and clear it on login/logout/expiry.

Display names are 1–100 characters with Unicode preserved. Optional birthday month/day is private; no birth year. Future business names have a 55-character storage and validation contract. Other future field formats require explicit agreement. Audit records are database-enforced append-only safe metadata, never raw private payloads.

Verified fiscal figures are labeled Government Revenue and Government Expenses, retain exact cents, and link original COA archives. Unchecked source rows are withheld. Province totals require complete same-year population/count coverage; town government accounts never become provincial-government finances. See `data/towns/verified-finances-2024.md`.

## D30 Economic Performance Demo

Decided 26-10-25. Add a separate public Cagayan Economic Performance page using isolated synthetic Tuguegarao data for Government Finances and Community Wishes. Keep the existing Home map unchanged. Use Recharts through ChartContainer for financial lines and ranked wishes; add user-approved `@visx/wordcloud` exactly 4.0.0 for browser-mounted, phrase-preserving clouds. Its official v4 release supports React 18/19: https://github.com/airbnb/visx/releases/tag/v4.0.0. Exact bundle cost is measured during final production build, not inferred from repository stars or package unpacked size.

Both finance Y axes share the same peso bounds; the X axis is fiscal year 2021 through 2025. Government Expenses retains its accrual meaning. No economic score or automatic favorable/unfavorable interpretation. Curated type and fictional specific-business wishes are separate modes, with identical aggregates feeding cloud/bars/table. No third-party runtime service or live raw submission publication.

The replacement boundary is the readonly Data contract, not a new API, generic repository or importer. Future source verification, history storage and SQL-derived measures stay separate. See data/economic-performance/README.md and the plan register. The user's instruction requires all tests and build checks only after the entire implementation and documentation are complete.

### D30 October 25 UI Correction

The user superseded the topic-separated presentation with a compact Cagayan Economy overview. Return both demo sections together, use independent financeView and wishView URL state, and reuse the SVG town map as a geographic picker beside finances. Remove the obsolete Topic/shared View state and per-device navigation title customization. Keep the route and data/privacy boundaries stable. The map adds geographic context without suggesting fiscal shading; Community Wishes adds a direct action to the existing submission form. No new packages.


### D31 Loaded Data Interactions Stay Local

Approved 26-10-25. Economy period, wish kind and town/map controls unnecessarily performed full Inertia GET requests and reran validation and municipality queries. This supersedes D30's server visits for those selections. Load the bounded public demo catalog once, with full finance rows, period boundaries and both curated wish snapshots; all control changes use local Inertia history. No global cache, new package, or privacy changes. Server validation still applies on entry/refresh. The same rule governs loaded map datasets and presentation controls across the app: request missing data only, cache/deduplicate it, and verify zero application requests for loaded selections. See frontend.md, "Local Selection Contract" and the [fix plan](../plan/archive/26-10-25-local-economy-selections.md).


### D32 Home Actions and Combined Wishes

Approved 26-10-25 by explicit user request. Replace Home's placeholder task cards and protection section with working town-aware wish/Economy actions. Combine curated type/specific wish labels into one backend-ranked snapshot, preserving counts and namespaced identities; remove kind filtering and all public Economy tables. Preserve privacy/metric definitions in docs, source metadata in contextual tooltips, and accessible exact observations without a table. Use bounded full-label ranked bars and town/count tooltips, with cloud count-chip fallback. Keep every loaded-data interaction local. No new dependency or live wish pipeline. See the [implementation plan](../plan/archive/26-10-25-home-economy-wishes.md).

## D33. Local Quick Sign In

Approved 26-10-27 by explicit user request. On a machine with `APP_ENV=local`, the hidden login page shows one button per seeded demo account (Super Admin, Admin, Staff). A button posts to `{login path}/demo/{role}`, which signs in as that account through `Auth::login`, so the session start and the `signed_in` Activity Logs entry match a password sign in. Anywhere else (`production`, `staging`, `testing`) the route answers 404 and the page receives no buttons. The check runs on every request (`DemoLoginQuery`), not when routes are registered, so a cached route list cannot enable it.

Accounts come from `config('cagayan.demo_accounts')`, filled from `.env` (`DEMO_SUPER_ADMIN_*`, `DEMO_ADMIN_*`, `DEMO_STAFF_*`), so credentials never enter the repository. `DemoAccountSeeder` creates the ones that are set and skips existing users; run it alone with `php artisan db:seed --class=DemoAccountSeeder`. A button appears only when the account exists, is active, and holds the matching role. The browser receives the role value and label only.

The route is not rate limited because it takes no guessable input and does not exist outside local. The login path still never enters the bundle: the buttons post to the page's own URL plus `/demo/{role}` (D20, `LoginPathOutOfBundleTest`). No new package, no new identity provider (D28). Production and staging must keep `APP_ENV` at `production` or `staging`; `deployment.md` already requires `APP_ENV=production`.
## D34. Python Source Tooling

Approved 26-10-27 by the user for the [Finance Ledger and Analytics Program](../plan/archive/26-10-27-finance-ledger-analytics-program.md). COA Executive Summary PDFs and audit-report workbooks are read by a Python parser in `tools/coa/`. Its dependencies (pdfplumber, openpyxl, pytest) are pinned in `tools/coa/requirements.txt` and installed in a local venv. Composer scripts run it, as D4 requires. The parser runs on team laptops only. It is not part of the app runtime, the Docker image, or CI. The app reads only the parser's committed CSV output under `data/towns/finances/`, so a clean clone works without Python.

**Why:** the user wants new report years added by running one command, not by reading every PDF by hand. The source files vary by town and year in sheet names, labels and table order, and pdfplumber and openpyxl handle both formats well. Earlier source-review helpers were already Python. Keeping Python out of the runtime leaves the PHP stack unchanged.

**26-10-29:** the parser now imports `pypdfium2` directly as a second PDF text engine, for fallback reading and digit confirmation. It was already pinned as a pdfplumber dependency, so no package was added. Python in `tools/coa/` uses camelCase names (user rule). See [the parser hardening plan](../plan/archive/26-10-29-coa-parser-hardening.md).

**26-11-03:** superseded in part by D42. The queue worker now runs the same parser on one uploaded file. A clean clone still works without Python; only report uploads need it.

**26-11-09:** CI initializes the pinned Python environment and runs the parser tests through `composer data:finances:test` before the PHP checks. The real queued-workbook integration test requires the same parser runtime as report uploads.

## D35. Finance Ledger

Approved 26-10-27. Government finance figures leave `town_facts` and move into one ledger:

- **Tables:** `finance_lines`, `finance_documents` and `finance_figures`.
- **Two entry routes:** the parser's import and a Capitol form. Each figure records how it was entered, its source document, and its page or cell.
- **Sign-off before publication:** a figure is publishable only after a person signs it off. Staff may enter figures for assigned towns. Admin and Super Admin may enter figures anywhere and may sign off. Nobody signs off a figure they entered.
- **One current figure:** each town, fiscal year, line and basis has at most one verified figure. When a later report restates a year, the later report's figure becomes current, and the earlier one is kept as superseded history.
- **Reproducibility:** sign-offs and manual entries are exported to committed CSVs, so `composer db:reset` reproduces the verified state.
- **Labels and limits:** labels and the line list come from `data/towns/finances/lines.csv`. Figures appear only at the reporting town's level. Totals across towns are labeled Sum of Reporting Towns, never provincial government finances.
- **Exclusions:** audit opinions, audit findings and infrastructure project lists are not extracted, stored or displayed. This was the user's decision on 26-10-27, and it supersedes the earlier note that audit qualifications must accompany the series.

**Why:** the user asked for revenue sources, spending by sector and budget execution, not only two totals. Admins must be able to enter and correct COA figures through a form. The verification rule must survive automation, so parsing assists people but never replaces their sign-off.

## D36. Industries and Focus Tags

> Superseded in part by D39 (26-10-28): Focus Tags are replaced by one Category per Business Type. The industry (PSIC section) part stands.

Approved 26-10-27. Each Business Type keeps one official industry, its PSIC section. A team member verifies that section against the PSA classification and records the check date. Admins manage Focus Tags (for example Agriculture and Fishery) and attach them to Business Types. A type can carry several tags.

Analytics groups businesses by industry or by tag. An agricultural supply store therefore counts under Wholesale and Retail Trade by industry, and under Agriculture by focus. Tags attach to types, not to individual businesses.

**Why:** the user wants to see which parts of a town's economy perform well. The official industry alone hides agriculture-related trade. Tagging every record separately would make each record harder to maintain, and simulated records would need invented tags.

## D37. No simulated data by default, one source per figure, our copy of the report

Approved 26-10-28 after the user said simulated data cannot be what the app shows.

- **Simulated records are opt-in.** `CAGAYAN_SIMULATED` defaults to false. While false, `db:seed` does not run `SimulatedBusinessSeeder`, the business trends query returns nothing even if old simulated rows remain, and the "Simulated data" label is not shown. Set it true for a presentation: `composer db:reset` seeds the records and the label returns on every screen and export (P13). The seeder, schema, privacy guard and Business Trends code stay, so a real registry or a presentation set can use them. Business Types and Focus Tags are starter taxonomy, not records, and stay.
- **Wishes are sample data, not records.** The Tuguegarao City demo wishes stay (`data/economic-performance/demo.php`, also the source of `WishSeeder`, so Economy and Suggestions agree). They carry a visible "Sample Data" label on Economy.
- **One source of truth for a figure's source.** `catalog.sources` holds one entry per figure; observations only point to it. No source text is repeated in rows, tooltips, charts or footers (design.md, "Government Finances Ledger Table").
- **Our copy of the report.** A citation opens the stored COA PDF at its page through `public.finance-sources.show`, not a COA address: COA's site sits behind a bot challenge and cannot be relied on to open a PDF inline at a page. The files already live in `data/towns/`. Only signed-off figures resolve. The COA address stays in the ledger as provenance.
- **Tuguegarao City is stated, not hidden.** Breakdowns beyond totals and barangay population exist for Tuguegarao City only; pickers say so instead of showing empty panels.

**Why:** the user's correction. A switch keeps the presentation option without making simulated numbers the default, and one evidence entry per figure ends the repeated source text.

## D38. Editable Business Categories and tag colors

> Superseded in part by D39 (26-10-28): the Focus Tag model is replaced by Categories. Editable names, the Title Case suggestion, the ten-slot color palette without a package, local table behavior and the simulated gate stand, now on Categories.

Approved 26-10-28. Admins and Super Admins add and rename Business Types (name, industry, Focus Tags) and Focus Tags (name, color) on the Capitol Business Categories page. A code is the slug of the first name and never changes, so wishes, records and links keep pointing at the same row. Names are stored as typed. The form only suggests Title Case, so nothing is forced (the "never force capitalization" rule stands). Names are unique ignoring case, plain text, and a Business Type name is at most 60 characters. Industries are the PSIC sections already seeded, so no section is written from memory. Removal and a citizen suggestion queue are not built.

Focus Tags carry a color slot from a fixed palette of ten (`--tag-1` to `--tag-10` in `app.css`, light and dark). New tags take the least used slot, and an Admin can pick another. **No color package:** a library produces arbitrary colors at run time, which breaks the tokens-only rule and cannot promise AA contrast in both modes. This is the one approved exception to "hue is reserved for data", because the tag name is always printed beside its color.

The page shows Registered, the active businesses of the latest data year from `stats_area_year` (M1, M28), per type and per tag. It reads counts only, never sales. A tag sums its types, and a business has one type, so nothing is double counted. Counts follow the D37 gate: with `CAGAYAN_SIMULATED` off they read "Not available". Every type is loaded (capped by `cagayan.categories.max_types`), so sorting, search, filters and pages are local history with zero requests.

**Why:** the user wants the list organized, counted and extendable by the Capitol without code changes, with proper labeling and colors that make tags scannable.

## D39. Category, Business Type, Business

Approved 26-10-28 after the user said categories are sectors and a Boarding House is not Tourism.

- **Tree.** Business Category (Tourism, Agriculture, Fishery, Retail, Vehicle and Motor Parts and so on), then Business Type (Hotel or Inn, Boarding House, Bakery), then the registered Business by name. Each Type belongs to exactly one Category. Categories replace Focus Tags and the `business_type_focus_tag` pivot. Admins and Super Admins add, rename and recolor Categories and Types on Business Categories. The starter seed is editable and reviewed by the data member.
- **Industry is a label, not a level.** The official PSIC section stays on the Type for official statistics. It is not a tree level because categories cross industries, and the Type already fixes its industry.
- **Privacy is unchanged.** A Category partitions businesses, so counts add up. It still crosses industries, so a category total minus an industry total could isolate a group the guard never checked. Category sales therefore stay bands for every audience (P4). Only the wording of P4 and M28 to M31 changes.
- **Businesses directory.** A read-only Capitol page lists registered business names with public-class fields only, server-paged and filterable by category, type, industry and status. It shows nothing while simulated records are off (D37). Adding, editing and importing businesses belong to the registry plan.
- **Registry boundary.** The later registry stores permit validity, renewal range, expiry and documents as published records. It does not process applications, fees or issuing, which stay with the city (mvp-scope.md).

**Why:** the user's correction. One Category per Type gives one clear tree, one color and an exact count, and it avoids two overlapping grouping systems. The directory and the schema keep the later registry from reworking the taxonomy.

## D40. Business Registry

Approved 26-10-29 with [Business Registry Records](../plan/active/26-10-28-business-registry-records.md).

- **Workflow.** Staff prepare records for their assigned towns and submit them. Admin and Super Admin approve them or return them for resubmission with a note. Only approved records are public or counted (P15). Editing an approved record sends it back for review.
- **Documents** (permit, BIR COR, BIR Registration Seal Badge, DTI SEC or CDA, the Tuguegarao permit list) are stored on the private `local` disk with a SHA-256 hash, limited by type and size in `cagayan.registry`, and served only through an authorized, throttled route.
- **BIR Registered badge.** Our own token-styled badge, shown when an approved record has a BIR Certificate of Registration on file (`bir_verified_on`). It is not BIR artwork, live BIR verification or a tax compliance claim. RR 15-2024 requires the COR on display at the place of business; RMC 38-2026 created the Registration Seal Badge and RMC 113-2026 suspended its mandatory online posting on 26-10-26.
- **Location.** Pin first, with no geocoder: the town and barangay selects zoom the map, the pin is placed by click or drag, and PostGIS checks it falls inside the barangay. The street address is typed.
- **Public fields** follow privacy-rules.md, "Field classes".

## D41. Contact Number Formats

Approved 26-10-29. Contact numbers are stored as typed: a mobile number `09XXXXXXXXX` or `+639XXXXXXXXX`, or a landline of digits, spaces, parentheses and a plus sign. The patterns live in `cagayan.registry`.

## D42. Report Uploads Read on the Queue

Approved 26-11-03 by the owner ([finance report redesign plan](../plan/active/26-11-03-finance-report-redesign.md)). Staff upload a COA report on Capitol Finances instead of asking the data member to run `composer data:finances:extract`.

- **One file at a time:** the queue job `ExtractFinanceUpload` runs `python -m coa.intake` from `tools/coa/` on that one file, never the repository-wide extraction. A PDF is an Executive Summary, an XLSX a Financial Statements or General Fund Annex workbook, a ZIP the audit report's workbooks. Size, archive and runtime limits are in `cagayan.finances.uploads`.
- **Same ledger, same sign-off:** figures arrive as pending, credited to the uploader, so someone else signs them off (D35). A different report already on record is never overwritten.
- **Private files:** uploads stay on the private `local` disk under `finance-uploads/` and are never served publicly, even after their figures are signed off (owner, 26-11-03): a file's author metadata could name the staff member who saved it. Public sources for those figures show the report and cells without a download link. A document read from an upload stays out of the CSV import and export, so `composer data:finances` neither deletes nor exports it.
- **Runtime:** the worker needs the `tools/coa/.venv` (or `COA_PYTHON`). `composer doctor` reports it. No Docker change and no new package: the parser's pinned dependencies are reused.

**Why:** the owner wants non-technical staff to load a new report year without the command line, while every figure keeps its exact source and independent sign-off.

## D43. Population Entry and Database Counts on Maps

Approved 26-11-03 by the owner ([Data Entry plan](../plan/archive/26-11-03-data-entry.md), Phase 4).

- **Proposals, then publication:** `population_figures` holds proposed census counts for a town (no barangay) or a barangay, with the same rules as finance figures: pending until someone other than the person who entered it verifies it, a reason to return, a reason to correct a verified count, stale-edit refusal, one write per submission, an official HTTPS psa.gov.ph source, limits in `cagayan.population`. A newer proposal for the same place and year supersedes an older pending one.
- **Publication writes the existing read tables:** a verified town count updates `town_facts`; a verified barangay count updates `barangays.population`, never replacing a newer census year. Every existing query keeps working.
- **Maps read the database, files keep shapes:** the Home census explorer and the Capitol dashboard map take counts and shades from `barangays` (`BarangayAtlasQuery`), and use `data/barangays/maps/*.json` only for boundaries, so a verified count shows without a rebuild. `barangays:import` and `towns:import` stay for bulk loads from the committed CSVs.

## D44. Data Entry Workflows

Approved 26-11-11 in the [Capitol Entry Redesign](../plan/active/26-11-09-capitol-entry-redesign.md#data-entry-workflow). Data Entry lazy-loads `@xyflow/react` 12.12.1 when the workflow guide opens. Each chapter uses fixed positions and shares its step definitions between the graph and HTML instructions. Opening and closing replace the current history entry locally; changing chapters adds a local entry so Back and Forward restore the open guide and chapter. Review pages are fetched from the server.

## D45. Corroborated Sign-Off and Reporting Differences

"26-11-12" follow-up: the owner explicitly requested checking the remaining 32 pending 2025 Income and Expenses figures against the COA PDFs and finishing verification. This specific batch permits agent inspection of the rendered source pages followed by sign-off through the existing reviewed CSV import, with no reviewer account impersonation. Notes identify it as an owner-directed agent PDF review, not two-report corroboration or independent human review. The [2025 verification record](../plan/archive/26-11-12-coa-2025-verification.md) preserves the amounts, cited pages, source hashes and retained reporting discrepancies. This exception does not authorize other pending years or finance lines.

Approved 26-11-12 by the project owner in [COA Accuracy and Reporting Differences](../plan/archive/26-11-12-coa-accuracy-and-differences.md). An owner-directed exception to D35's person-to-person sign-off, like the 26-10-29 Tuguegarao batch.

- **Corroborated figures sign themselves off:** `php artisan finances:corroborate` verifies a Pending Executive Summary figure only when a year's own report and the next report's prior-year column print the same amount to the cent, for the same line, basis and definition, and neither report has a blocking or error finding on that line or across the document. The note reads "Corroborated: the Y and Y+1 COA reports print the same amount"; the reviewer is empty. `finances:export` saves them to `reviews.csv`. Restated, single-report and flagged figures stay Pending for a person.
- **Three finding families:** every parser finding is "Within the Report" (the document disagrees with itself as printed), "Between Reports" (two reports print different amounts for the same year) or "Extraction Limits" (a limit of the parser). Anything that may be the parser's own limit is an Extraction Limit, never the government's. The mapping lives once in `tools/coa/coa/summary.py` and is written as the `family` column of `issues/<year>.csv`.
- **Report Differences:** a Capitol page for all three roles lists those findings by municipality and report year, with the stored PDF page of each.
- **One source of truth:** `composer data:finances:extract` always writes the committed CSVs and prints the added, removed and changed values; findings stay in `issues/` and nothing is published until signed off.

**Why:** the 2014 to 2019 reports could not reach the maps without hundreds of manual sign-offs, while two separately published COA reports agreeing to the cent is stronger evidence than one transcription checked once. The owner asked that flags about a government's reporting be certain before they are shown.


## D46. Workbook Intake for All Cagayan Governments

Approved "26-11-13" by the owner, explicitly selecting **2014–2025 workbooks for all 29 governments** after being asked about the previous Tuguegarao-only restriction. `cagayan.finances.workbook_towns` now contains all 29 canonical PSGC identities.

- Eligible M21 consolidated revenue and M22 General Fund details enter the existing ledger as Pending. No automatic sign-off or public publication is authorized. M23 remains withdrawn from display; newly parsed check-only evidence stays staged.
- Preserve existing observations, sign-offs and reviewed source evidence. Original collection files are never edited; registered copies retain matching hashes and source provenance. Ambiguous/unsupported layouts stay withheld.
- Executive Summary parser behavior, existing totals, detail-town restrictions, metric definitions and publication gates remain unchanged.
- The [ingestion plan](../plan/archive/26-11-13-workbook-ingestion-all-governments.md) records exact promotion/import counts, duplicate-source choices, immutable-source checks and review access. The [four expense reconciliations](../plan/archive/26-11-13-historical-expense-differences.md) are explanatory evidence, not automatic numeric corrections.


D46 clarification, "26-11-13": the owner subsequently required only Tuguegarao's nine extracted fields, not all catalog fields. The 208 newly imported, still-Pending Other Shares from National Taxes observations were removed; final new Pending intake is 2,177 observations. Existing figures, sign-offs and published statistics remain unchanged. Budget and Actual Amounts stays withdrawn, while a Pending/gray status alone does not exclude a supported field. [Current scope, UI evidence and integration limits](coa-workbook-handoff.md).


## D47. Executive Summary Lines for All Governments, N/A and the Finances Guide

Approved "26-11-13" by the owner with the plan [Workbook Data Trim, All-Town Executive Summary and Parser Upkeep](../plan/archive/26-11-13-workbook-trim-all-town-summary.md).

- **All 29 governments.** Assets, Liabilities, Government Equity, Current and Continuing Allotments and Continuing Obligations load for every government, not Tuguegarao City alone. `summary_detail_towns`, `summary_detail_lines` and the reason `not_loaded` are removed. New figures enter Pending and follow D35 and D45 like every other Executive Summary figure.
- **The 2023 Executive Summary change.** From 2023, COA's Executive Summaries print only Financial Position and Financial Performance (for example Aparri 2022 against Aparri 2024). Missing Budget Execution years read N/A with the reason `not_reported`; the parser is not at fault.
- **N/A.** A missing finance figure reads N/A in muted text, never `0` or blank. Its accessible name stays "Not available" and its reason moves from the row note to the cell tooltip. This supersedes the earlier design rule that avoided "N/A".
- **Government Finances Guide.** The right-hand Help sheet is replaced by a full guide in the blur overlay, opened from the header, a row label or an N/A cell, with a plain why-it-matters line for every measure and the four budget steps. Definitions in `metrics.md` stay authoritative.
- **Newest year (26-11-14).** A figure from a government's latest available report of its kind has no later report to confirm it. When it passes the verification rule below, `finances:corroborate` signs it off with no reviewer and the note "Newest year: only the Y COA report prints this figure so far; the Y+1 report will confirm or restate it". There is no on-screen marker (owner, 26-11-14); the note shows in the Capitol review and `reviews.csv`. The next report's prior-year column later confirms it or restates it.
- **Verification before automatic sign-off (26-11-14).** After the Enrile/Solana misattribution the owner asked that no figure be shown on trust. `finances:corroborate` signs off a figure only when two reports of the same kind (workbooks included) print the same amount, or, for the newest year, an Executive Summary figure with no holding finding or a workbook revenue figure whose sources add up to the workbook's own printed Total Revenue to the centavo. Every check is exact: a difference of even one centavo is a finding (owner, 26-11-14). A difference of ₱1.00 or less is a `minor_difference` that stays listed for reviewers but does not hold sign-off, and two reports at most ₱1.00 apart are signed off with the later report's amount and a note giving both amounts and the difference. Anything larger waits for a person. Each `finances:corroborate` run also returns to Pending any automatic sign-off (no reviewer) that a current finding now holds, with the copy it superseded; a person's sign-off is never reopened. On 26-11-14 this reopened nine 2024 workbook revenue figures for Gonzaga and Santa Ana. General Fund detail has no total to reconcile and waits for the next report or a person. Cross-government duplicates, changes of more than three times and another government's sheet hold sign-off. Extractor 1.7.0 checks file content before reading. Accuracy is measured with `composer data:finances:sample`.
- **Workbook sheets of another government (26-11-14).** COA files can keep another town's template sheet (Enrile 2019 Annexes carried Solana's statement; Lasam 2014 Annexes carry only Allacapan's). Extractor 1.6.1 never reads a sheet headed for another Cagayan government; when no sheet for the expected government remains, the workbook is a blocking `municipality_mismatch`.
- **National share (26-11-14).** Share from National Collections adds Other Share from National Taxes where a statement prints it separately (M21), so the field means the same for every government; 62 of 102 such workbooks carry a non-zero amount. Revenue completeness is proven against the workbook's own printed Total Revenue, not Executive Summary Income.
- **Workbook scope.** The nine approved workbook fields stay (D46). Expense types, Statement of Financial Position, Cash Flows, Changes in Net Assets, SCBAA sector rows and Annex schedules are excluded from display. The national-share scope and the SCBAA totals and Capital Outlay investigation wait for the owner's decision.

**Why:** the owner judged the gray "Loaded for Tuguegarao City only" and "Not available" cells to crowd the screen, and asked for help that explains each figure's role for good governance and for business proponents, in plain wording.

## D48. Three Workbook Fields and Retirement of Annex Extraction

Approved "26-11-14". Executive Summary parsing and fields remain the foundation and are unchanged. Across all 29 governments and collected years, active workbook intake/display is Tax Revenue, Share from National Collections and Service and Business Income. This supersedes D46/D47's nine-field scope.

Delete Annex financial parsing, SCBAA sector/budget summation, workbook expense-component extraction, retired upload/UI branches and obsolete tests. Grants and Other Income remain internal source components needed to reconcile the workbook's printed Total Revenue, not new ledger series. Show the selected series as separate lines, not a complete stacked total.

Preserve originals, source registrations and individual/ZIP downloads regardless of extracted/verified coverage. Freeze retired observations and original workbook diagnostics with checksums; replay history, including Pending records, without reactivating intake or automatic publication. Existing human decisions stay intact.

Uncertainty belongs in Report Differences for human correction and independent review. Enrile's tax/national split is unresolved; its warning holds automatic sign-off, and Cash Flows cannot substitute for performance revenue. Accuracy takes priority over filling 29 cells. No new packages or schema changes.

Correction to D47's generalization: 2025 Santo Niño and Enrile Executive Summaries do print Budget Execution. Absence must be established per source; the workbook reduction does not alter the PDF parser or replace Executive Summary budget amounts with SCBAA.

## D49. Executive Summary Only and Parallel Tests

Approved "26-11-14" by the owner. Workbook (XLSX) reading is retired entirely. The Executive Summary is the only source of government finance figures: Financial Condition, Results of Operation, and Appropriations, Allotments and Obligations. This supersedes D46 and D48 and the Revenue Sources part of D36/M21.

Why: the workbooks vary too much between governments and years to parse reliably. The last staged run left 242 workbook findings in the Extraction Limits family (438 with the Executive Summary's), and some governments, such as Santo Niño, were flagged on almost every line. The Executive Summary states the same audited totals in one consistent layout.

What changes:

- The Python workbook reader, its audit and promotion commands and their tests are deleted. `coa.intake` reads only a PDF and refuses a workbook or archive by its content.
- `finances:import` loads only Executive Summary documents and figures. The `intake` column and workbook lines leave `lines.csv`; a migration removes workbook figures, documents, uploads and lines from existing databases.
- Capitol upload accepts only the Executive Summary PDF. Revenue Sources leaves Economy, Analytics, the guide, Data Entry and Report Differences.
- The removed rows are frozen with checksums in `data/towns/finances/history/26-11-14-workbook-reading-retirement/` (1,217 figures, 423 findings, 1,381 sign-offs, 516 document records). History is never read by the importer.

What stays: every original workbook, `financial-statements/index.csv`, the workbook collector and the public individual and ZIP downloads on About the Data. The Executive Summary parser modules and version 1.7.1 are unchanged.

New package: `pytest-xdist` 3.8.0 (with `execnet` 2.1.2), a development dependency of `tools/coa`, so parser tests run on every core. Pest runs in parallel with the `brianium/paratest` package Pest already installs. Measured on the owner's laptop: parser tests 115 seconds to 28 seconds.
