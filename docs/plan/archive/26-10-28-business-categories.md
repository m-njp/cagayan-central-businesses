---
title: "Business Categories Revamp"
created: "26-10-28"
updated: "26-10-28"
status: completed
branch: "feat/analytics-clarity"
base: staging
predecessor: "archive/26-10-27-finance-ledger-analytics-program.md"
---

# Business Categories Revamp

> Superseded in part on "26-10-28": the Focus Tag model (overlapping tags, several per type) was replaced by one Category per Business Type in [Category Hierarchy and Business Directory](26-10-28-category-hierarchy.md) (D39). Kept as history. The page shell, local sorting, paging, search, Title Case suggestion, color palette idea and validation rules carry over.

## Objective

The user asked for a proper way to categorize businesses on the Capitol Business Categories page: registered-business aggregates per type, pagination, sorting, proper labeling and capitalization, colored tags, and a design that handles custom inputs from clients. It builds on D36 (Industries and Focus Tags), which only allowed adding Focus Tags.

## Scope and Acceptance

- [x] Each Business Type and Focus Tag shows Registered (year): active businesses in the latest data year (M1, M28), counts only. A tag sums its types without double counting. It reads "Not available" while simulated records are off (D37).
- [x] Two URL tabs, Business Types and Focus Tags. Sorting by header with `aria-sort`, search, Focus Tag and Industry filters, ten rows a page. All of it is local history with zero requests (browser check).
- [x] Admins add and rename Business Types (name, industry, tags) and Focus Tags (name, color). A type may queue new tags, created with the least used color. Codes never change.
- [x] Names are stored as typed. The form suggests Title Case and flags duplicates live. The server refuses duplicates ignoring case, markup, control characters, empty slugs, over-length names, an unknown industry, tag or color, and a type past the cap.
- [x] Tag colors are the ten `--tag-N` tokens (light and dark) through `TagChip`, always with the tag name. No color package (D38).
- [x] Seeded type names are Title Case. Activity events: `business_type_created`, `business_type_updated`, `focus_tag_updated`. Staff are refused on every route.
- [x] Docs: D38, `database.md`, `design.md`, `frontend.md`, CLAUDE.md, roadmap, frontend rules and the `design-check` and `ui-builder` skills.

## Deferred or Excluded

- Citizen suggestion queue from Specific Business wishes.
- Removing types or tags. Both are referenced by wishes or business records, so deletion needs its own plan.
- Per-tag sales. Focus Tag sales stay bands for every audience (P4), so this page shows counts only.
- PSIC sections beyond the ten seeded. The data member still has to verify the existing sections.
- Server-side paging, which the 300-type cap makes unnecessary.
- Not browser-checked: light mode, the Edit forms for an existing type or tag, and Staff access (covered by Pest only).
- Existing databases keep sentence-case type names until `php artisan db:seed --class=BusinessTypeSeeder` runs. It was run on the local database only.

## Decisions and Sources

- "26-10-28", scope: Admins add and edit types and tags, with no suggestion queue or removal. User choice.
- "26-10-28", capitalization: store as typed and suggest Title Case, chosen over forced conversion because CLAUDE.md says never to force capitalization.
- "26-10-28", loading: load everything and work locally, chosen over server-side paging for the loaded-data rule.
- "26-10-28", colors: the user asked for Mentimeter-style colors and whether a package is better. No package: a library yields arbitrary colors that break the tokens-only rule and AA contrast. A ten-slot token palette with automatic least-used assignment gives the variety. Recorded in [D38](../../engineering/decisions.md).
- "26-10-28", decision number: the plan said D37, but D37 was taken the same day by the simulated-data decision, so this is D38. Counts follow its flag.
- Canonical docs: [design.md, "Business Categories"](../../engineering/design.md), [frontend.md](../../engineering/frontend.md), [database.md](../../engineering/database.md).

## Change History

| Date | Change | Reason / Reference |
| :- | :- | :- |
| "26-10-28" | Migration `add_color_and_unique_names_to_categories` (focus tag color, case-insensitive unique names), config, `CategoryName` rule, requests, four actions, controller, query and Data objects | The agreed plan |
| "26-10-28" | New page, forms, `TagChip`, `lib/category-table.ts`, `lib/tag-colors.ts`, `--tag-N` tokens, `composer test:categories` | The agreed plan |
| "26-10-28" | Registered counts gated on `cagayan.simulated` | D37 and P13 landed in the working tree during the work, and business figures must not show without the label |
| "26-10-28" | Long Industry names wrap | Browser check showed the table overflowing at 1280px |
| "26-10-28" | Focus Tag model superseded in part by one Category per Type | User correction: Boarding House is not Tourism, and categories are sectors. See the successor plan and D39 |

## Verification and Handoff

- `npm run check:fix`, `npm run check`, `npm run types:check` and `npm run build` pass. `composer test:categories` passes (8 tests). PHPStan passes with zero errors. Pint passes.
- Pest: `BusinessCategoriesTest` 28 passed (123 assertions), `tests/Feature/Privacy` 11 passed, `CapitolWorkspaceTest` passed. Two runs failed with "relation does not exist" because another session reset the shared test database during the run; both passed when rerun alone. The full suite and `composer ci:check` were not run.
- Browser as the local demo Admin: sorting, paging, search and Back made zero application requests. A new type with a messy name showed the Title Case suggestion and saved with an existing tag and a new tag, which took the next free color. The Focus Tags tab and a 375px width showed no page overflow. The test rows were removed afterward.
- Privacy review: the diff reads no sales or admin fields, passes Data objects only, logs event codes only, and gates counts on the simulated flag. P1 to P7, P9, P11 and P12 do not apply. P8, P10, P13 and P14 hold.
- Committed on `feat/analytics-clarity` on "26-10-28" together with the rest of that day's work, as one commit (see `git log`). Not pushed, and no pull request is open yet.
