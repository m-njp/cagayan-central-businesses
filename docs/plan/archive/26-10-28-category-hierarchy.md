---
title: "Category Hierarchy and Business Directory"
created: "26-10-28"
updated: "26-10-28"
status: completed
branch: "feat/analytics-clarity"
base: staging
predecessor: "26-10-28-business-categories.md"
---

# Category Hierarchy and Business Directory

## Objective

The user rejected the Focus Tag model built earlier on "26-10-28". Categories must be real sectors (Tourism, Food Processing, Agriculture, Fishery, Retail, Vehicle and Motor Parts), and a Boarding House is not Tourism. The actual registered business names must sit under their Business Type. A business registry is planned later, so this plan had to scale toward it without building it.

## Scope and Acceptance

- [x] Category then Business Type then registered Business. Each Type belongs to exactly one Category. Industry (PSIC section) stays a label on the Type, not a tree level. Categories replace Focus Tags.
- [x] One migration renamed `focus_tags` to `business_categories`, added `business_types.business_category_id` (backfilled, then not null) and dropped the pivot. A second adds an index on `lower(business_name)`. A starter taxonomy of 13 categories is seeded: Boarding House is under Lodging and Rentals, Hotel or Inn under Tourism.
- [x] Models, actions, requests, routes, Data, analytics dimension, colors (`--category-N`, `CategoryChip`) and activity events renamed. Category sales stay bands for every audience (P4 wording updated only).
- [x] Business Categories page has Categories and Business Types tabs, each sorted, searched and paged locally, and a required Category select on a type.
- [x] Read-only Businesses directory for all three roles: public-class fields only, server-paged, filterable, sortable, empty while simulated records are off (D37). Registered counts link into it.
- [x] Tests: taxonomy, counts, analytics bands, directory paging, constant query count, and a sentinel proving no owner, contact or sales value reaches a response.
- [x] Documentation: D39, `privacy-rules.md`, `metrics.md`, `database.md`, `design.md`, `frontend.md`, `mvp-scope.md`, `roadmap.md`, analytics guide, CLAUDE.md, frontend rules and skills.
- [x] The deferred registry plan is written and linked.

## Deferred or Excluded

- Adding, editing and importing businesses, the business record view, permits, documents, BIR, computation, address entry and the Prepare, Review and Publish workflow: [Business Registry Records](../active/26-10-28-business-registry-records.md).
- Removing categories or types, a citizen suggestion queue, and `pg_trgm` contains-search.
- Any change to sales privacy beyond the P4 wording.
- Not browser-checked: the Businesses directory with rows (the dev database has simulated records off, so only its empty state was seen; rows are covered by Pest), the directory at 375px, light mode, and the Edit forms. Staff access is covered by Pest only.
- Existing databases keep their old type names and categories until the migration and `php artisan db:seed --class=BusinessTypeSeeder` run. Both were run on the local database only.

## Decisions and Sources

- "26-10-28", model: one Category per Type, replacing Focus Tags. User choice over keeping tags or adding Industry as a level.
- "26-10-28", names tier: a read-only directory now, entry in the registry plan. User choice.
- "26-10-28", privacy: a Category partitions businesses but crosses industries, so a category total minus an industry total could isolate an unchecked group. Category sales stay bands (P4). No privacy rule is loosened.
- "26-10-28", starter taxonomy: a seed, not a fact. Admins can rename, recolor and add categories, and the data member reviews it. Hotel or Inn under Tourism and Restaurant, Coffee Shop and Carinderia under Food and Beverage are the author's choices, not the user's.
- "26-10-28", registry boundary: records, not processing (user choice), recorded in `mvp-scope.md`.
- Supersedes the Focus Tag parts of D36 and D38 through D39 in [decisions.md](../../engineering/decisions.md).

## Change History

| Date | Change | Reason / Reference |
| :- | :- | :- |
| "26-10-28" | Plan created and approved | User correction after [Business Categories Revamp](26-10-28-business-categories.md) |
| "26-10-28" | Schema, backend, analytics dimension, categories page and directory built; documentation and skills updated | The agreed plan |
| "26-10-28" | `BusinessTypeSeeder` runs `BusinessCategorySeeder` itself | The simulated seeder test seeded types without categories and failed |
| "26-10-28" | `dimension=tag` became `dimension=category` in the public economy test | The retired value is no longer valid |

## Verification and Handoff

- Full Pest suite: 304 tests passed (2,083 assertions). New: `BusinessCategoriesTest` (taxonomy, counts, validation, the database refusing a type without a category), `BusinessDirectoryTest` (7 tests: public fields only with owner, contact and sales sentinels, filters, sorting, paging, constant query count, simulated gate, roles), `BusinessTaxonomySeederTest` (every type under one category, Boarding House not Tourism, Admin renames survive reseeding), and the renamed analytics privacy tests.
- `composer test:categories` (8), `test:analytics` (7) and `test:maps` (3) pass. PHPStan zero errors, Pint, `npm run check`, `npm run types:check` and `npm run build` pass.
- Browser as the local demo Admin: the Categories tab shows colored chips and "Not available" counts (simulated records are off); the Business Types tab sorted by category and paged with zero application requests; Boarding House is under Lodging and Rentals; the directory shows its empty state; the types page has no overflow at 375px.
- Design and privacy checks: no dashes, middots, raw colors or raw type classes in the new files. The directory reads and sends no owner, contact, size, employee or sales field, passes Data objects only, and reads no business table while simulated records are off.
- Prepared for the registry, not built: the type to category foreign key, UUID public ids, the directory's public-only Data object with a sentinel test, the reserved `capitol.businesses.show` name, and the planned tables documented in the registry plan and `database.md`.
- Committed on `feat/analytics-clarity` on "26-10-28" together with the rest of that day's work, as one commit (see `git log`). Not pushed, and no pull request is open yet.
