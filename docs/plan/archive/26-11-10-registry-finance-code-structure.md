---
title: "Registry and Finance Code Structure"
created: "26-11-10"
updated: "26-11-10"
status: completed
branch: "chore/maintainability-refresh"
base: f55a81b3f95b4373fdfd691df7207acf60a2feae
predecessor: ../archive/26-11-09-repository-structure-follow-up.md
---

# Registry and Finance Code Structure

## Objective

Continue the [Repository Structure Follow-Up](../archive/26-11-09-repository-structure-follow-up.md) with focused changes to the frontend, models, queries, and parser service boundary.

## Scope and Acceptance

- [x] Colocate the Business Types table with its page. Keep local history, keyboard focus and edit actions.
- [x] Use one town boundary result for the business map and pin selection. Preserve manual barangay selection, server validation and map fallback.
- [x] Index town evidence by year and metric once per query. Preserve source order, deduplication and the public response.
- [x] Share the uploaded-file privacy check and give the upload record ownership of the stale-attempt predicate. Preserve download containment and parser retry behavior.
- [x] Limit Inertia route resolution to page entries, keep shared maps together, and defer hidden business maps and below-the-fold charts. Compare the production manifest and browser requests before and after.
- [x] Run the frontend checks, production build, relevant browser checks and full PostGIS CI.

## Deferred or Excluded

The Dashboard Atlas has a separate loading and retry workflow. Assigned-town scopes and finance metric calculations remain outside this pass.

## Decisions and Sources

- "26-11-10". The [Business Registry Records plan](../active/26-10-28-business-registry-records.md) defines pin selection and server validation. The [Live Finance Parsing plan](../active/26-11-08-live-finance-parsing.md) defines attempt ownership. The [report upload decision](../../engineering/decisions.md#d42-report-uploads-read-on-the-queue) keeps uploaded files private.
- "26-11-10". The build exposed 29 modules under the page directory for 20 route entries. The shared MapLibre chunk was already async, but the phone directory mounted it inside a CSS-hidden panel. Keep one renderer and start loading within 160 px of the viewport. [Inertia code splitting](https://inertiajs.com/docs/v3/advanced/code-splitting), [Vite async chunk loading](https://vite.dev/guide/features#async-chunk-loading-optimization), [React lazy loading](https://react.dev/reference/react/lazy), and [Intersection Observer](https://www.w3.org/TR/intersection-observer/).

## Change History

| Date | Change | Reason |
| :- | :- | :- |
| "26-11-10" | Moved the Business Types table and the Home and Dashboard atlases beside their pages. Grouped reusable maps and the MapLibre worker setup in `components/map/`. | Keep local features with their owners and use one shared renderer setup. |
| "26-11-10" | Indexed finance evidence, shared the private upload path rule, and moved stale-attempt detection to the upload record. | Remove repeated work and make the privacy and retry boundaries easier to trace. |
| "26-11-10" | Restricted Inertia page discovery to `index.tsx` and deferred the hidden map and offscreen charts. | Avoid loading heavy visuals before they are needed. |

## Verification and Handoff

- The production client and SSR builds passed. The manifest exposes 20 page modules, down from 29. The Business Categories static JavaScript graph went from 28 to 26 assets. The phone directory added one small static dependency, while its static JavaScript fell from 632 to 630 kB.
- At 375 px, the baseline requested the business map, renderer, styles, and worker on the list. The new build deferred all four until Show Map. These files total 1.70 MB uncompressed in the baseline. Reopening the map and navigating between Businesses and Economy with Back and Forward produced zero repeat JavaScript asset requests in the same public tab. The map also rendered at 1280 px. The Financial Condition chart loaded when scrolled into range.
- The Business Categories sort retained keyboard focus and an Edit action opened the correct sheet. The Staff demo did not expose the business entry form, so dropping a pin on that form was not verified in the browser.
- Full `composer ci:check` passed with 30 frontend tests and 378 PostGIS tests. The finance evidence regression covers citations from two towns and excludes an adjacent fiscal year. Existing tests cover private upload links and retry ownership.
- The renderer remains 1.11 MB minified (301 kB gzip) and the worker 518 kB. Vite still reports the chunk size warning. The access trace measures requests and assets, not production page speed.
