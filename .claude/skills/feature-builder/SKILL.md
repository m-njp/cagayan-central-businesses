---
name: feature-builder
description: Build one product feature (C1 to C14 on the Capitol side, P1 to P5 on the public side) as a small, tested vertical slice, privacy first.
argument-hint: "[feature id, e.g. C3 or P2]"
arguments: [feature]
disable-model-invocation: true
---

# Build feature $feature

One feature, one branch, one pull request. Build only what $feature needs.

## Read first

1. `docs/product/mvp-scope.md`: the entry for $feature and the "Demo walkthrough" step that uses it.
2. `docs/engineering/privacy-rules.md`, if the feature shows business data. It almost always does.
3. `docs/engineering/metrics.md`: every metric the feature shows.
4. `docs/engineering/database.md`: the tables and the visibility class of every field it touches.
5. `docs/engineering/architecture.md`, `frontend.md`, and `design.md`.
6. `docs/team/roadmap.md`: the feature's status.

## Steps

1. **Branch.** If on `main` or `staging`, create a work branch inside `staging`: `git switch staging`, `git pull --ff-only`, `git switch -c feat/<id>-<short-name>` (`docs/team/workflow.md`).
2. **Apply `/plan-history`.** Resume the matching active plan or create an agreed plan under `docs/plan/active` using `yy-mm-dd`. Record verification during implementation; archive only when acceptance checks pass. **Restate the feature** as three to six acceptance checks a person can verify by clicking, written from the user's point of view. If the product document is unclear or two docs disagree, stop and ask.
3. **Check dependencies.** Every table must exist in `database/migrations/` and every metric in `database/sql/metrics/`. If one is missing, stop and name the skill to run first (`/db-change ...` or `/metric-builder M5`). A metric built inside a feature hides it from review.
4. **Look for what exists.** Search `app/Queries`, `app/Data`, `app/Services`, `resources/js/components`, and `components/ui` for pieces to reuse. List them in the plan.
5. **Propose the slice**, then wait for approval:
   - route name and URL (`capitol.*` in `routes/capitol.php`, or `public.*` in `routes/public.php`)
   - controller and method, with a Form Request if there is input or filters
   - Query (read) or Action (write), and any Service it uses
   - Data objects with every field listed. Separate public and admin objects. Sales as guarded figures.
   - the page path in `resources/js/pages/` and the few components it needs, reused or new
   - rate limit, if strangers can repeat the action
   - the tests, by name
   - which parts are backend and which are frontend, so the two developers can split the work (`docs/team/workflow.md`, "How frontend and backend split a feature")
   Keep it small. No extra layers, options, or components the feature does not need now.
6. **Build in this order**, so each step proves the one before:
   1. Server tests in `tests/Feature/Capitol/` or `tests/Feature/Public/`: the happy path, guests rejected on Capitol routes, and the privacy cases (hidden figure with its reason, admin fields absent from public responses, a sentinel sales value absent everywhere, a filter that shrinks a group below the threshold). Watch them fail.
   2. The back end until they pass. Thin controller, eager loading, aggregates from `stats_` tables.
   3. The page with typed props matching the Data objects.
   4. The states: loading, empty, not available, hidden, error, with the exact words from `privacy-rules.md`.
   5. The "Simulated data" label, from the shared layout.
7. **Check the UI** against `design.md`: no em dashes, middots, or title separators, no gradients, tokens only, light and dark mode, phone width for public pages. Or run `/design-check`.
8. **Run** `php artisan test --compact`, `npm run check:fix`, and `composer lint`. The hooks run the rest on commit and push.
9. **Privacy review.** Run the `privacy-reviewer` agent on `git diff origin/staging...HEAD`. Resolve every finding, or explain why one does not apply.
10. **Update `docs/team/roadmap.md`:** $feature to `Done`, or `In progress` if parts remain.
11. **Finish with the manual check:** the URL, which account (or none for public pages), each click, and what the person should see, including where a hidden figure appears in the demo data.

## Done when

Every acceptance check passes, the privacy tests exist and pass, the UI follows `design.md`, the privacy reviewer has no open findings, the roadmap is updated, and the person has the click-by-click check. Then suggest `/commit`, which also prints the pull request description.
