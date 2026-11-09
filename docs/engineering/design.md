# Design System

How screens look, read, and behave. Read this before building or changing any page, and start the work with `/ui-builder`. For code structure, see `frontend.md`.

The short version: **calm, exact, and readable by any citizen.** Sans for words, mono for numbers. Square corners. Dots for data. One hue for values, brass for a choice. Nothing decorative.

## Principles

1. **Start from the user's question.** A planner asks "where are businesses closing?" A resident asks "how many people live in my town?" Every screen answers one question, and the answer is the first thing on it.
2. **Exact over pretty.** Clear numbers, plain labels, obvious next steps. Every group of figures names its year and its source once, and the source is a link to the document.
3. **Fast to read.** Short labels, aligned numbers, one idea per block.
4. **Honest.** Simulated data is off by default and says so wherever it is shown. Hidden figures say why. A missing figure reads "Not available", never `0`.
5. **Government grade.** The page must feel trustworthy to a mayor and usable by a first-time phone user. Calm type, high contrast, no tricks.
6. **Two audiences, two densities.** Capitol screens are dense and built for laptops. Public screens are simple and built for phones first.

## Working on Existing Screens

These rules record the user's corrections from the public map and wish form work. Apply them before proposing or implementing another UI change. The latest explicit correction takes precedence over earlier examples or historical decisions.

### Preserve the Requested Structure

- Read the shared project instructions, the relevant skill, and the area docs before editing. Inspect the current page and its shared components first. [Shared project instructions](../../AGENTS.md).
- Make the requested change within the existing layout. Moving information from a modal into a panel does not authorize a panel redesign, additional measures, a legend, or new instructions.
- Keep related controls and content in one coherent surface. The wish form and its map share one card; the map must not sit on an unrelated blank canvas.
- Fit additions within the existing section. About the Data must not grow into or overlap its neighboring content. Keep long option lists scrollable through the shared Select primitive.
- Use the available map area deliberately. Avoid large empty padding below the map. Keep the Cagayan islands in view when changing framing or scale.
- Reuse shared components and tokens. One shared map style preference and one icon control serve both Home maps; do not repeat equivalent controls in each section.

### Remove Dangling and Repeated Copy

Every visible sentence must add information needed to understand the data, complete an action, or recover from an error. A sentence that restates a heading, field, placeholder, or button should be removed.

- Do not place descriptions below page titles. Preserve field limits, validation errors, and recovery feedback where they are needed to complete the task.
- Do not add isolated statistics, badges, captions, instructions, or marketing lines merely to fill space. The removed town/dashboard statistics, "Open Now", suggestion totals, and numbered form walkthrough must stay removed unless explicitly requested again.
- Do not explain obvious controls with "Click a town to pick it", double-click instructions, or "Hold Ctrl" copy. Keep keyboard support in the interaction itself and documentation.
- Do not add repeated account/privacy assurances beneath the form or headings. Privacy definitions belong in the documented policy; the user removed the Home protection section on 26-10-25. Do not recreate that section or scatter its text across cards.
- Do not append company examples as helper paragraphs. A concise placeholder is enough. Keep the removed "Have a specific business in mind?" and "Wish for a favorite like..." descriptions out of the interface.
- Prefer one concise action, such as the Specify a Business reveal control, to a paragraph explaining the field.
- Use an underline for text links. Do not append decorative arrows.
- Use formal, factual language for About the Data, source attribution, privacy explanations, and the footer. Do not invent legal assurances, affiliations, seals, or guarantees.

### Keep the Approved Public Map Scope

- Home's details panel shows Population, Revenue, and Expenditure with their relevant years and sources. Do not reintroduce barangay count, income class, poverty incidence, or unrelated source links.
- Show province population context once as "Cagayan Province (as of 2024)". Do not add another detached population-year caption in About the Data.
- The main panel uses "Source" on its own line, followed by one source link per line. The compact barangay explorer uses one inline Source row with the PSA link.
- Use the supplied [PSA 2024 release](https://psa.gov.ph/statistics/population-and-housing/node/1684077888) for population links. Boundary provenance belongs in the data READMEs; a population workbook does not supply boundary shapes.
- Geographic scope is Cagayan Province: 28 municipalities and Tuguegarao City. Batanes is outside this scope.
- About the Data selects a town and displays its barangay boundaries and populations. Do not restore the extra barangay dropdown, duplicated style switch, population legend, or helper paragraphs.
- A single click or keyboard activation selects a place. The details stay in the panel; do not restore More Details, its modal, or Suggest a Business Here on the map.
- Keep Geist for readable words and Geist Mono for numbers and compact data labels. Dark surfaces use muted blue slate that complements the map's blue, with readable foregrounds and field borders. Avoid a near-black base or an unrelated neutral charcoal palette.

### Build Forms Around the Task

- Town is required. Business Type is the default visible choice. Reveal Specific Business only when the user activates "Not on the List? Specify a Business", or when editing a wish that already contains a company name.
- Accept a business type, a specific business, or both. Do not silently make both required or make the reveal control submit the form.
- Label the reason as a direct question about why the business is wished for in the area. Enforce the 255-character limit in both the input and server validation.
- Use "Send Your Wishes" for a new submission and retain a clear back icon on the separate page.
- Selecting a town in the dropdown or map must preserve entered text, focus where practical, and scroll position. Do not request the entire form payload to update a count.
- Update only the count in the background when the selection supports it. Skip unchanged selections, reuse cached counts, cancel superseded requests, and handle failures without replacing the form with a loading state.

### Show Parsing Progress as a Status Overlay

Owner corrections, "26-11-08", after two rejected versions of the finance upload progress view.

- A full-page Gaussian blur (`bg-background/60 backdrop-blur-md`) over the page that stays visible, with bare content centered on it. No card, no scrollbar.
- Geist Mono throughout. One status line, a thin scan bar and a fixed seven-line log: time (24-hour Philippine), source location, line label, exact amount, then an icon. The newest line spins; finished lines draw an animated check.
- Print what happened in plain words and figures, not raw event text. Show no all-caps headings, hint text, "Discovered Figures" counts, badges, results tables or expandable history.
- Prefer deleting code to adding it. Server events carry facts; the browser formats them.

### Focus Overlay

Owner direction, "26-11-08" and "26-11-09". One reusable component, `components/ui/focus-dialog.tsx`, serves the parsing progress and the Home map view. Do not rebuild the blur by hand.

- The page stays visible under a Gaussian blur that fades in slowly and out quickly (reduced motion skips it). Content sits bare in the middle: no card, border or shadow. Children bring their own title (`sr-only` is fine).
- The Home view opens from one expand icon in the map controls (not a click on the map, which already selects a municipality). It is a full-screen comparison view, not a details dialog. Four bare areas on a 2 by 2 grid with no visible grid lines: top left the map, top right the area's figures, bottom left the Income, Expenses and Net Income trend, bottom right the same figures as a table with years as columns.
- With a municipality chosen, the map shows that municipality alone, drawn from its barangay file with its barangay boundaries and names on hover, and no figure per barangay (one shade, the municipality's). With none chosen it shows the province. It uses the same URL selection and cached data as the hero, so opening it requests nothing new, and it has no second style or year control.
- Home uses the current appearance for the blur tint, figures, charts, table, and controls. Keep the underlying page recognizable through the Gaussian blur, with a lighter tint in light mode. Preserve the hero ink treatment within the map.
- Previous and next chevrons sit at opposite viewport edges with a 44 px hit area. On phones they sit at the top edges with Close centered and content scrolling below. They step through the province and each municipality in a loop through the same URL selection.
- Close with a 44 px icon button or Escape; focus returns to the expand button. Below `md` it is one scrolling column.
- Where else it could help, ranked, not built: 1) the wish cloud and ranking, for more real labels at full size; 2) finance charts on Economy and Capitol Analytics (one shared `AnalyticsPanels`); 3) the Capitol Dashboard map, preferring MapLibre's own fullscreen control over a second WebGL instance. Do not use it for tables, entry forms, Population, Data Entry, Businesses, the finance review dialog or upload.

### Compact Controls

Use the shared input and select primitives. Control height, compact height, and touch targets use the `control`, `control-compact`, and `touch` spacing tokens. Field typography uses `type-control`. Home and public Businesses use quiet select triggers with the current value and a chevron. Fiscal Year displays the resolved year, such as `2025`. The menu retains `Latest` and explicit fiscal years. On the province map, reporting coverage sits with the source when shared across the figures.

Compact toolbars use `SearchField`. Show a magnifier at rest, expand and focus the field on activation, and keep an active query visible with a clear action. An empty field collapses when focus leaves or Escape is pressed. In public Businesses, put the magnifier beside the town picker and put Barangay and Category on one row at phone widths. Preserve page-specific filtering and history. Map zoom, reset, and expand use unobtrusive icons with 44 px touch targets.

Use the same Radix Select for value selection across public and Capitol screens. Filters use quiet triggers. Form fields use a subtle border and share the same chevron, focus ring, menu rows and selection checkmark. Triggers and option rows have 44 px touch targets below the desktop breakpoint. Long menus scroll within the available viewport. Group headings identify option sets and stay out of the selection order.

### Tabs and Municipality

- One tab row for every filter tab: `components/capitol/tab-row.tsx`, the Businesses look (filled pill for the active tab, mono labels for years, no underline). Government Finances years, Businesses review states and Population census years all use it inside a `Surface padded`.
- In the Capitol workspace, the word is Municipality in every label, header and placeholder. Never Town, City, "Town or City" or both. Code names and routes keep `town`.
- Entered figures show one status cell (`FigureStatus`: dot and word, "by you", the Returned note inline) and one entry cell (`EntryCell`, compact `h-7`). Times in Data Entry read `MM-DD-YY HH:mm` in Philippine time through `formatEntryTimestamp`.
- Population is the table on the left and the municipality's barangay map on the right. The map is the shared `ProvinceMap` in its solid style, as on the Dashboard, shaded by the barangay file. Choosing a barangay on the map selects its row and the reverse. All rows share one font size; the municipality total is only medium weight.

### Validate the Result and Leave a Clear Handoff

- Review the diff against the actual request and the removals above. A deleted feature is not permission to recreate it under a different label.
- Check desktop and phone layouts, both appearances, keyboard operation, source links, and section spacing. Check that map and dropdown selections agree and that drafts survive changes.
- For frontend changes, run format/lint, TypeScript, and the production build. Lint alone does not catch an undefined helper such as the earlier `formatCount` SSR failure. Run affected regression tests for behavior changes and the full required checks before pushing.
- After rebuilding while the development preview is open, refresh the preview before the final interaction check so an old Inertia asset version is not mistaken for a new application defect.
- Delete code only after tracing imports, lazy imports, page resolution, routes, and remaining callers. Remove redundant imports and props with the obsolete component. Preserve source datasets still used by importers or tests.
- Keep commits focused by concern. Record the final behavior and any remaining limitation in the docs and PR; do not leave decisions only in chat.

## Tokens

All tokens are CSS variables in `resources/css/app.css`, exposed as Tailwind classes through its `@theme` block. Components use the classes, never raw values. To change the look, change a token, never a page.

### Color

| Token | Classes | Use |
| :- | :- | :- |
| `--background`, `--foreground` | `bg-background`, `text-foreground` | The page and its text |
| `--card`, `--muted`, `--accent`, `--border` | `bg-card`, `bg-muted`, `bg-accent`, `border` | Surfaces, quiet blocks, hover, hairlines |
| `--muted-foreground` | `text-muted-foreground` | Labels, years, notes |
| `--primary` | `bg-primary`, `text-primary` | The one primary button per area, links in focus |
| `--brand` | `bg-brand` | River ink surface: the Home hero and the login panel. Dark in both modes |
| `--highlight` | `fill-highlight`, `bg-highlight` | Brass. A chosen or connected place, the current nav item. Markers only |
| `--map-1` to `--map-5` | `fill-map-*`, `bg-map-*` | Value scale, low to high, one hue. Every step stays visible as a dot on the background |
| `--map-none` | `fill-map-none` | No value: "Not available" |
| `--water` | `fill-water` | Sea dots around the province map. Geography, so always quieter than the map scale |
| `--status-*` | `*-status-new`, `*-status-renewed`, `*-status-not-renewed`, `*-status-closed` | Business status |
| `--chart-1` to `--chart-5` | `*-chart-*` | Other categories in charts |
| `--category-1` to `--category-10` | `bg-category-*`, `bg-category-*/15` | Business Category colors beside the category name. The one approved non-data hue (D38, D39) |

Hue is kept for data. Only the map scale, statuses, and charts carry color. A colored thing always means something, and color is never the only signal (pair it with a label or dot size).

Dark mode uses muted blue slate surfaces in the same hue family as the river ink map. The base stays lighter than the original near-black theme. Cards, popovers, and hover surfaces step progressively lighter, with visible field borders and light text. The map keeps its river ink blue surface in both appearances.

### Type

Two families, both open-license and self-hosted from `resources/fonts/` (works offline at the booth):

- **Geist** (`font-sans`): every word people read. Titles, headings, body, buttons.
- **Geist Mono** (`font-mono`): numbers, labels, codes, legends, years, sources, the top line. It says "this is data".

| Role | Class | Font and size | Example |
| :- | :- | :- | :- |
| Display | `type-display` | Sans semibold, 4xl to 6xl | See How Cagayan Lives and Works |
| Page title | `type-title` | Sans semibold, 3xl to 4xl | Suggest a Business |
| Section heading | `type-heading` | Sans semibold, xl to 2xl | How Businesses Are Protected |
| Subtitle | `type-subtitle` | Sans, base to lg, muted | Context only when needed |
| Figure | `type-figure` | Mono medium, 3xl to 4xl, `tabular-nums` | 1,284,676 |
| Label | `type-label` | Mono medium, xs, uppercase, tracked | POPULATION |
| Lead | `type-lead` | Sans, base | Protection points |
| Item | `type-item` | Sans medium, base | Business Map |
| Body | `type-body` | Sans, sm | Descriptions, controls |
| Meta | `type-meta` | Sans, sm, muted | Footer, notes |
| Pin | `type-pin` | Sans medium, xs | 38 |
| Data | `type-data` | Sans or mono, 12px | Table notes, changes |

Use the roles, never raw size and weight classes. They are named `type-*` so `cn()` never mistakes them for a text color.

### Space

| Token | Classes | Use |
| :- | :- | :- |
| `--spacing-section` (3rem) | `gap-section`, `pb-section` | Between page sections |
| `--spacing-gutter` (1rem) | `px-gutter` | Page edge on phones. `md:px-6` from `md` |

Inside a section: `gap-3` for a row of controls, `gap-4` for cards in a group, `gap-6` between a heading and its content.

### Shape

- `--radius` is **2px**. Every `rounded-*` step resolves to 2px (`rounded-xs` is 1px). Controls, cards, sheets, and surfaces all look nearly square.
- `rounded-full` only for dots, avatars, and round icon buttons.
- Hairlines (`border`, `divide-y`) separate things. Shadows stay as shadcn ships them.

### Motion

| Token | Class | Use |
| :- | :- | :- |
| `--ease-out-soft` | `ease-out-soft` | The one easing |
| `--duration-quick` (150ms) | `duration-(--duration-quick)` | Hover color |
| `--duration-base` (250ms) | `duration-(--duration-base)` | Dim, lift, fade |
| `--duration-slow` (600ms) | `duration-(--duration-slow)` | Entrances |
| `animate-rise`, `animate-fade`, `animate-nudge`, `reveal` | | Entrance, map fade, the scroll cue, sections rising into view |

Everything that moves sits behind `motion-safe:` or `motion-reduce:transition-none`.

### Halftone

The default map treatment is taken from print. **Dots carry data, never decoration.** A solid-fill view uses the same boundaries, population shades, selection color, tooltips, and loading sequence. One icon-only toggle beside the main town selector controls both Home maps; the preference is shared and remembered in the browser. Each icon has an accessible name and a title.

- Constants live in `resources/js/lib/halftone.ts`: `DOT_STEP` (6 map units between dots), `DOT_RADIUS` (one size for every dot), `DOT_FILL` (the `fill-map-*` class per shade, index 0 is "no value").
- **Every dot is the same size. Color depth carries the value:** a deeper shade means more. Size never changes, so small towns stay as visible as big ones.
- All shapes share one grid, so dots line up across borders like a printed page.
- **Borders always show** as a thin line in the parent's color (45 percent), so every town and barangay reads as its own place. Hover or a choice makes the line full strength.
- **Water** (`water` on `ProvinceMap`): smaller `fill-water` dots on the same grid, only where the coast meets the sea (north of the mainland and around the islands, east of the northeast coast), in a band a few dots wide. Neighboring provinces stay empty. Drawn as one path by `seaPath()`.
- Dots are SVG circles. Never a CSS `radial-gradient` (gradients are banned) and never a dotted background pattern.

## Writing

Every word in the UI: titles, headings, labels, buttons, toasts, empty states, exports.

### Capitalization

- **Title Case** for page titles, section headings, card titles, buttons, tabs, menu and nav items, badges, form field labels, legend titles, and table headers: "How Businesses Are Protected", "Suggest a Business", "Open Suggestions".
  - Small words stay lower case unless first: a, an, and, as, at, but, by, for, in, of, on, or, the, to.
- **Sentence case** for anything that is a sentence: subtitles, descriptions, body text, toasts, errors, empty states, and help text.
- **Proper nouns and acronyms are always right:** Cagayan, Tuguegarao City, Peñablanca, Capitol, PSA, PSGC, DTI, BIR, SEC, CDA, LGU.
- `type-label` renders in uppercase through CSS. Write its text in Title Case anyway, so screen readers and copies read it right.
- The fixed phrases in `privacy-rules.md`, "Words on screen", are used word for word. They are sentences ("Not available", "Hidden to protect individual businesses").

### Rules

- **No em dashes or en dashes** (— or –). Use a period, or rewrite.
- **No middots** (·).
- **No separators in titles and headings.** Not commas, dashes, middots, or pipes. A title is one name. Show context with a `type-label` line above it or a subtitle under it.
- **Plain words.** "Did not renew", not "Non-compliant". "Below peer average", never "underperforming".
- **Buttons say what happens.** "Export CSV", "Suggest a Business", never "OK" or "Click here".
- **No exclamation marks and no emoji.**
- **Numbers:** thousands separators (12,450). Percent with one decimal (9.1%). Changes signed (+3.4%). Pesos as "₱224.7 million" in panels and "₱225M" in legends. Area as "707.5 km²".
- **Say the year and the source once** per group of figures ("Figures as of 2024." and one source line), not beside every number.

Page titles: pass only the page name to `<Head title="Home" />`. The app adds its own name to the browser tab.

## Do and Don't

| Area | Do | Don't |
| :- | :- | :- |
| Color | Use token classes. Keep hue for data. Brass only for a choice or a connection | Hex values, `bg-blue-500`, gradients, glows, tinted backgrounds for mood |
| Type | Geist for words, Geist Mono for numbers and labels. Use `type-*` roles | Raw `text-2xl font-bold`, a third font, mono paragraphs |
| Shape | Let `rounded-*` resolve to 2px. Hairlines between rows | `rounded-[12px]`, pill buttons, heavy shadows |
| Layout | Build on the existing layout. Lead with the answer and let the next section peek in | Redesigning a page that only needed tweaks, empty hero padding, a sticky header, a top nav bar on public pages |
| Map | Same-size dots shaded by value, a select that does the same job, details beside the map | Unrequested legends, dots that shrink until they vanish, a source line under every figure, a map as the only way to pick a place |
| Copy | Title Case headings and buttons, sentences for text, one year and source per group | Repeated helper paragraphs, dangling statistics, sentence case buttons, separators in titles, "N/A", "0" for missing |
| Motion | Short fades and lifts behind `motion-safe:` | Bounce, parallax, motion that blocks reading |
| States | Loading skeleton, empty message, "Not available", "Hidden to protect individual businesses", error | A blank area, a spinner forever, `0` |
| Access | Keyboard for every control, visible focus ring, `aria-label` on icon buttons and map places | Hover-only information, tiny targets under 40px |

## Layout

### App shell (public)

- **No top bar.** A slim line at the top scrolls away with the page: the app name as a `type-label` link home, the "Simulated data" tag only while simulated records are on (D37), and the light and dark switch on the right. There is no login button on public screens (decisions.md, D20).
- **Phones:** navigation is the bottom bar (`components/app-bottom-bar.tsx`), at most four items, each with an icon and a label.
- **Desktop:** Home and Suggestions as small `type-label` text links on the right of the same line, the current one underlined in brass. Not a bar. Home also links to wishes with its "Share Your Wish" button.
- **Footer:** a formal institutional block naming the Republic of the Philippines, Provincial Government of Cagayan, the initiative, and the Digital Innovation Challenge context. Use clear hierarchy and restrained wording. Data sources are credited in `data/` READMEs and beside the relevant figures.
- Content is centered at `max-w-6xl`, `px-gutter` on phones and `px-6` from `md`, sections `gap-section` apart.
- Capitol uses a fixed, non-collapsible labeled desktop sidebar and a mobile menu. One compact breadcrumb strip follows actual geography/page hierarchy. The content fills the remaining width.
- Compact spacing, readable type, hairline separators, no oversized greeting or decorative metric cards. New workspace dimensions live in app.css tokens.
- Do not render a shared greeting, birthday, date or clock row. Keep session activity, expiry checks and renewal warnings active independently of page decoration.
- Dashboard: MapLibre population map and selected-area details. Omit the requests/approvals placeholder until functional. Community Wishes never appears on the dashboard.
- Selected sidebar destinations use background and weight, without an accent bar. Use a compact Account trigger in the bottom bar; do not show the account name or role in the sidebar. No repeated helper paragraphs about expressed interest, unavailable workflows, or boundary limitations.
- One click selects an area locally, without a server visit or remounting the map. Geographic breadcrumbs also update locally. View Barangays explicitly drills down. Town/barangay selects provide equivalent access; URL filters preserve navigation. Sources and missing figures remain explicit.
- Community Wishes is separate, accessible only to Admin and Super Admin. Activity Logs is Super Admin only. Profile is available to each signed-in role.
- Future business publication uses labeled stages 1 Prepare, 2 Review, 3 Publish. No preview approval controls before that workflow is implemented.

### Home

1. **The hero:** the river ink surface (`bg-brand`) holding the town map, as large as the screen allows (`lg:h-[44rem]`), with compact equal vertical padding and a side column on the right: the town select and the shared icon style control, followed by population, revenue, expenditure, and their sources. On phones the column sits under the map. The page title stays in the document for screen readers only.
2. **About the Data:** the selected Cagayan town’s barangay map (`pages/public/home/barangay-atlas.tsx`), shaded by census population with a tooltip per barangay, a click to keep a barangay's figure under the map, and one PSA source link. Beside it, a definition list of what is real and what is simulated.
3. **How Businesses Are Protected:** three numbered points on `bg-muted`.
4. **What You Can Do:** one large card for wishes with the title "What Business Do You Wish for Your Town?" and the primary "Share Your Wish" button beside compact muted cards for features not open yet.

### Suggestions

The page header, then one shared form canvas with town, business type, specific business, reason, and the halftone town map. Choosing a town on the map or the list fills the other.

Business Type is visible by default. The inline button "Not on the List? Specify a Business" reveals the Specific Business field; either field or both may be submitted. Existing company wishes reveal the field when edited. Selection changes preserve the draft and scroll position, with only the count updating in the background.

### Town map (Home hero)

`components/town-atlas.tsx`. Its wrapper carries the `dark` class, so the map, text, and select use the dark tokens on the ink surface in both modes (the color tokens are mapped in `@theme inline` for this).

- **Map** (`components/map/province-map.tsx`): same-size halftone dots, shaded by population (metrics.md, M18), with water and borders.
  - **Zoom** (`zoomable`): plus, minus, and reset buttons top right, Ctrl or Cmd with the wheel, or a trackpad pinch, from 1 to 6 times. Drag to move when zoomed. Plain scrolling still scrolls the page.
  - Hover or focus: the town lifts slightly, the others dim, and a tooltip shows the name and population.
- **Click** (or Enter): the town turns brass and the panel beside the map shows the town's population, revenue, expenditure, years, and source. The town goes in the URL as `?town=`.
- **Style control:** Dotted and Solid share the population scale, geometry, selection, and tooltips. One icon-only control sits beside the main town selector without adding another toolbar row; it also controls About the Data. No additional legend or zoom instruction is shown.
- **Town select** (`Select`): "All of Cagayan" and the 29 towns. The map is never the only way to pick a town.
- **Details panel:** the kind as a label, the name as the title, then Population, Revenue, and Expenditure in mono. Sources appear once per group. There is no details dialog. A missing figure reads "Not available".
- Only business-relevant figures are shown: population, revenue, expenditure. Other town facts stay in the data file for later.

### Figure row

- `components/figure-row.tsx`: one `Card` with figures split by hairlines. The figure (`type-figure`) on top, the label (`type-label`, muted) under it. Two across on phones, up to four from `lg`.
- A row that holds sales figures uses the guarded figure component, so a hidden cell reads "Hidden to protect individual businesses", never `0`.

### Results screen (list and map)

Used by the business map (P1) and listing (P2), and by the Capitol directory (C13) with its own columns.

```
| Title                      [Share] |  Map panel           |
| Subtitle                           |  (sticky, full       |
| [Barangay v] [Type v]  [Filters]   |   height, in a card) |
| [ Search............ ] [Clear][Go] |                      |
| [Sort A][Sort B]      [List][Grid] |                      |
| item                               |   pins and shading   |
| [ Show More ]                      |                      |
```

- **From `lg`:** the list takes about 55 percent and scrolls. The map stays in place (`sticky top-6`).
- **Below `lg`:** one at a time. A floating "Show Map" or "Show List" button switches. The choice lives in the URL.
- **Filter row:** select triggers with a leading icon and a chevron. Rare filters go behind "More Filters" in a `Sheet`. Applied filters show in their trigger.
- **Sort and view:** `ToggleGroup`. **The list is one `Card`** with `divide-y`.
- **More results:** a full width "Show More" outline button that loads the next server page.

### List item

- A 4 by 3 tile in `bg-muted` with the business type's lucide icon. No photos, no stock images.
- Eyebrow (`type-label`), business name (`type-item`), one line of description, then icon and text pairs separated by space only.
- Hovering the row highlights its pin, and the pin highlights its row. The whole item is one link with a visible focus ring.
- **No sales on public screens,** not a figure, not a band.

### Map panel (business map, F6)

- A `Card` with our barangay shapes over the OpenFreeMap `positron` basemap, falling back to a plain `--muted` background offline (decisions.md, D5). No Google Maps.
- Controls float inside: zoom top right, layer switch top left, legend bottom left.
- Pins are small bordered tags (`type-pin`) with a count or a name. The selected pin inverts to `bg-primary`.
- Pins never show sales. Bands appear only as barangay shading with a legend (privacy-rules.md, P7).

## Components

- Build from `resources/js/components/ui/` (shadcn). Add missing ones with `npx shadcn@latest add <name>`. Do not hand-roll a card, badge, dialog, select, sheet, table, menu, or tooltip. If the CLI adds packages (`cn`, `radix-ui`) or rewrites imports, undo that: imports use `@/lib/utils` and the `@radix-ui/*` packages already installed.
- Change a primitive once instead of overriding it per page.
- Shared building blocks in `resources/js/components/`:

| Component | Use |
| :- | :- |
| `page-header.tsx` | The one-name title and an optional subtitle |
| `figure-row.tsx` | Headline figures in one card |
| `definition-list.tsx` | Term and description rows, for notes and limits |
| `province-map.tsx`, `lazy-province-map.tsx` | The halftone SVG map of the towns or of Tuguegarao's barangays, and the lazy loader pages use |
| `town-atlas.tsx` | The Home hero map, town/style controls, and three public figures with sources |
| `privacy/simulated-label.tsx` | The "Simulated data" tag, amber (`bg-simulated`), rendered by the layout only, and only while simulated records are on |
| `business-badges.tsx` | `BusinessBadges` (BIR, permit, new, streak) and `BirMark` for list rows |
| `registry-badges.tsx` | Capitol record state and the word permit badge in the directory table |
| `category-icon.tsx`, `detail-field.tsx`, `profile-section.tsx` | Category icon in muted ink, a label over its value in `DetailGrid`, and a profile card with a Title Case heading |
| `map/business-map.tsx`, `map/lazy-business-map.tsx` | The one business map: barangay outlines, storefront dots, a business pin, and with `onPick` the form's pin picker |
| `analytics/analytics-panels.tsx`, `charts/finance-panel.tsx` | The Government Finances card and its ledger table, shared by Economy and Capitol Analytics |
| `analytics/source-line.tsx` | Collapsed COA Sources, grouped by year and report, with direct PDF page or workbook links |
| `analytics/analytics-help.tsx` | The Help sheet in the Government Finances header |
| `app-bottom-bar.tsx`, `appearance-toggle.tsx` | The shell |

- **One primary button per screen or area.** Everything else is outline or ghost.
- A feature not built yet is a muted card with a "Not Open Yet" badge.
- The toast (`components/ui/sonner.tsx`) shows flashed messages, above the phone bottom bar.

## States

Every view that shows data handles all five. Exact words come from `privacy-rules.md`, "Words on screen".

| State | Shows |
| :- | :- |
| Loading | A `Skeleton` the shape of the content |
| Empty | What is missing and the next step: "No businesses of this type here yet." |
| Not available | "Not available" in muted text. Never `0`, never a dash |
| Hidden | "Hidden to protect individual businesses", with the reason on hover |
| Error | A plain message with a way back. No technical details |

## Forms

- Every input has a visible label. Placeholders are examples, not labels.
- Build fields from shadcn's `Field`, `FieldLabel`, `FieldDescription`, and `FieldError`. A form-wide error sits in an `Alert` above the submit button.
- During a cooldown or any wait, show the visitor's status and the next date instead of a disabled form.
- The submit button is disabled while submitting and shows a spinner. Success redirects and shows a toast.

## Navigation and auth

- **The URL is the state.** Filters, tabs, the chosen town, and pages live in the URL, so refresh, back, and shared links show the same screen.
- **Breadcrumbs** follow the route: Province > Tuguegarao City > Ugac Norte.
- **Guests who open a Capitol page** get a plain 404 (decisions.md, D20). Logged-in users who open the login page go to the Capitol home. Logout returns to the public home.
- **Public pages never require login.** Admin data never appears on them, not even hidden.
- **Errors:** 403 says "You do not have access to this page." 404 says the page does not exist and links home.

## Accessibility

- Contrast at WCAG AA (4.5 to 1 for text) in both modes.
- Everything works with a keyboard. Focus rings stay visible (`ring` token). Map places are focusable buttons with their name as the label.
- Icon-only buttons have an `aria-label`. Links that open a new tab say so to screen readers.
- Touch targets at least 40 by 40 pixels on public screens.
- Every chart and map has a non-visual way to the same answer: the select, the panel, or a table.

## Before you call a screen done

- [ ] Answers its question in the first screenful, and the next section peeks in
- [ ] Title Case headings and buttons, sentence case text, proper nouns right
- [ ] No em dashes, middots, separators in titles, gradients, or raw colors
- [ ] Sans for words, mono for numbers. `type-*` roles only. 2px corners
- [ ] Dots only for data, with a legend
- [ ] Loading, empty, not available, hidden, and error states
- [ ] Simulated label from the layout on screens with business data
- [ ] Works at 375px and on a laptop, in light and dark mode
- [ ] Keyboard, focus, and reduced motion

## Cagayan Economic Performance

The finance parts of this section, of the two corrections after it, and of Analytics Clarity are superseded by "Government Finances Ledger Table" at the end of this file.

Approved 26-10-25. The new public destination uses the existing slim desktop links and mobile bottom navigation, with Economic Performance on desktop and Economy on phones. The full page title is Cagayan Economic Performance. It answers how the selected town's government finances changed and which businesses are wished for.

Town precedes Topic; only the selected topic's controls and visualization appear. Tuguegarao City is the default. Other towns remain selectable and show Not available with concise source-gathering context. Finance Period and View, or Wishes For and View, sit within the same data surface. Filters and views live in the URL. No barangay controls, statistic strips, population or business-count placeholders.

Finance uses matching left/right peso axes and annual X-axis labels, quiet CartesianGrid, labeled solid/dashed series, exact-value tooltips and a table alternative. Missing points remain gaps. Community Wishes uses distinct Business Types/Specific Businesses modes and cloud/bars/table views. Preserve whole phrases, cap cloud density, keep stable placement and horizontal words, expose counts to keyboard and pointer users, and retain table access when placement fails. Cloud text size encodes counts; palette colors have no category or ranking meaning. No automatic motion is required.

Keep source/demo context with each visualization. Public wish labels are curated and contain no raw resident text. Browser-only visualizations load lazily after mounting; loading, empty, unavailable, hidden where applicable, and retryable error states remain explicit. At phone width, chart tables scroll inside their own surface without making the whole page overflow.

### October 25 Economy Layout Correction

The public page is now named Cagayan Economy, with Economy in navigation. This correction supersedes the preceding one-topic-at-a-time layout: Government Finances appears first, alongside a compact town-picker map on desktop, and Community Wishes follows below. Phones stack the finance surface, map and wishes. One Town dropdown serves both data sections and the map. The map has no fiscal shading or additional statistics.

Keep the page in one compact wrapper to avoid the public shell's large inter-section gaps. Cards use compact padding and 12px content gaps; the finance chart is shorter with solid, high-contrast Cartesian grid lines. View changes use icon-only toggle groups with accessible labels, pressed states, keyboard support and hover/focus tooltips. Keep Period and Wishes For as labeled selects. A Share Your Wish action belongs with Community Wishes, not on the map. Both sections retain complete tables and independent view selections.


### October 25 Home and Combined Wishes Correction

Explicit user correction supersedes earlier table, separate wish-mode, caption and Home placeholder examples. Home presents two working action cards after the geographic hero: What Businesses Would You Like in Your Town, and How Is Your Town Performing Economically. Use primary Share Your Wish and outline Explore the Economy actions, preserving a selected valid town. Remove What You Can Do, Business Map/guide placeholders and How Businesses Are Protected; retain the existing Home maps and About the Data.

Economy shows finance charts only, and one combined Community Wishes ranking with cloud/bars icons. No table controls, kind filter, default phrase-size instruction, visible All Time/as-of caption, finance disclaimer or expressed-interest disclaimer. Source attribution stays concise; snapshot provenance/date is available through the source info tooltip. Policy/metric definitions remain in documentation. Keep full financial observations in a screen-reader list and keyboard-accessible exact-value tooltips.

Ranked bars are a bounded, scrollable ordered list. Put the full wrapping label and exact count above each horizontal bar, so label length never consumes the plotting area. Retain backend order; bar widths are visual scaling only. Cloud items and omitted phrases expose the same exact town/count tooltip on focus, hover and activation. Display omitted phrases as actual count chips rather than a dangling fallback paragraph. Tooltip locations describe the dataset town; do not invent cross-town leadership or measured demand.


### Compact Home Action Cards

26-10-25 follow-up: remove icons from the two Home action cards. Use 16px vertical/horizontal padding, 12px content gaps, no shadow and no extra description. The Economy heading is Explore Your Town's Economy. Preserve the wish heading, working town-aware links and phone stacking. This supersedes the preceding question-style Economy heading and roomy icon-card treatment.

### Government Finances Review Correction

Approved 26-10-28. Keep the index compact with a report table and a right-hand desktop filter panel; stack filters above the list on phones. Use the shared pagination and workspace breadcrumbs documented in frontend.md. A town report separates report year from fiscal year: fiscal-year tabs run newest first and figures sit within catalog categories. Sources are labeled COA source cards with official report links, not a loose row of report-name hyperlinks. Remove the duplicated Government Finances back-link from page content.

Enter a Figure is an action that opens a right-hand drawer. Sign Off and Reject open separate dialogs only after selection; do not leave a rejection reason or entry form permanently on the screen. Rejection requires a reason; a sign-off has no note and records the signed-in Admin instead (26-10-28), shown in the Status column as "Signed off by Name" or "Rejected by Name". Required controls use the shared FieldLabel marker and actual required state. Preserve role, assigned-town and independent-review restrictions.

### Government Finances Simplification

Later user correction, 26-10-28. This supersedes the fiscal-year tabs, source cards and verbose pagination in the earlier correction.

- Use one table with one header and shared column widths. Category separators belong inside that table; do not render each category as an independently sized table. Use Item, Amount in PHP (₱), Source and Status. Align amounts right and keep rows consistent.
- Sources are a compact Source attribution line, like the landing page. In a figure row, the citation itself is the link: Executive Summary p. 1 or pp. 1-2. Do not append a repeated COA Source line. Workbook citations keep a concise sheet/cell reference with the complete reference available to assistive technology and the link title.
- Fiscal Year is a selector in the right-hand filter panel, matching the index pattern. It still updates local URL history over already loaded figures. Do not repeat Report Year 2025 below the town title; report context already appears in the breadcrumb.
- Reading is the default. Checkboxes appear only after Review Figures is activated. Sign Off, Reject and the selection count appear only after at least one figure is selected. Never show 0 selected or a permanently empty bulk-action container.
- Pagination is quiet Previous / Page n of m / Next navigation. No record-range sentence, boxed numbered strip, filled active-page button or separate card surface. Hide the control for a single page.
- The workspace shell owns page gutters and width. Its table/filter grid fills the available content width; do not add a feature-level max-width that leaves an unexplained empty strip on one side.

Repeated patterns to avoid: expanding a source credit into cards, adding subtitles that repeat navigation context, displaying inactive workflow controls, splitting a single dataset into misaligned table surfaces, and presenting every pagination statistic at once. Check these explicitly before calling a UI improvement complete. A user request to improve clarity does not authorize extra framing or helper copy.

### Finance Filters and Business Activity "26-11-09"

- Keep the Fiscal Year label and quiet selector on one row, to the right of the figures on desktop and above them on phones. Recent Uploads is a separate collapsed disclosure below the selector. Indicate active parsing in its label. Each upload shows its filename, short status and upload timestamp in Philippine time. Open it for the full result or retry.
- Public Economy and Capitol Analytics use one collapsed COA Sources disclosure. Inside it, reports are grouped by report year, oldest first. Each report is named once and keeps its direct cited page links or workbook download. Province totals keep the plain COA attribution. This supersedes the earlier wrapped Source line on the analytics screens.
- Business Activity Over Time uses Show Trends For with Business Categories and Official Industries option sections. Contextual help explains the two classifications. Selecting a group changes both existing URL values in local history. The displayed counts and simulated-data rules stay the same.

### Workspace Controls Correction "26-10-28"

- Put Enter a Figure below the fiscal-year selector in the right panel. Use visible outlined Review Figures and Cancel Review buttons; reserve ghost styling for secondary icon controls.
- Figure entry order is Fiscal Year, Item, Basis, Amount in PHP (₱), Source Report, then a conditional official COA link. No entry Note. A sign-off has no note: the reviewing Admin is recorded automatically and shown under the status. Rejection reasons stay required. Figures loaded from the reviewed CSVs have no reviewer account, so they show only their status, and no stored note is shown on a verified figure.
- Use FieldHelp for requested field explanations, rather than permanent helper paragraphs. FieldLabel required markers accompany actual required controls.
- Activity Logs and Community Wishes use the same right-side filter layout, with filters above the table on narrow screens.
- Keep the sidebar compact, with Workspace and authorized Management destinations. The bottom account dropdown contains Profile and Sign Out; do not repeat Profile in the primary navigation. Government Finances uses the peso icon.
- Profile shows the required name marker, without a permanent character-limit caption. Preserve the limit itself.

The bottom bar has one single-row Account dropdown beside the appearance toggle. Profile and Sign Out stay inside the dropdown. Do not use a two-line name/role block or expose identity details in the sidebar (user correction "26-10-28").

Government Finances index uses report-year tabs (`TabRow`), newest available year first and selected by default. Show one year at a time. Do not repeat Report Year in the right-hand filters or every table row. Municipality and Status remain in the filter panel; Clear Filters preserves the active year.


### Analytics Clarity "26-10-28"

Superseded the same day for Source Details, Year Details, the Compare control, the Help drawer and the per-row notes by "Government Finances Ledger Table". The rest stays.

Explicit user approval supersedes the earlier chart-only finance rule on public Economy and Capitol Analytics. Lead with aligned Yearly Summary rows for revenue, expenses and surplus or deficit. Trends is optional. Category labels remain source-specific; Business Taxes and Fees always displays General Fund. Contextual FieldHelp and a navigable Help drawer explain definitions on demand. Source Details is reachable from annual figures, chart tooltips and a persistent Year Details control for keyboard/touch access.

Comparison is off initially. Compact changes use green up and red down triangle/text tokens for direction only, with neutral unchanged values. Rate changes use percentage points. The type-data role is 12px; chart tooltips use 8px padding and aligned label/value rows. Combined coverage, census, restatement and missing reasons remain available together. Exact government figures remain public; the separate Business Trends section explicitly identifies simulated data.

## Dynamic Economic Map Approval "26-10-28"

The user approved Government Revenue, Government Expenses and Surplus or Deficit on both existing economic-map panels, with Year and Measure selectors in the existing panel. This supersedes the earlier Home three-figure restriction for this feature. Latest selects one absolute newest verified paired fiscal year; explicit fiscal-year views remove population values, population sources and population shading. Both maps use financial shading, defaulting to revenue. Retain population context only in Latest. Aggregate finance headings say Sum of Reporting Towns and show metric-specific reporting coverage. A missing town reads "2025 figures are not available." for the selected year, with no earlier-year fallback. Keep the separate public census explorer and its style preference unchanged.

### Economic Map Cleanup "26-10-28"

User correction. The Measure selector is removed from both economic maps because the panel already lists Government Revenue, Government Expenses and Surplus or Deficit. The map always shades by Government Revenue, and the Capitol legend names it. Year is the only finance control.

The panel ends with one Source line followed by one underlined link per distinct report page, such as "COA Report p. 12", opening the PDF at that page. The Latest population link joins the same list. A province total (Sum of Reporting Towns) has one report per town, so it shows one plain line, "COA Annual Audit Reports", with no per-town link list; each town's own report is one selection away. Never print a run of identical citations. The panel has no Source Details drawer, fiscal-year or sign-off text, definitions, or repeated source descriptions. Coverage prints once when all metrics share it. A missing year or barangay level shows one status line, never repeated Not available rows.

Surplus or Deficit shows a green up triangle (`text-trend-up`) for a surplus and a red down triangle (`text-trend-down`) for a deficit, with the signed amount. Zero or missing shows no triangle. The triangle marks the sign of the result, not a performance rating; M26 still forbids rating a change.

## Government Finances Ledger Table "26-10-28"

User correction after reviewing Analytics Clarity: the Yearly Summary repeated sources, the Help was a wall of text, and simulated figures were shown as if they were data. Approved redesign for public Economy and Capitol Analytics. It supersedes every finance rule above that it contradicts.

**The question.** What did this town's government report for the year, and where in the COA report is it?

**Layout.** One Government Finances card. The heading and an outline Help button share the top row. One row of labeled pickers follows: View, Show, then Fiscal Year (Yearly Summary) or Period (Trends). Capitol keeps the same pickers in its right rail. Then one table, nothing else above it.

```
Government Finances                                      [Help]
View [Yearly Summary]  Show [Government Totals]  Fiscal Year [2025]

Item                    2024                2025
Government Revenue      ₱1,465,722,146.22   ₱1,679,019,473.60 ↑
Government Expenses     ₱1,184,642,113.21   ₱1,403,269,517.37 ↑
Surplus or Deficit      ₱281,080,033.01     ₱275,749,956.23

Source
Executive Summary 2025 p. 2
```

- **Yearly Summary** is a table of the year before beside the selected fiscal year, so a 2025 filter reads 2024 then 2025 (oldest to newest, as Trends does; corrected 26-10-28). The previous year and the change are always shown, so there is no Compare control. Item labels wrap; amounts are right-aligned `font-mono tabular-nums`; the selected year carries its arrow, compared with the year before, and there is no Change column (26-11-04). The arrow is direction only, never a rating (M26).
- **Trends** is the existing chart, then the same table with the period's years as columns. Years are underlined buttons: choosing one opens the Yearly Summary for that year with local history, which is the way from a trend to its source. The table uses the short peso form (₱1.68B), with the exact figure kept for screen readers.
- **Exact amounts on hover.** Every abbreviated peso figure (the short form on a phone and every Trends cell) and every figure with a change arrow is an `ExactAmount`: hover, focus or tap shows the exact peso amount, such as "-₱58,651,101.96 from 2024". Tooltips never carry sources. The trigger is a real button with the exact text as its accessible name.
- **Surplus or Deficit, one display.** `lib/trend.ts` and `components/charts/trend-mark.tsx` are the only place that decides it: a surplus is green with a ▲, a deficit is red with a ▼, zero, missing and hidden figures are neutral with no triangle. `Figure`, the Yearly Summary and Trends rows, the chart tooltip and the map panels all use them with the `--trend-up` and `--trend-down` tokens. Never write the triangle or its color inline. It marks direction and sign, not a rating.
- **Trends changes.** Each year cell shows the amount with its tooltip and the triangle beside it, the exact change and its percent on hover (26-11-04; none for a year without a comparable year before). The Executive Summary and the dashboard ledger do the same.
- **Phones** keep the same four columns: amounts use the short form below the `sm` breakpoint (exact figure for screen readers), cell padding and type shrink, and the table never overflows the page.
- **Missing, hidden and not available.** A cell reads "Not available" or "Hidden to protect individual businesses", never `0`. The reason (not published by COA, workbook not loaded, awaiting sign-off, census, restated, coverage) prints once under the table when every row shares it, otherwise under its own item label.

**Sources, once.** The only source on the card is the Source line under a Yearly Summary: one underlined link per distinct report page, such as "Executive Summary 2025 p. 2", opening our stored copy of the COA PDF at that page (`public.finance-sources.show` plus `#page=N`). A workbook figure links once per report ("Financial Statements 2025") and downloads. A Sum of Reporting Towns shows the plain line "COA Annual Audit Reports" with no links. Calculated rows reuse their inputs' citations. Never print a run of identical citations, and never put a source, fiscal-year phrase or sign-off text in a tooltip, a row, a chart or a footer. There is no Source Details drawer and no Year Details control. All evidence lives in one catalog entry per figure (`catalog.sources`); observations only point to it.

**Help.** A Help sheet opens from the card header, never beside the page title. It is short: one sentence of introduction, then native disclosure groups (Reading the Table, Coverage, Sum of Reporting Towns, What These Figures Are, Measures), the first open. Measures lists each measure as its label, one line of meaning and, where it is calculated, its formula. Longer definitions stay in `docs/engineering/metrics.md` and `docs/product/analytics-guide.md`. Show keeps its contextual FieldHelp. No topic select, no marketing copy.

**Tuguegarao City.** Executive Summary totals cover every town, but breakdowns beyond totals are loaded for Tuguegarao City only. A measure another town lacks stays in the Show list, disabled, with the hint "Tuguegarao City Only" (or "Not Loaded for This Town" when several towns have it), so no one lands on an empty panel. Home's About the Data says the same in one sentence. Barangay population is Tuguegarao City only.

**Simulated data.** Off by default (`CAGAYAN_SIMULATED`, D37). While off there are no business records, no Business Trends section, no Simulated data tag. While on, the tag appears in the layout and Business Trends returns, built from the same table. The Community Wishes on Economy are sample wishes for Tuguegarao City and carry a `type-label` "Sample Data" beside the heading; they have no source footer or source tooltip.

Do not restore: per-row Source Details buttons, a Source footer in every panel, report-year captions, a Year Details select, source text in chart tooltips, a Compare checkbox, a Help topic select, or the Help button beside the title.


### Business Categories "26-10-28"

Approved by the user, recorded in D38 and reworked by D39 ("Category, Business Type, Business"). The Capitol Business Categories page is one Business Types table in one `Surface` (26-11-04). The toolbar holds a magnifier that opens into a search box, Category and Industry menus and Clear Filters; the page header holds Categories (a sheet listing categories with their own pen and Add Category) and Add Business Type. Ten rows a page through the shared `Pagination` in the surface footer. Do not restore the old four-column table with an Edit Tags drawer, or Focus Tags.

- **Tree.** Category, then Business Type, then the registered Business by name. A Business Type has exactly one Category. Industry (PSIC) is a label on the type, not a level.
- **Columns.** Business Type, Category chip, Industry, Registered with its year, and a pen icon button to edit. Counts are right-aligned mono and link to the Businesses directory filtered by that type or category. Long industry names wrap, and the table scrolls inside its own surface on phones.
- **Sorting and paging.** Headers are buttons with `aria-sort`, and a count that is not available sorts last in either direction. Ten rows a page with the quiet Previous, Page n of m, Next control. Every row is loaded, so sort, search, filters and pages are local history with zero requests; typing in Search replaces the URL entry instead of adding one per keystroke.
- **Category chips.** A tinted background with a saturated dot and foreground text, always with the category name (`CategoryChip`, palette in `lib/category-colors.ts`). Colors are the `--category-1` to `--category-10` tokens in both modes. This is the approved exception to "hue is reserved for data": a category color never carries meaning alone. Use only the palette, never a typed color or a color package.
- **Forms.** One right-side Sheet for add and edit. Name is stored as typed. When it differs from Title Case the form offers one underlined suggestion, "Title Case: Sari-Sari Store", that the person can accept. Acronyms and mixed-case brands are kept. A duplicate name is flagged as they type. A type chooses one Category and one Industry. Editing a category adds a named color picker; a new category gets its color automatically.
- **Registered.** Active businesses in the latest data year, a count only. It reads "Not available" while simulated records are off (D37).

### Businesses "26-10-28"

A read-only Capitol page for all three roles that lists registered business names under their type (D39). Columns: Business Name, Business Type, Category chip, Industry, Barangay, Established, and the latest year's Status with its status token dot. Public-class fields only: no owner, contact, size, employees or sales. Search, Category, Business Type, Industry and Status are in the right-hand panel and the URL; names, types and years sort. The list is paged 25 at a time by the server with the shared Pagination, because a real registry runs to thousands of rows. While simulated records are off the page reads "No registered businesses yet." and reads no business table. Adding, editing and the full record view belong to the registry plan.

## Finance Trends "26-10-29"

User-approved successor to Government Finances Ledger Table: public Economy and Capitol Analytics default to Trends and Max. Keep 3Y / 5Y / Max beside the displayed year range in the card. Totals lead with the latest revenue and expenses and SQL annual changes, two distinguishable lines, then an aligned surplus/deficit bar chart with a zero baseline. Other finance groups focus one selectable metric at a time. Keep quiet horizontal guides, straight annual segments, missing-year and definition-change gaps, token styles, exact amounts and the detailed table. Yearly Summary and its single Source line remain available through the View picker and table year links. Metric and period selections use local history with no application requests. This supersedes the previous table-first default and heavy-grid treatment only; no new financial definitions or prosperity claims.

## Business Registry "26-10-29"

Approved with D40. The Capitol record and the public profile read like a formal profile, not a long form: a header card with the type icon tile, name, Category chip, type, barangay and Since year, and one row of badges; then compact cards in two columns from `lg` (main content, then a 20 to 22rem side rail). No long single-column rows and no empty side strip.

- **Badges.** Superseded 26-10-29 by "Businesses and Economy Polish" below: no Permit Valid and no New badge anywhere; Expiring and Expired stay, with the dashed record state on Capitol only. Color is never the only signal.
- **Simulated color.** The layout's Simulated data tag is an amber (`--simulated`) bordered tag with a dot, in sentence case at the top right, in both modes so a presentation set is unmistakable.
- **Capitol record.** About, Owner and Registration (TIN masked), Permits, Documents as tiles that open the private file, Registration History as year tiles; side rail Location map with the street address, Business Contact, and Review. Admins approve or return with a required reason from the Review card. Staff see Edit.
- **Directory.** Tabs All, For Review, Returned, Drafts with counts; names link to the record; Permit column; Barangay filter. Add a Business is the one primary button, for Staff.
- **Form.** One page: field groups in two columns on the left, and a sticky Location card on the right with Town, Barangay, Street Address and the pin map. Choosing a barangay frames it; a click or drag sets the pin, and a pin in another barangay selects it. Save Draft and Submit for Review. A returned record shows the reason above the form.
- **Public, corrected by the user "26-10-29".** Nothing loads until a town is chosen; each town's businesses load once and are cached, and every other filter is local. A list row is the category icon in muted ink and the name, with the milestone and BIR marks in fixed slots (corrected again below, "Businesses and Economy Polish"). No result count, uppercase eyebrow, type, description, Since year, icon tiles, permit or streak marks on rows.
- **Profile.** Header corrected below ("Businesses and Economy Polish"): back link above the name, then "Barangay, Town". Below, two columns: About (description, then labels over values in two columns: Business Type, Category, Established, Renewal Streak, Business Permit, BIR Registration), Contact, and More in {barangay} with names and category icons; a sticky Location card with a tall map showing the barangay's other storefronts as dots and this business as a pin. No boxed fields; fields are ruled like a table to the card's edges. Section headings use `type-item` in Title Case, never `type-label` capitals.
- **Badges** (`BusinessBadges`, `BirMark`). Quiet mono: a bordered BIR badge with its shield, a bordered permit badge with its status dot and word (Valid, Expiring, Expired), and muted sparkle New and flame streak marks, each named in a tooltip and for screen readers. Color is only on the permit dot and the BIR shield.
- **One map** (`BusinessMap`) for the list, both profiles and the form: dashed muted barangay outlines, the chosen barangay darker, tinted and framed, primary dots for storefronts, and the business itself as a primary tag with its category icon and a pointer.

## Businesses and Economy Polish "26-10-29"

User corrections from screenshots of the profile, the directory and Economy. They supersede the Business Registry and Government Finances Ledger Table bullets they contradict.

- **Dates** read mm-dd-yyyy everywhere (12-31-2026), from `formatDate` only.
- **Profile header.** A small underlined "Businesses" back link with its arrow above the title, so the title, address and cards share one left edge. No category icon beside the name and no badge row under it.
- **Profile fields.** `DetailGrid` is ruled like a table: a line above the first row and between every cell, running to the card's edges, with field text aligned to the card's text; cards use 12px padding and 8px gaps. BIR Registration reads the shield and "Certificate checked 01-20-2026" inside About.
- **Achievements.** One card of hexagonal ribboned medals set close together, each with a title and one detail line: M34 Operating Milestone (brass, the years on its face, "Since 2004"), M33 Renewal Streak (solid filled flame and count), BIR Registered (shield, "Checked 01-20-2026"). Hidden when nothing is earned.
- **No New, no Valid.** No New badge, New This Year filter or sparkle icon. A valid permit has no badge; Expiring, Expired and No Permit on File keep theirs.
- **Directory.** Filters in one grid row: Town, Search with a leading search icon, Barangay, Category, then BIR Registered. Rows show the category icon and the name only, with two fixed right slots (milestone mark, BIR shield) so rows align. From `lg` the list and map share one fixed-height panel; the list scrolls inside it with Show More at its end.
- **Map area.** The chosen barangay has a solid primary outline and a name tag in the top left; the rest of the town is dimmed.
- **Changes.** Arrow per cell, percent on hover (26-11-04). Trends cells show the figure with the triangle beside it; every year cell shows the arrow against the year before; the exact amount and the percent stay on hover through `ExactAmount`. Negative pesos read in accounting style, (₱58.65M), and keep their red down triangle.
- **Economy.** Panels use 12px padding and tighter charts. Business Trends shows counts only: Declared Sales and Share of Town were removed. The right column is sticky and holds the Town Explorer map and Community Wishes, so no empty strip remains.
- **Community Wishes.** On Economy, one compact ranked list with thin horizontal bars and every wish, no view switcher.
- **Home wish cloud.** The Home wish card holds the `@visx/wordcloud` word cloud (`WishCloud`) between its heading, with the Sample Data label, and Share Your Wish: horizontal words packed on an archimedean spiral with a fixed generator, sized on a log scale, the most wished in brand blue, then teal, then muted. No counts on the words; hover, focus or tap shows the original `WishTooltip` (label, town, wishes). A layout that cannot place every word is redone with smaller sizes, so no wish is dropped. Lazy loaded. The Economy action becomes one row below it: heading left, Explore the Economy right.

## Kita Brand "26-10-29"

The app is named Kita (`APP_NAME`). Logos come only from the official files in `docs/brand`, copied to `public/brand` and drawn by `KitaLogo` (`components/kita-logo.tsx`), which picks the reverse file on dark surfaces and never recolors, stretches or retypes them.

- **Public top line.** The horizontal lockup (28 px high) links home, then icon and text links from `lg` with the brass underline on the current section, then the appearance toggle and the Simulated data tag behind a divider. A hairline border closes the line; it still scrolls away.
- **Footer.** Brand ink: the stacked reverse logo with one sentence on the initiative; Explore (the four sections), Sources (PSA, COA, OpenStreetMap, external links) and Government columns; a bottom row with the Kita icon and name, and Digital Innovation Challenge 2026. No link to the Capitol (D20).
- **Capitol and Sign In.** The Capitol sidebar shows the horizontal lockup; the Sign In brand panel shows the stacked reverse logo. The browser tab uses the Kita icon.

## Executive Summary View "26-10-29"

User-approved simplification for public Economy and Capitol Analytics. It supersedes the Trends default in "Finance Trends", and every Surplus or Deficit triangle rule in "Government Finances Ledger Table", "Economic Map Cleanup" and "Businesses and Economy Polish".

**The question.** What did this town's government own, owe, earn, spend and budget each year, as COA printed it?

- **Default view.** View offers Executive Summary (default), Yearly Summary and Trends. Executive Summary has no Show or Fiscal Year picker; Period (3Y, 5Y, Max, default Max) sits beside the year range.
- **One table.** Item, then one column per fiscal year, oldest to newest. Three sections in COA's order, each a `type-label` row: Financial Condition (Assets, Liabilities, Government Equity), Results of Operation (Income, Expenses, Net Income), Budget Execution (Current Appropriations, Current Allotments, Continuing Allotments, Current Obligations, Continuing Obligations). The table fits its card with no scrolling and no sticky column: the Item column takes the free width and the years share the rest (26-11-04).
- **Amounts.** Exact pesos when three years or fewer are shown. Longer periods use the short form (₱1.68B) with the exact amount on hover, focus or tap through `ExactAmount`, as Trends does. A year heading opens that year's Yearly Summary with local history.
- **Net Income.** Income minus Expenses (M19). A loss reads in accounting parentheses, (₱6.54M), with no triangle and no green or red, everywhere: the table, Yearly Summary, Trends, chart tooltips and both economic map panels. Arrows remain only beside year cells, as direction, never a rating.
- **Words.** Income, Expenses and Net Income under the Government Finances heading; "Government Income" on map legends, which have no such heading. The Show group formerly called Government Totals is Results of Operation.
- **Charts.** Below the table, side by side from `lg`: Results of Operation (Income and Expenses lines) and Financial Condition (Liabilities and Government Equity stacked, so each bar is Assets), with `--chart-*` tokens, lazy loaded, the table as their alternative.
- **Missing.** A row a town lacks reads Not available in every cell with the reason once under its label, such as "Loaded for Tuguegarao City only" (M35). Never `0`.
- **Sources.** One Source line, one link per distinct report page, sorted by report year then page.

## Finance Report Polish "26-11-03"

Owner-approved polish of the Executive Summary View after the rejected rebuild was reverted (plan `docs/plan/active/26-11-03-finance-report-redesign.md`). It supersedes, in "Executive Summary View", the View picker, the Period caption beside the year range, Max, the `type-label` section rows, and every triangle.

**The question.** How did this town's government do year after year, in COA's own words?

- **Header.** One row beside Government Finances: Show (only in Charts or one year), 3Y 5Y All chips, a Year select that opens the Yearly Summary, an icon Table or Charts toggle (Charts is the former Trends) and an icon Help button. No year-range caption; years appear only in column headings and axes. Every control is local history.
- **Table.** Visible column lines, alternating solid row shades (`bg-card`, `bg-muted`), Title Case section rows in `type-item`, and an arrow in each year cell comparing it with the year before. Net Income has no arrow.
- **Change marks.** Green up arrow ↑ and red down arrow ↓ from `lib/trend.ts`, direction only, never a rating.
- **Charts.** Results of Operation shows Income and Expenses as paired bars with Net Income as a line over the zero line; Financial Condition keeps its stacked bars. Both share one compact tooltip card (year, then swatch, label and exact amount per series) and hover the same year together.
- **Source.** One wrapping line of links, never a vertical stack.
- **Capitol only.** A compact figure strip above the table: Income, Expenses, Net Income and Assets for the newest reported year, short amounts through `ExactAmount`, with the change. Public Economy has no strip; it leads with the yearly history.
- **Words.** Revenue lines use the Statement of Financial Performance's own labels; the SCBAA group is Budget and Actual Amounts (metrics.md M21, M23).

## Dense Data Rule "26-11-03"

Owner correction after the Trends view showed a 2x2 grid of padded figure tiles with a 3xl amount, a year and an arrow under each. Never build that again, on any screen.

- **Rows, not tiles.** Several figures for the same years go in one table row each, with the years as columns. Never a grid of cards, one figure per card, an oversized amount, or the year repeated under every figure.
- **Every table is the shared `components/ui/table.tsx`.** It draws the outer border and the column lines by default. Alternating solid row shades are only for finance tables beside charts (`<Table data-striped>`: Executive Summary, the Charts view table, Yearly Summary); other workspace tables are not striped (owner, 26-11-03). A section heading row passes `data-plain` so it is never shaded. Put it inside a `Surface`, which draws the one border (the table's own is removed there).
- **Clickable rows, not underlined links.** A row that opens something puts `RowLink` (`components/row-link.tsx`) in its first cell and `RowChevron` in a narrow last cell. The whole row is the click and keyboard target, with hover and focus on the row. Buttons inside such a row sit above it with `relative z-10`. Underlines stay only on links inside text, such as a Source line.
- **Density tokens.** Spacing comes from `--spacing-panel` (inside cards and panels), `--spacing-cell-x`, `--spacing-cell-y` and `--spacing-row` (table header height) in `resources/css/app.css`, used as `p-panel`, `px-cell-x`, `py-cell-y` and `h-row`. Tighten or loosen the whole app there, never per screen.
- **One tooltip per figure.** A short amount and its change arrow are one control (`Figure` with `withChange`): the tooltip reads the exact amount, then "Change from 2024:" the exact change in accounting style (a fall in parentheses, a rise as a plain amount, no words and no plus sign), then its percent such as "-4.2%" when the year before is not zero. Every change label follows this. Never a separate hover target for the arrow.
- **Maximize rows.** Compact cell padding, `type-data` on phones, no empty space reserved for decoration. Padding exists to separate content, not to fill a card.

## Finance Charts "26-11-03"

Supersedes the chart lines in "Finance Report Polish". One chart per measure, chosen by how its lines relate (`FinanceHistoryChart` `variant`):

| Measure | Chart | Why |
| :- | :- | :- |
| Results of Operation | Income and Expenses as overlapping areas, Net Income as a line over a zero line | One chart instead of separate Income, Expenses and Net Income charts; the gap between the areas is the result. Not stacked: Income plus Expenses means nothing |
| Financial Condition | Liabilities and Government Equity as stacked areas | Their sum is Assets, so the top edge reads as Assets; the tooltip lists all three |
| Revenue Sources | Stacked areas | The reported sources add up to Income |

Stacked parts use one hue, the `--map-*` ramp, darkest at the base, with light fills (30%) and solid edges, so they read as layers of one total. Unrelated `--chart-*` colors are only for series compared side by side (owner, 26-11-03).
| Budget Execution | Grouped bars per year | Appropriations, allotments and obligations are compared side by side, not added |
| Business Taxes and Fees | Lines | Separate General Fund collections with no reported total |

No synchronized tooltips across charts. Missing years stay gaps. The Charts view is that one chart above the same dense table; it has no figure tiles and no separate Net Income chart. Budget and Actual Amounts is withdrawn from display (metrics.md M23).

## Public Navigation and Data Labels "26-10-30"

User correction supersedes the Kita Brand top-line icons and underline. Desktop navigation uses text-only links with a filled selected background, visible keyboard focus and no underline. The public navbar has no Simulated data tag. Sample Data labels sit at the upper right of the Home and Economy wish cards, using a red token-based bordered badge. Public simulated business labels use the shared config-driven SimulatedLabel inside the directory, profile and Business Trends content; Capitol retains its layout label. No extra explanatory copy or navigation icons.

## Achievements Redesign "26-10-30"

User correction supersedes the custom hexagonal ribbon medals in Businesses and Economy Polish. The shared Achievements card uses lucide-react Award, Flame and ShieldCheck icons from the semantic icon registry, with compact bordered panels, clear values and short details. Stack panels as rows on phones and columns from sm. Keep operating milestone, renewal streak and BIR eligibility unchanged; omit the card when none are earned. No custom medal SVG, ribbon tails or additional animation.

## Original Achievements Restored "26-10-30"

Latest user correction supersedes both attempted external badge packs and the generic icon panels. Retain the original compact hexagonal medals and ribbon tails, original colors, titles, values and details. Refinement is limited to a continuous quiet inner outline, slightly lighter ribbons and more breathing room within the medal face. The original card and wrapping arrangement remain. No external artwork, new dependency, metric or eligibility changes.

## Surfaces and Page Headers "26-11-04"

Owner correction: tables, tabs and titles sat straight on the page background, so nothing looked like a card.

- **Everything sits on a card.** `components/ui/surface.tsx` is the one card: a bordered `bg-card` surface with an optional heading row (`title`, `toolbar`), a `footer` (pagination, save actions) and `padded` for forms. Tables go in flush; the surface draws the border. Tab rows (`TabRow`) and filter rows are a `Surface padded` too. Never `rounded-sm border bg-card` by hand.
- **Page header.** Every Capitol page starts with `components/capitol/page-header.tsx`: the title in its own card with the page actions on the right. The breadcrumb strip is a card bar as well.
- **Page background.** `--background` is a neutral stone one step darker than white cards (light) and one step darker than the card slate (dark), so a surface always lifts off the page. No blue cast in light mode.
- **Icon actions.** Row edit is a pen icon button (`components/capitol/edit-button.tsx`, named for screen readers), never the word Edit. Search in a toolbar is a magnifier (`components/capitol/search-field.tsx`).
- **Verified is locked.** In a bulk entry grid a verified figure shows as text with a pen; the pen unlocks that one cell and asks for a reason. Pending and empty cells stay inputs (`components/capitol/entry-cell.tsx`). Status words come from `FIGURE_STATUS` in `lib/registry.ts`: Pending, Verified, Returned.
