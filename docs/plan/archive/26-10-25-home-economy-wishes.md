---
title: "Home Economy and Wishes"
created: "26-10-25"
updated: "26-10-25"
status: completed
branch: feat/cagayan-economic-performance
base: staging
predecessor: ../archive/26-10-25-local-economy-selections.md
---

# Home Economy and Wishes

## Objective

Help residents share wishes for their town and explore its economy from Home. The user explicitly requested replacing What You Can Do, removing placeholder cards and the protection section, combining public wish types/specific labels, removing tables and specified dangling copy, improving large ranked lists, and adding town context to wish tooltips. Apply ui-builder, plan-history, design-check, Inertia/privacy and testing guidance.

## Acceptance

- [x] Home offers working wishes and Economy destinations, preserving selected town; obsolete placeholder/protection sections and unused props/imports removed.
- [x] One backend-ranked wish snapshot combines curated type/specific labels without summing overlapping respondents. No kind filter, finance/table view or wish-table branch remains.
- [x] Remove specified descriptions and cloud/table fallback prose. Preserve source/date metadata through an explicit source tooltip and financial values through accessible non-table content.
- [x] Ranked bars wrap full labels, show exact counts, use bounded scroll for long lists, and expose town/count tooltips to keyboard/pointer/touch. Cloud and unplaced labels retain equivalent counts and tooltips.
- [x] Keep local URL selections, Back/Forward, refresh and zero-request loaded-data interactions. Retain demo/privacy isolation and unavailable towns.
- [x] Add a durable no-dangling-copy rule to UI skills and canonical docs. Complete code/documentation before final tests, lint/types/builds and phone/desktop browser review.

## Decisions and Limits

- 26-10-25: This explicit correction supersedes prior visible table alternatives, separate public wish modes, date captions, metric disclaimers and Home placeholder/protection sections. Keep policy/metric limitations in documentation. Do not alter wish submission fields or private/public boundaries.
- 26-10-25: Combine existing curated labels as distinct entries with namespaced IDs, original counts, descending count/label/ID tie order. This is a combined ranking, never a sum of unique respondents. Raw submissions remain excluded.
- 26-10-25: Tooltip location is actual dataset town, not an invented most-demanded location. Only the Tuguegarao fixture is available; comparative geographic rankings remain deferred.
- 26-10-25: No new packages, source collection, live classification, predictions, commits or deployment. Preserve prior archived decisions as history.

## Verification

- Implementation, regression coverage and documentation were ready before final verification started. During final verification PHPStan identified an unnecessary nullable fallback for a town already guaranteed by the catalog branch; corrected it and reran the full check.
- `composer ci:check` passed: **151 tests, 951 assertions** on PostGIS, frontend format/lint, TypeScript, Pint and PHPStan (zero errors). Combined labels retain original counts, deterministic ties, namespaced identities even for duplicate phrases, all 120 long labels beyond the cloud limit, exact financial cents and changes, unavailable isolation and private-field exclusion. Invalid table deep links are rejected; retired kind/financeView keys are ignored.
- `npm run build:ssr` passed production and SSR builds. Existing map chunk-size and Inertia SSR sourcemap warnings remain non-blocking. No dependency changes in this correction.
- Browser verified Home's two live action cards, removal of the placeholder/protection sections, valid selected-town propagation into both Wayfinder destinations, and the Economy destination. Phone Home checked at 375px in light appearance; desktop Home checked in dark appearance. Economy ranked bars checked desktop/phone dark; cloud and fallback checked phone light. No horizontal overflow: document width/content width both 360px inside the 375px viewport.
- All 26 combined entries were present in the ranked list. At phone width the cloud placed 7 phrases and the fallback rendered the remaining 19 count chips, preserving complete coverage without captions or table references. Desktop cloud also retained every omitted phrase through chips. No table elements remain on the page. No client runtime errors in final preview diagnostics.
- Keyboard Enter opened cloud and ranked-row tooltips with the exact phrase, Tuguegarao City and count; Escape dismissed them. Ranked bars retain complete labels above the bars, exact visible counts and a bounded 480px scroll region. Source snapshot period/date remain available from source details rather than a visible caption.
- A temporary Laravel server on port 8001 provided access-log evidence. After the initial page request, cloud/bar view switches, period selection, town selections and browser history produced zero additional application requests. A separate focused navigation began the phone review; subsequent view changes, tooltip actions, Back and Forward again made no new page requests. Back restored bars, Forward restored cloud with the same period and town.
- Existing animation-free charts and reduced-motion tooltip/skeleton classes were reviewed; no OS motion preference changed. Empty/unavailable/visualization-error paths retain concise recovery feedback. Comparative town leadership remains unavailable because only Tuguegarao has simulated data; tooltips make no invented geographic ranking.
- Cleanup found no retired table branches, kind/financeView controls, specified dangling strings, Home placeholder imports/arrays or unused smallGroupMin prop. ui-builder/design-check and CLAUDE now expressly prevent recurring description/caption/fallback prose. Policy and metric limitations remain in canonical documentation.
- Original appearance restored, temporary viewport reset, Home preview left open. No commit, push, deployment or source collection. Original archived plans preserved. `git diff --check` and documentation link checks completed.
