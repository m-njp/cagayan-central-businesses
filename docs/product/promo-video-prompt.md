# Kita Promo Video Master Prompt

Written 26-11-05 for the final presentation and online promotion of Kita (Cagayan Business), the PGC Digital Innovation Challenge entry. Paste everything below the line into the video agent or hand it to the editor. Fill the three placeholders in section 15 first.

---

## 1. Role and mission

You are the creative director, motion designer, editor and sound designer for one flagship product film.

Product: Kita, a map-based economic and business dashboard for the Provincial Government of Cagayan. It has two audiences. A public side with no login (explore your town, read government finances in COA's own words, browse registered businesses, tell the Capitol what business your area is missing) and a role-protected Capitol workspace for Super Admin, Admin and Staff. Built for the PGC Digital Innovation Challenge, Good Governance pillar, with Youth through employment and livelihood as one supporting sentence.

Core idea to land: the Capitol and its people finally see Cagayan's economy barangay by barangay, from one honest set of sources, and residents get a voice in it.

One line the film must earn: "One map of Cagayan's registered businesses, for the planners who decide and the people who want to start."

Feeling to create: the confidence and polish of an Apple keynote film, with the precision of a government data product and the warmth of a local initiative. Calm authority, then bursts of kinetic energy on the beat, then calm again.

Make the maximum showcase: purpose, every built feature, the badges, the engineering behind it (including the scripts and code), and the potential. Show real work, never mockups pretending to be real work.

## 2. Truth rules (never broken)

1. Show only what is built. Section 5 is the verified inventory. Anything listed as "not built" never appears as a working screen, button, chart or number. The potential section is text and abstract motion only, labeled Planned.
2. Every screen is a real recording of the running app, captured live at 60 fps: real clicks, real typing, real scrolling, real loading states. No static screenshot slideshow. No recreated or imitated interface.
3. Every number on screen comes from the live app or a stored source at recording time. Do not invent, round up or "improve" any figure. Where a figure is government data, keep its label (Income, Expenses, Net Income, Government Income) and its geography (Tuguegarao City only breakdowns are labeled as such; totals cover 29 towns).
4. Maps are real. Use the committed boundaries in `data/boundaries/` (`cagayan-municipalities.geojson`, `tuguegarao-barangays.geojson`) or the app's own map views. If a basemap appears it is real OpenStreetMap with attribution. A generated, redrawn or "approximate" map is forbidden.
5. Image generation, if used at all, must not create anything that claims to be real: no maps, no interface, no charts, no documents, no logos, no readable text, no invented people or faces, no fake storefronts presented as Cagayan businesses. Prefer real photography of Tuguegarao and Cagayan that the team shot or is licensed to use, credited on screen at the end. Abstract texture and grain are the only safe generated uses.
6. Official Kita artwork only: the SVGs in `public/brand/` (mirrors of `docs/brand/`). Never recolor, stretch, retype or redraw them. Use the reverse files on dark surfaces. Animating the mark's reveal is allowed; changing its shapes is not.
7. Privacy: no real owner names, contact numbers, TINs, credentials, `.env` values, API keys or session cookies on screen, ever. Business scenes use the seeded demo set. No single business's sales are ever shown, and none exist on any screen to show.
8. Simulated records stay labeled. Business directory, profile and registry scenes require `CAGAYAN_SIMULATED=true` on the recording machine, and the amber "Simulated data" tag (Capitol) or the in-content label (public) must be visible in frame. Curated wishes keep their red "Sample Data" badge. Say plainly in the film that the frame is real (boundaries, census population, COA finances) and business records and wishes are sample data.
9. Do not claim nothing like it exists. The claim is the gap: barangay level, by business type, year on year, run by the province, with citizen wishes. COA figures describe the local government, not private business revenue. Wishes are interest, not measured demand.
10. Do not show or promise: auditing, investment advice, prosperity scores, forecasts, rankings of towns as good or bad.
11. Local sign-in: use the local quick sign in for the three demo roles, or cut around the password. Never show a typed password.

## 3. Lessons from the two rejected reels (design against these)

The team already made reels on 26-11-05. The owner rejected the first for being a screenshot slideshow with boring music, and the second for slow pacing, arbitrary zooming and reused compositions. A third, a 72 second fast cut, followed. The standing rule from `AGENTS.md`: showcase reels need deliberate full-screen motion compositions, new campaign visuals, quick state-led edits and interaction feedback with sound.

So, this film must:
- Cut fast and on the beat. Short holds, only as long as a viewer needs to read a line. No dead air, no padding.
- Never zoom or drift the camera without a reason. Zoom only when it follows a real click target or a real map fly-in. No Ken Burns on screenshots.
- Never repeat a composition. Every beat gets its own full-screen motion layout. A heading over recycled footage twice is a failure.
- Tie sound to state: click, toggle, select, type, loading, success, transition. Every visible interaction has a matching SFX.
- Use deliberate effects only: each effect says something (data arriving, a place being chosen, a record being checked), not decoration.
- Reuse captures from `storage/app/showcase*` only as raw footage, never their layouts or edits. Prefer fresh recordings.

## 4. Visual identity (match the product exactly)

Source of truth: `docs/engineering/design.md` and `resources/css/app.css`. Convert the OKLCH tokens exactly; do not eyeball colors.

- Hero surface: river ink blue (`--brand`, oklch 0.36 0.07 245). The map is river ink in both light and dark appearance.
- Brass (`--highlight`, oklch 0.7 0.12 78) is the single accent: one chosen place, one focus, one number per frame. Never more than one brass element competing at a time.
- Value ramp for anything that means "more": `--map-1` to `--map-5`, one hue, light to deep. Sea dots use `--water`. No value uses `--map-none`.
- Paper and card neutrals: stone background (`--background` oklch 0.955), white cards. Dark scenes use the muted blue slate (`--background` oklch 0.23 0.022 245, `--card` oklch 0.3).
- Amber (`--simulated`) and red Sample Data only where those labels are required.
- Type: Geist for every word, Geist Mono for numbers, labels, years, codes and sources. Files are in `resources/fonts/`. Numbers always tabular. Labels uppercase tracked mono, small.
- Shape: nearly square. 2px corners on every card, button and window. Hairline borders separate things. Full circles only for dots, avatars and round icon buttons.
- No gradients anywhere, in the film or its graphics. Depth comes from flat layers, hairlines, scale and motion, not glows or blends.
- Icons: the Lucide set the app already uses (`resources/js/lib/icons.ts` is the semantic registry): map, search, building, shield-check, award, flame, pen, chart, table, help. Stroke style consistent, draw-on animated.
- Halftone is the film's core visual DNA. In the app, dots carry data, never decoration, every dot the same size, color depth carries value, all shapes share one grid (`DOT_STEP` 6 map units, `resources/js/lib/halftone.ts`). Carry that rule into the motion graphics: the grid is the canvas of the whole film.

Light and dark: open in dark river ink for the hook and the code scenes, move to light stone for the public product tour, return to dark for the Capitol and the close. Show the appearance toggle flipping once, live, to prove both themes.

## 5. Product inventory (verified 26-11-05 against routes, plans and tests)

Use the live app as the final authority. Before recording, open every screen listed under Built and confirm it works; drop what does not.

### Built and showable (public)

- Home. The halftone map of Cagayan: 29 towns, census population from PSA 2024, town and barangay explorer (820 barangays with population), a cached revisit that does not reload, one icon-only style control that flips dotted and solid. Economic map shading by Government Income for one fiscal year, with Latest, explicit years, Income, Expenses and Net Income. A wish cloud (word cloud, most wished in brand blue). Actions: Share Your Wish and Explore Your Town's Economy.
- Cagayan Economy page (`public.economic-performance`). Government Finances in COA's words. The Executive Summary table (Financial Condition, Results of Operation, Budget Execution as rows, fiscal years as columns, column lines, alternating rows, an arrow in each year cell against the year before). 3Y 5Y All chips, a Year select that opens the Yearly Summary, a Table or Charts toggle, a Help sheet. Hover on any short amount reveals the exact peso amount, the change and its percent. Net Income as Income minus Expenses, a loss in accounting parentheses with no color. Two charts: Results of Operation (Income and Expenses areas with a Net Income line) and Financial Condition (stacked areas). A Source line whose links open the stored COA PDF at the cited page (`finance-sources`). Town Explorer map. Community Wishes ranked list. All loaded figures are signed off; breakdowns beyond totals exist for Tuguegarao City only and the screen says so.
- Wishes (`public.wishes`). Business Type first, Specific Business revealed on request, optional reason up to 255 characters, cooldown and edit, counts update in the background. Nothing personal collected.
- Businesses directory and profile. Pick a town, its businesses load once and are cached, every other filter is local (Barangay, Category, BIR Registered, search). List and map share a panel; the map shows storefront dots, the chosen barangay outlined and tagged. Profile: About fields ruled like a table, Contact, More in the barangay, a tall Location map with the business as a pin, Achievements medals (Operating Milestone with the years on its face, Renewal Streak flame, BIR Registered shield), permit status badge (Expiring, Expired, No Permit on File). Simulated label in content.
- Public shell. Text navigation with filled active tab, appearance toggle, footer in brand ink with Explore, Sources (PSA, COA, OpenStreetMap) and Government columns, Digital Innovation Challenge 2026.

### Built and showable (Capitol workspace)

- Discreet sign in (Fortify). Three roles: Super Admin, Admin, Staff. Quick sign in for demo roles on the local machine only.
- Dashboard. The map-first workspace: economic map by Income, drill from town to barangay with client-side history (no reloads), geographic breadcrumbs, compact greeting with the Philippine 24-hour clock, fixed labeled sidebar.
- Population. Enter census counts with review; maps read the database.
- Data Entry hub. The finance report grid: a whole COA report in one grid, status vocabulary Pending, Verified, Returned, review queue, a verified figure locked behind a pen that asks for a reason, sign-off by someone other than the person who entered the figure.
- Finances. Upload a report PDF, review each figure against its source, sign off.
- Analytics. Executive Summary with a compact figure strip (Income, Expenses, Net Income, Assets for the newest reported year), Yearly Summary, charts, Help.
- Businesses registry. Directory with tabs All, For Review, Returned, Drafts, with counts. Add a Business form with a sticky Location card and a draggable map pin that selects the barangay, documents upload. Capitol record: About, Owner and Registration (TIN masked), Permits, Documents, Registration History, Review card where an Admin approves or returns with a required reason. Every full record view is logged.
- Business Categories. Categories and Types (each Type has exactly one Category), ten category colors, starter taxonomy, local sort and search.
- Community Wishes. Admin and Super Admin only, aggregate counts, never shown to Staff or on any dashboard.
- Activity Logs. Super Admin only, append only, safe event codes and actor keys.
- Profile. Private, optional birthday month and day, never year.
- Session security. 30 minutes idle, 8 hours absolute, two minute warning, background requests never extend idle time, history encrypted and cleared at sign out.

### Built and showable (engineering and process)

- Laravel 13 on PHP 8.3, Inertia 3, React 19 with TypeScript, Vite 8, Tailwind CSS 4, shadcn on Radix, Fortify, Wayfinder typed routes, PostgreSQL 17 with PostGIS 3.5 in Docker, Pest 4, MapLibre, visx.
- One request path: route, Form Request, controller, Query or Action, Data object, Inertia page, components. Pages never get models. Thin controllers.
- COA PDF parser in Python (`tools/coa/`): two PDF engines with fallback and digit confirmation, crash isolation and a run log, 201 Tuguegarao cells tested, 77 parser tests.
- Finance ledger with sign-off, metrics defined in `docs/engineering/metrics.md` and computed in SQL, `metrics:refresh` rebuilding summary tables.
- Privacy by design: the privacy guard, privacy tests (`tests/Feature/Privacy/`), a `privacy-reviewer` agent, `/privacy-check`.
- Quality gates: git hooks run fast checks on commit and the full `ci:check` plus a build on push. `composer ci:check` runs front end format and lint, TypeScript, Pint, PHPStan, frontend tests and Pest on PostGIS. Last recorded counts: 306 PHP tests passed; re-run and use today's counts.
- Team workflow: `main` is production, `staging` is development, work branches merge back only through pull requests; skills such as `/ui-builder`, `/design-check`, `/privacy-check`, `/demo-check`, `/plan-history`; a plan register in `docs/plan/` with dated change history.

### Not built, never shown as working

Outlook and backtest, Early warning, Survival rate, Dominance, Compare, Peer comparison, Rankings and movers, business-level metrics (growth, density, declared sales), PDF brief and CSV export, the public business sales-band map, the Start a Business guide, Local snapshot, account management and role-change screens, other towns' breakdowns, deployment. These may appear only on the Planned card in the potential beat.

## 6. Writing rules (the film's words)

- No em dashes, en dashes, middots or pipes in any on-screen text, caption, lower third, file name card or end card. Use periods, commas, colons and line breaks.
- Titles and headings are one name with no separators. Title Case for titles, headings, buttons, tabs and badges. Sentence case for sentences. Proper nouns right: Cagayan, Tuguegarao City, Capitol, COA, PSA, PSGC, PostGIS.
- Short. One idea per frame. Concrete nouns and verbs. No hype adjectives (revolutionary, game-changing, seamless, powerful, cutting-edge). Let the product be the proof.
- Use COA's words for finance: Income, Expenses, Net Income, Government Income on map legends. Never Revenue, Surplus or Deficit, and never call it business sales.
- Say "registered businesses", "sample data", "simulated data" exactly as the app does. Numbers in Geist Mono, thousands separators, peso sign as the app writes it.
- Voiceover is optional; if used, calm, warm, local, unhurried, native Filipino English accent, no announcer voice. Every spoken line also exists as a burned-in caption. The film must work completely on mute.
- Optional wordplay: "kita" means income or earnings in Filipino. Confirm with the team before using it in any line.

### Copy bank (adapt, do not pad)

Hook: "Where does business live in Cagayan?" / "Barangay by barangay." / "One map."
Problem, three beats: "Planners decide without barangay evidence." / "People start businesses blind." / "Residents have no way to say what is missing."
Reveal: "Meet Kita." / "One map of Cagayan's registered businesses, for the planners who decide and the people who want to start."
Public: "Explore your town." / "29 towns. 820 barangays." / "Population from the 2024 census, shaded dot by dot." / "Share a wish."
Finance: "Government Finances, as COA printed them." / "Income. Expenses. Net Income." / "Every figure links to its page in the audit report." / "Checked by a second person before it is published."
Businesses: "Find who is open near you." / "Registered, permitted, and counted."
Capitol: "Staff prepare. Admins review. The public sees what is verified." / "Three roles. Every action authorized on the server." / "Every full record view is logged."
Trust: "No single business's sales ever leave the server." / "Owner details stay behind login." / "Wishes are interest, not proof of demand."
Engineering: "Built on open standards." / "Tested on PostGIS, never a shortcut."
Potential: "Next: more towns, one at a time." / "City and municipal offices with their own views." / "Real records under a data sharing agreement." / "Ask the map in Filipino or Ilocano."
Close: "Kita" / "Cagayan Business" / "PGC Digital Innovation Challenge 2026"

## 7. Motion language

One system, used consistently. The grid is always the stage. Name each device and use it for a consistent meaning.

- **Dot grid (the canvas).** A uniform grid of equal dots at the app's pitch. It idles as a faint field. It wakes in a ripple from the point of interest, brightening the dots that carry value.
- **Place from dots.** Dots migrate and settle into real geometry: first the Cagayan province from the municipality GeoJSON, then Tuguegarao City, then its 49 barangays. Borders draw in as thin lines in the parent color at 45 percent. Sea dots in `--water` arrive last, only along the coast. Always the real shapes.
- **Squares.** Nearly square 2px cards and windows slide, snap and tile like a modular grid. A tile grid may dissolve into the dot grid and back. Squares mean "records, documents, units of work".
- **Waves.** Rows of dots displaced by a slow sine, one phase per row, to mean time, fiscal years and change. A wave settles flat when a value is confirmed. Use for transitions into finance and for the soundtrack's builds.
- **Flows.** Lines of dots travel along real boundaries or between points: data flowing from towns to the Capitol, a figure traveling from PDF page to table cell to chart. Dot trails, never glowing strokes. Flow direction is always source to answer.
- **Text duplication.** A headline is stamped three to five times with offset outlined echoes that peel away on the beat, then collapse to one solid line. Used for hooks and section names only, never for body lines.
- **Text spread.** Letters scatter to individual dots in the grid and reassemble into the next word, with the signed-distance glyph morph the earlier cut proved out. Number counters roll up in Geist Mono and lock with a short tick.
- **Type on a rail.** Key words slide on horizontal rails across the full frame at different speeds, the sentence readable only at the moment they align.
- **Wipe by data.** A scene change reveals the next scene through the map's own shapes (a barangay silhouette, a bar, a chart area) instead of generic wipes.
- **Draw-on.** Icons, underlines, chart lines and rings draw on with an ease-out; hairline rings mark selections and the focus caret blinks while typing.
- **Badge stamps.** Badges land with a quick press and a short settle, never a bounce loop. The medals rotate once to catch light by scale only, not by gradient.

Easing: one soft ease-out for everything, like the app's `--ease-out-soft`. Entrances rise; map fades; no spring wobble. Quick state changes around 150 ms, base 250 ms, entrances up to 600 ms. Cuts land exactly on beats.

Every effect must sit on a state: a selection, a load, a check, a confirmation, a handoff. Decoration for its own sake is cut.

## 8. Capture rules

- Resolution: record at 2560 by 1440 or higher, deliver 1920 by 1080 and a 4K master if possible, 60 fps constant.
- Use a clean browser profile, no bookmarks bar, no extensions, no notification banners, page zoom 100 percent, device scale 2. One window, one tab per take.
- Start from `composer db:reset` for a known state, with `composer dev` running on port 8000 (one server only). For business scenes, set `CAGAYAN_SIMULATED=true`. Run `npm run build` first so assets are final and fast.
- Cursor: a clean custom cursor, large enough to follow, with a soft click ripple ring and a press scale. Move deliberately along curved paths, no jitter, no hunting.
- Scroll is live, real, smooth, eased: use real wheel scrolling, not a scripted jump. Let the sticky columns and lazy-loaded charts do their real thing.
- Capture every real state, including skeleton and loading, the empty state of a filter, the hover tooltip with the exact amount, the focus ring. These sell craft.
- Type for real at a natural cadence, with a believable correction once. Do not submit anything that writes to real government data; a wish may be drafted and shown as a draft, or submitted only on the local development database with the Sample Data label visible.
- Record light and dark of the same scenes where it adds value, and one live toggle between them.
- Record a second pass at phone width (375 px) for the vertical cut: Home map, wish form, business list.
- Browser chrome: crop it out, or show a clean address bar with the local or deployed domain only. Never show a token or session in a URL.

## 9. Story structure (beats, in order, with the job of each)

Each beat gets its own composition. Pace fast. Time follows the beat grid, not a stopwatch.

1. **Cold open hook.** Ink black. The dot grid wakes from a single brass dot. The dots flow into the real shape of Cagayan. The question appears with text duplication and settles: "Where does business live in Cagayan?" A heartbeat of low sound, then a downbeat as the dots shade by population.
2. **The gap.** Three fast kinetic statements on rails, each tied to a different real source image or UI fragment: planners (a blank map), someone starting a business (a long requirements list from the city's checklist), a resident (an empty wish box). Cut away with a wipe by data.
3. **The reveal.** The official Kita mark builds from the dots, its magnifier closes on Tuguegarao. The line "One map of Cagayan's registered businesses, for the planners who decide and the people who want to start." resolves. Beneath it, one small mono line: PGC Digital Innovation Challenge 2026, Good Governance.
4. **Explore your town (public).** Live Home. The map fills the screen. Click a town, the map frames it, barangays appear, population counters roll. Flip dotted to solid and back with the real style control. Revisit a town to show it loads instantly. Dark and light toggle. Beat text: "29 towns. 820 barangays."
5. **Government Finances.** Scroll the live Economy page. The Executive Summary table builds by flow from a PDF page into cells. Hover an amount: the exact peso amount, change and percent appear. Switch 3Y, 5Y, All and open a Year with local history, zero loading. Charts draw on. Click a Source link and the stored COA PDF opens at the cited page, with the figure highlighted. Beat text: "As COA printed it."
6. **Voices of the town.** The wish cloud assembles; hover shows town and counts. Open the wish form, pick a business type, type a short reason (draft), show the live count. Cut to the Community Wishes ranked list. Keep the Sample Data badge visible. Beat text: "Share a wish."
7. **Businesses, found and counted.** Public directory: choose a town, list and map load once, filter by Barangay and Category locally, pick a business: the pin lands, the profile builds, the badges stamp (BIR Registered shield, permit status, Operating Milestone, Renewal Streak). The simulated label stays in content.
8. **Inside the Capitol.** Sign in with the demo role. The sidebar and greeting settle. The map-first dashboard drills from town to barangay with breadcrumbs, zero reloads. Then three fast work scenes, each its own layout: Data Entry (a whole COA report in one grid, a verified figure locked behind its pen, the reason prompt), a business record (Review card, approve or return with a reason, TIN masked), Business Categories (a Type moves under a Category, colors apply). End on Activity Logs, append only, and the roles.
9. **Trust by design.** Animated rule cards, one at a time, each shown with the real thing that enforces it: "No single business's sales ever leave the server." "Owner details stay behind login." "Every full record is logged." "A second person signs off every figure." "Session ends after 30 idle minutes." Back each with a flash of the real code, test file or UI state.
10. **Under the hood.** The code showcase (section 10).
11. **Badges and marks.** The motif that pays off the earlier badge stamps: a wall of product badges (BIR Registered, Valid and Expiring permits, Operating Milestone, Renewal Streak, Sample Data, Simulated data, Verified, Pending, Returned) assembling in a grid, then a second row of engineering badges (Laravel, Inertia, React, PostGIS, Pest, CI green with today's test count). Each badge is the real component or a faithful typographic card with the app's tokens, never a fake third-party logo set. Add earned recognition only if the team has real ones, with names filled in section 15.
12. **Potential.** Pull back. The dot grid zooms out from Tuguegarao to the whole province; greyed towns light one by one as dots, labeled Planned. Four Planned lines in mono. No UI, no fake numbers.
13. **Close.** The grid collapses into the logo. Kita, Cagayan Business, the initiative line, the team and adviser credits, the real source credits (PSA, COA, OpenStreetMap, photography). Hold on the brass dot, then silence on the last beat.

## 10. The code and scripts showcase

Goal: let engineers and judges see rigor, in a form anyone can follow. Render windows that look like a real editor (a VS Code style frame: flat, 2px corners, activity bar, tab, breadcrumb path, line numbers) but in the film's palette, Geist Mono, no gradients. Actual file contents only, redacting any secret.

- **Typing and reveal.** Code types on at a lively pace, then a highlight bar sweeps the key lines, then the line is called out by a mono label on a rail. Fold away the rest with a smooth collapse.
- **Show these real files** (read them first, show only real excerpts):
  - `routes/` capitol group with `auth` and `can:capitol` (one group, named `public.*` and `capitol.*`).
  - A Query in `app/Queries/` (for example `WorkspaceMapQuery.php`) returning a readonly Data object from `app/Data/`.
  - `config/cagayan.php` thresholds and input limits, to prove "thresholds live in config".
  - `resources/js/lib/halftone.ts`: `DOT_STEP`, `DOT_RADIUS`, `DOT_FILL`, tying the code to the dot grid the film uses.
  - `tools/coa/run.py` and a parser excerpt, with the PDF page on the left and the parsed figure on the right.
  - A Pest test from `tests/Feature/Privacy/` or a finance sign-off test.
  - `composer.json` scripts block.
- **Animate the request path as a diagram.** Boxes slide in on the grid: Route, Form Request, Controller, Query or Action, Data object, Inertia page, Components. A single dot travels the path. Use words and arrows only, no pipes. Label "Pages never get models."
- **Terminal scenes** (PowerShell, real commands, real output, trimmed to the relevant lines):
  - `composer setup` (first-time setup, safe to rerun), with the doctor step at the end.
  - `composer doctor`, showing each check pass.
  - `composer dev`, the app up at http://localhost:8000 (server, queue, Vite) and the guard refusing a second server.
  - `composer db:up` and `composer db:reset`.
  - `composer data:finances:extract` then `composer data:finances` and `composer data:finances:test`, the COA parser running and its tests passing.
  - `composer data:towns`, `php artisan metrics:refresh`.
  - `composer ci:check` running end to end and finishing green with the real totals. If it is not green on the day, fix it or do not show it.
  - A `git commit` firing the hooks, and a `git push` running the full check and build. Never show `--no-verify`.
- **Skills and agents.** One quick montage card per skill (`/ui-builder`, `/design-check`, `/privacy-check`, `/demo-check`, `/plan-history`) and the read-only `privacy-reviewer`, showing the real findings format. This signals disciplined, reviewable work.
- **Plan history.** Scroll `docs/plan/README.md` with its dated records, to show deliberate, documented decisions.
- Code scenes use dark river ink. Keep each under a few seconds of reading, and never let a scene be a wall of text.

## 11. Transitions and edit grammar

- Every cut lands on a beat of the soundtrack. Use hard cuts for rhythm and one of the motion-language transitions for chapter changes. No default crossfades, no cross zooms, no spin or page-curl presets.
- Approved transitions: dot-grid ripple, wipe by data (a real boundary or chart shape as the matte), tile snap (squares reassemble into the next layout), wave sweep (a displaced dot wave carries the next scene in), flow handoff (a dot trail carries the eye from one screen element to the next scene's anchor), text-spread morph (the last word becomes the next scene's first element).
- Match cuts: end a scene on a shape or a gesture that begins the next (a click on a barangay becomes the barangay silhouette that wipes in the next scene).
- Eyeline continuity: the focus of each frame sits where the previous one ended.
- Consistent safe margins, one title zone, one number zone, one source zone (small mono line at the lower edge). Layouts repeat as templates but never as the same composition twice in a row.
- Callouts: thin rings, small mono labels on rails, and brass focus marks. Never cartoon arrows or stock sticker animations.
- Lower thirds: Geist, Title Case, one short line, tag line in Geist Mono. Always burned-in captions in sentence case, large and high contrast.

## 12. Sound

- Original or properly licensed instrumental only, with proof of license listed in the credits. No copyrighted tracks.
- Direction: modern, confident, minimal electronic with warmth: a soft pulse, pluck and mallet motifs, a clean kick and clap, short filtered builds, no cheesy corporate pads, no cinematic trailer boom. Energy rises through the film and breathes at the code beat and the close.
- Tempo around 120 to 140 BPM for the product tour, with a half-time breathe for the hook and the close. Every cut and every effect snaps to the grid.
- SFX tied to state: pointer tick on click, soft toggle on switch, keystroke ticks while typing, selection ring pulse, loading shimmer, success chime on check, stamp for badges, paper whisper for PDF open, rising dot hum for the grid wake, flow whoosh for handoffs. Keep the mix clean and under clipping.
- Leave a short silence before the final logo lands.
- Mix for phone speakers: clear mids, controlled low end. Loudness around minus 14 LUFS for social, minus 16 LUFS for the stage.

## 13. Deliverables

- **Flagship cut.** About 2 to 3 minutes, 16 by 9, 60 fps, the whole story above.
- **Hero cut.** About 60 to 90 seconds for the stage and social, beats 1, 3, 4, 5, 8, 9, 12, 13.
- **Teaser.** About 15 seconds, hook, reveal, one map interaction, the logo.
- **Vertical cut.** 9 by 16, about 30 to 45 seconds, rebuilt from phone-width captures, never a crop of the wide frame.
- **Captions.** Burned-in plus a separate SRT file in English.
- **Stills.** A poster frame, a social thumbnail, and 6 key frames, all from the film's own compositions.
- **Handoff.** The editable project, an asset manifest (every clip, font, SVG, audio file with its source and license), a text file of every on-screen line, and a verification note (frame rate, duration, audio peak, decoded spot checks).
- Name the master `kita-promo-flagship`. Keep render sources out of the repository, in the ignored storage folder, as before.

## 14. Final quality checklist (fail the cut if any is false)

1. Every screen is live footage of the working app; nothing recreated.
2. Every map is the real geometry or the app's own map.
3. Every number is verifiable against the app on the recording day.
4. No unbuilt feature shown as working. The Planned card is text only.
5. No em dashes, en dashes, middots or pipes in any on-screen text or caption. No gradients anywhere.
6. Official Kita artwork unmodified; Geist and Geist Mono throughout; token colors only; 2px corners.
7. Simulated data and Sample Data labels visible wherever those records appear.
8. No secrets, credentials, real personal data or single-business sales visible.
9. No composition repeated, no arbitrary zoom, no slideshow of stills.
10. Every interaction has a matching sound; every cut lands on a beat; the film is complete on mute.
11. Light and dark both shown; phone width shown; the code and terminal scenes are real and green.
12. Credits complete: team, adviser, sources, photography, music license.

## 15. Fill these in before starting

- Team member names and roles: ______ (from the submission list)
- Adviser name: ______
- Deployed or local URL to display, or "localhost" only: ______
- Any real recognition or awards to include in the badges beat: ______ (leave blank to skip)
- Photographs of Tuguegarao or Cagayan the team owns or has licensed: ______
