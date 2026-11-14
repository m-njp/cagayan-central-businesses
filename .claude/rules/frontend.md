---
paths:
  - "resources/js/**/*.{ts,tsx}"
  - "resources/css/**/*.css"
---

# Front end rules

Full guides: `docs/engineering/frontend.md` (structure and data) and `docs/engineering/design.md` (look and words).

Before editing an existing page, read design.md, "Working on Existing Screens". That section is the canonical record of the user's corrections. Preserve the requested structure, remove copy that repeats a control, and do not restore removed features under a new label.

## Structure

- **Data comes from props.** No API calls for page data, no Redux, Zustand, React Query, or Axios. Loaded-data filters change the URL through local Inertia history. Scope server requests to missing or stale authorized data.
- **Pages** in `resources/js/pages/`, folders following the route name (`capitol/barangays/show/index` resolves to `pages/capitol/barangays/show/index.tsx`). Each entry declares props matching its PHP Data object. Page-private sections and hooks live beside it.
- **Reuse first:** `components/ui/` (shadcn), then `components/`, `hooks/`, `lib/`.
- **Do not over-split.** Extract a real section or workflow. Keep short private markup inline and skip pass-through wrappers and barrels.
- **No metric math in the browser.** Format only, with the helpers in `lib/`.
- **Links and forms use Wayfinder** (`@/routes`, `@/actions`). Never a hard-coded URL. `<Link>` for navigation.
- **Heavy libraries load lazily** (`React.lazy` for the map and charts) with a skeleton.
- **Map:** import from `react-map-gl/maplibre`, never the default import. Basemap is OpenFreeMap `positron` with the offline fallback in `frontend.md`, "The map". Map colors come from tokens through `mapColor()`.
- **Charts:** shadcn's `ChartContainer` (Recharts) with `--chart-*` tokens. Every chart has a table version.
- **Planned PDF export:** `@react-pdf/renderer`, templates in `resources/js/pdf/`, built only from the page's props, loaded with `import()` on click. Tailwind and shadcn do not work inside a PDF. Show the "Simulated data" label.
- **The server decides access.** Hiding something in React protects nothing. Public pages never receive admin fields.
- File names kebab-case. Components PascalCase. Hooks `use-*`.

## Look and words

The design system is `docs/engineering/design.md`. Start UI work with `/ui-builder`, finish with `/design-check`.

Do:

- Token classes for every color, space, radius, and motion value (`bg-primary`, `fill-map-3`, `gap-section`, `ease-out-soft`).
- `type-*` roles. Sans for words, `font-mono tabular-nums` for numbers, `type-label` for small uppercase labels.
- 2px corners through the `rounded-*` scale. `rounded-full` only for dots, avatars, and round icon buttons.
- Business Categories render through `CategoryChip` with the `--category-1` to `--category-10` palette (`lib/category-colors.ts`), always beside the category name. It is the one approved non-data hue (D38, D39). Never add a color picker that accepts a typed color or a color package.
- Halftone dots (`lib/halftone.ts`) for map values: every dot the same size, color depth for the value. Add a key only where the approved screen requires it; Home does not have a legend.
- **Title Case** for page titles, headings, card titles, buttons, tabs, badges, nav items, form field labels, legend titles, and table headers. **Sentence case** for subtitles, descriptions, body, toasts, errors, and empty states. Proper nouns and acronyms right (Tuguegarao City, Peñablanca, PSA, PSGC).
- The year and source once per group of figures, never repeated on every row. Numbers right-aligned.
- Build on the existing layout. Change what was asked, do not redesign a page unasked.
- Fit additions within their section. Keep form and map in one shared canvas, bound dropdown heights globally, and avoid blank hero padding or vertical overlap.
- Use one shared icon control for Home map styles. Keep only population, revenue, expenditure, and their relevant sources in the main map panel.
- Keep Business Type visible by default and reveal Specific Business through its explicit control. Preserve drafts on selection changes; update only the count in the background.
- Write formal, accurate data and privacy explanations. Keep helper copy only when the control cannot communicate the requirement itself. Underline links without decorative arrows.
- Every data view: loading, empty, not available, hidden, error. Exact phrases from `docs/engineering/privacy-rules.md`, "Words on screen". Never show hidden or not available as `0`.
- Phone first, then desktop. Light and dark mode both work.

Don't:

- Em dashes, en dashes, or middots in UI text. Separators (commas, dashes, middots, pipes) in titles and headings.
- Gradients, glows, decorative patterns, dots as decoration, hex values, or palette classes like `bg-blue-500`.
- Raw `text-2xl font-bold` where a role exists, a third font, or `rounded-[...]`.
- A top nav bar on public pages, or the "Simulated data" label added page by page (it comes from the shared layout).
- Color as the only signal, or a map as the only way to pick a place.
- Dangling dashboard statistics, suggestion-count badges, numbered form instructions, repeated company examples or privacy assurances, and unrequested legends or zoom hints.
- More Details modals, Suggest a Business Here on maps, double-click requirements, duplicate style controls, or the extra barangay dropdown.

Run `npm run check:fix` after editing. The pre-commit hook runs the lint and TypeScript checks.

## Repeated UI Corrections

Never build grids of padded figure tiles (one large amount per card with a year and arrow beneath). Figures for the same years go in table rows, years as columns. Every table uses `components/ui/table.tsx`, which draws the border and column lines; only finance tables beside charts add `data-striped` for alternating rows; mark section rows `data-plain`. Rows that open something use `RowLink` and `RowChevron`, never an underlined link. Spacing comes from the density tokens `p-panel`, `px-cell-x`, `py-cell-y`, `h-row`. Stacked chart parts use the one-hue `--map-*` ramp. A short amount and its change share one tooltip through `Figure withChange`. Charts follow design.md, "Finance Charts", with no synchronized tooltips (26-11-03).

Show a change only through `TrendMark`, `ObservationChange` and `lib/trend.ts` (green ↑ up, red ↓ down, tokens only). Net Income carries no arrow or color: a loss reads in accounting parentheses (design.md, "Executive Summary View"). Show any rounded or abbreviated peso figure through `ExactAmount` so hover, focus and tap reveal the exact amount. Order year columns oldest to newest. Never read a mutable module cache during render without keeping its result in state: the React Compiler memoizes the read and the screen never updates (the About the Data bug, 26-10-28).

Check every state of a data panel before reporting it done: province or aggregate, a single town, an empty year, and barangay level. Verify the aggregate in the running app, because it carries the most rows. Never print a run of identical or near-identical citations or rows; condense them into one line (design.md, "Economic Map Cleanup"). The economic maps have no Measure selector, and Net Income reads in parentheses when negative, with no arrow.

Public Economy and Capitol Analytics finance open on design.md, "Executive Summary View" (COA's lines as rows, years as columns). Apply "Finance Filters and Business Activity" for the collapsed COA Sources disclosure and "Government Finances Simplification" for the Capitol review table. The shared pagination and breadcrumb contracts in frontend.md apply across the workspace.

Apply the later workspace-controls correction in design.md: actions must look actionable (Review/Cancel use outlines), Enter Report (the report grid, 26-11-03) belongs in the right panel, source selection is called Source Report, and entry has no Note; a Reason for Correction appears only when a verified amount changes. Use contextual FieldHelp instead of permanent explanations. Do not restore shared greeting/clock rows or duplicate Profile in the primary nav. Preserve session renewal/expiry behavior, profile limits, permission-based destinations and independent review restrictions when simplifying presentation.

Sidebar account controls use a single-row Account trigger with Profile and Sign Out in its dropdown. Never show the account name or role in the sidebar; keep the appearance toggle aligned beside the trigger.

## Corrections From "26-11-14"

- Guides use `components/guide-dialog.tsx`: tabs at the left, one section at a time, an outlined entry when a link opened it. Never one long scrolling page.
- Report Differences is one table per report year with a worded Review column and the year in `WorkspaceFilters`. The owner rejected the municipality by year grid, coloured dots without words, blank cells, counts beside filter options and a key line under the filters.
- Entry placeholders say what to enter and never look like a stored value.
- After a rewrite, confirm Vite serves the new file before telling the owner to look (`/ui-builder`, "Verify").
