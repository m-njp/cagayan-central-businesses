---
name: demo-check
description: Prove the ten-step demo walkthrough works end to end before recording the video and before the booth.
disable-model-invocation: true
---

# Demo check

**Role: Release verifier.** Own the walkthrough and evidence on an explicitly designated disposable demo database. Confirm the target before a reset. Record each failed step and the actual behavior, including the role and viewport where relevant.

The video and the booth both follow the ten-step "Demo walkthrough" in `docs/product/mvp-scope.md`. This check proves each step works on a freshly reset database, the way a clean laptop runs it. Run it on `staging` every Friday and before a release, and on `main` after the release, before recording.

## Steps

1. **Reset the designated disposable demo database:** `composer db:reset` (fresh migrate, seeder with its fixed seed, `metrics:refresh`). If it fails, stop: nothing after this means anything.
2. **Build:** `npm run build`. The booth laptop may run without Vite.
3. **Doctor:** `composer doctor`. Every check must pass.
4. **Read** the walkthrough in `docs/product/mvp-scope.md` and the seeder requirements in `docs/engineering/database.md`.
5. **For each of the ten steps, check two things:**
   - **The route responds.** Find it with `php artisan route:list --except-vendor`. Capitol steps as the demo administrator (`DEMO_ADMIN_EMAIL` in `.env`), public steps logged out. Expect 200, not 404, 500, or a redirect to login.
   - **The data the step needs exists:**
     - Step 1: headline numbers and the "Simulated data" label
     - Step 2: Tuguegarao shaded by sales, rankings with movers
     - Step 3: a dominance panel with a leading type
     - Step 5: a barangay with sales hidden by the small-group rule
     - Step 6: an outlook range with backtest error, and at least one early warning
     - Step 7: wishes beside peer comparison, a full record with owner contact (and a new view log row), a PDF brief
     - Step 8: public sales as bands only, a listing with no owner fields
     - Step 9: submitting a wish increases the count
     - Step 10: a coffee shop checklist with verified sources, and greyed-out towns
     Use a read-only query or `php artisan tinker --execute '...'` for the data checks.
6. **Report pass or fail per step** in a table: step, route, result, and for each failure the reason and the missing feature or seeder requirement by id (C9, seeder item 6).

## Done when

All ten steps have a pass or fail with a reason, on a database reset in this run. A step whose feature is not built yet counts as fail, with the feature id named.
