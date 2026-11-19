---
name: ui-builder
description: Use when building or restyling any screen, section, or component. Walks through the design system in docs/engineering/design.md (tokens, Title Case, sans and mono type, 2px corners, halftone dots for data, the five states, phone first) so new UI matches the rest of the app, then hands off to /design-check.
argument-hint: "<screen or component, e.g. barangay profile page>"
---

# UI Builder

Act as the product designer and frontend engineer for the requested screen. Own the hierarchy, density, responsive layout, and interaction details inside the approved scope.

## 1. Decide Before Building

1. Write one sentence: what question does the visitor need answered on this screen, and what do they do next? If you cannot, ask.
2. Read `docs/engineering/design.md` for the screen's own section and "Working on Existing Screens", the active plan, and the owner's latest correction. A later owner correction replaces an earlier rule; update `design.md` in the same change.
3. Open the current screen and its consumers. Choose the smallest change that answers the question in step 1.

## 2. Reuse Before Writing

Compose from these before writing markup. Extend one with a small prop when it nearly fits; never copy its markup.

| Need | Use |
| :- | :- |
| Page title and header actions | `components/capitol/page-header.tsx` |
| Any card, table frame, form frame | `components/ui/surface.tsx` |
| Table | `components/ui/table.tsx`; rows that open something use `components/row-link.tsx` (`RowLink`, `RowChevron`; `onClick` for rows that open in place) |
| Filters beside results | `components/capitol/workspace-filters.tsx` (`WorkspaceFilterLayout`, `WorkspaceFilters`) |
| Tab row above content | `components/capitol/tab-row.tsx` |
| Help or reference guide | `components/guide-dialog.tsx` (`GuideDialog`, `GuideSection`, `GUIDE_TARGET`) |
| Full-page focus view | `components/ui/focus-dialog.tsx` |
| Review status in words | `FigureStatus` in `components/registry-badges.tsx` |
| Number entry cell | `components/capitol/entry-cell.tsx` |
| Contextual help on a field | `components/capitol/field-help.tsx` |
| Scroll area inside an overlay | `scrollbar-quiet` utility |

## 3. Rules That Were Broken Before

- One idea per view. A grid of many rows by many columns is overwhelming: show one slice (a year, a town) and put the other dimension in the filter panel.
- Never leave a blank cell, and never show a zero-like placeholder. A real count of zero prints `0`; a missing figure prints N/A.
- A status is words, not a colour alone. If a dot needs a key, use the worded badge instead.
- Do not add helper lines, keys, legends or subtitles under controls. If the screen needs one to be understood, the layout is wrong.
- Do not put counts beside filter options unless the owner asks.
- Placeholders say what to enter ("Enter amount", "https://www.coa.gov.ph/"). They never look like a stored value.
- Keep it short. Delete props, imports and branches the change makes unused, in the same change.

## 4. Verify

1. `npm run check:fix`, `npm run types:check`, `npm run build`.
2. Confirm the dev server serves the change before saying the owner can see it: compare `http://localhost:5173/resources/js/<file>` with the file on disk. Vite on Windows can miss a change; save the file again if they differ.
3. Unless the owner said not to, look at the screen in the browser: desktop and phone, the link or row that opens something, and the console.
4. Re-read each changed file on disk at the end. Other agents share this checkout and can overwrite a file.
5. Run `/design-check`, then record the result and what was not checked in the active plan.
