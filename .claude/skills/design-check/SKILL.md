---
name: design-check
description: Use before committing a change to pages, components, layouts, CSS, or UI text. Checks the diff against the design system in docs/engineering/design.md (capitalization, writing rules, tokens, type, radius, halftone, states, routes and auth behavior) and reports findings with file and line.
argument-hint: "[optional file list]"
---

# Design check

Scope given by the person, if any: $ARGUMENTS. Otherwise the changed files under `resources/js/` and `resources/css/` in `git diff origin/staging...HEAD` and `git diff`.

## Steps

1. **Read `docs/engineering/design.md`** in full, and `privacy-rules.md`, "Words on screen".
2. **Search the changed files** for each of these. Report every hit with file and line:
   - em dash `—`, en dash `–`, or middot `·` in any UI string
   - a title, heading, `<Head title>`, breadcrumb label, or tab label joining parts with a comma, dash, middot, or pipe
   - `gradient` in a class (`bg-gradient-*`, `from-*`, `via-*`, `to-*`) or in CSS (`linear-gradient`, `radial-gradient`)
   - raw colors: hex values, `rgb(`, or palette classes like `bg-blue-500`, `text-red-600` instead of tokens
   - capitalization (design.md, "Capitalization"): page titles, headings, card titles, buttons, tabs, badges, nav items, legend titles, and table headers not in Title Case; sentences (subtitles, descriptions, toasts, errors, empty states) not in sentence case; a proper noun or acronym written wrong (Tuguegarao City, Peñablanca, PSA, PSGC, DTI)
   - Unicode replacement characters, invalid UTF-8 or mojibake in visible copy; verify changed labels in the browser after writing files as UTF-8
   - exclamation marks or emoji in UI text
   - raw type classes (`text-2xl font-bold`) where a `type-*` role exists, a font other than `font-sans` and `font-mono`, numbers or figures not in `font-mono` with `tabular-nums`
   - radius other than the `rounded-*` scale or `rounded-full` (no `rounded-[...]`), and `rounded-full` on anything but dots, avatars, and round icon buttons
   - dots or a dotted pattern used as decoration, or map dots that change size; check map keys against the screen's approved scope instead of adding an unsolicited Home legend
   - raw durations or easings where a motion token exists, and motion without `motion-safe:` or a reduced-motion fallback
   - a year or source repeated on every row instead of once per group of figures
   - a hard-coded URL instead of a Wayfinder import, or `<a href>` for in-app navigation
   - a data view missing one of its states: loading, empty, not available, hidden, error
   - "not available" or "hidden" rendered as `0`, a dash, or blank
   - a page with business data that does not use the shared layout with the "Simulated data" label
   - icon-only buttons without `aria-label`, inputs without a visible label
   - a component that only wraps one element or passes props through, or a new file for markup used once
3. **Check scope and copy against design.md, "Working on Existing Screens":** no unrequested redesign, dangling statistics, repeated helper paragraphs, decorative link arrows, restored modals or map CTAs, duplicate style controls, or section overlap. Confirm bounded dropdowns, the Specific Business reveal control, draft preservation, and count-only updates. Check behavior by reading the routes and pages: filters and tabs in the URL, redirect and toast after a write, guests get a plain 404 on Capitol pages and nothing public links to the admin login (decisions.md, D20), public pages without admin fields.
4. **Audit every visible sentence:** reject unrequested descriptions, metric/privacy disclaimers, automatic snapshot captions, chart-size explanations, and fallback instructions that reference a removed view. The user has explicitly removed this copy repeatedly. Definitions belong in docs; secondary provenance/date can use one accessible contextual tooltip. Check empty/error recovery separately so necessary feedback is retained. Do not restore Home placeholder/protection sections under new labels.

5. **Check interaction requests:** sort/view/loaded-data filters and cached map selections must update local Inertia history with zero application requests. Verify repeated selection, Back/Forward, and refresh. A URL change alone never justifies a server visit. Check frontend.md, "Local Selection Contract"; flag missing-data requests that are not scoped, cached and deduplicated.

5a. **Check category colors:** Business Category color appears only through `CategoryChip` and the `--category-N` tokens, always with the category name, and never as a typed color or raw palette class. Tables with chips must stay within their own scroll surface at 375px.
5c. **Check shared figures:** a change uses `TrendMark` or `ObservationChange` and `lib/trend.ts`, never an inline ▲, ▼ or `text-trend-*` class. Net Income has no triangle or color; a loss reads in parentheses. An abbreviated or rounded peso figure uses `ExactAmount`. Year columns run oldest to newest. A component that reads a module-level cache during render must also hold the value in state.
5b. **Check repetition in every state of the changed view:** open the aggregate or province case, a single item, an empty case and the drill-down level. Flag any run of identical or near-identical links, citations, labels or rows, repeated coverage or Not available lines, and sources listed once per row instead of once per group. Condense them. Never judge a panel from the single-item case alone (user correction "26-10-28", economic map province Source list).
6. **Report** findings grouped as must fix (breaks a written rule) and should fix (polish). With no findings, say which checks ran.
7. **Fix only when asked.**

## Done when

Every check above has run on the changed files and each finding has a file and line.
