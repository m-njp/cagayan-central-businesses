---
name: privacy-check
description: Use before any commit or pull request whose diff touches controllers, Data objects, Queries, exports, pages, or queries over business data. Walks docs/engineering/privacy-rules.md rule by rule against the diff and reports findings.
argument-hint: "[optional base branch or file list]"
---

# Privacy check

**Role: Privacy reviewer.** Trace every protected value from source through serialization, downloads, logs and errors. Verify the server boundary, including indirect callers. Report concrete disclosure paths and distinguish observed evidence from inference.

Scope given by the person, if any: $ARGUMENTS. Otherwise check the working tree and branch against `staging`.

With Inertia, everything a controller passes to a page reaches the browser. A leak in a prop is a leak, whether or not React shows it. The author of a change is the worst person to spot their own leak, so read the diff as a stranger would.

## Steps

1. **Read the diff:** `git diff origin/staging...HEAD` plus `git diff` for uncommitted work. List the changed files.
2. **Read `docs/engineering/privacy-rules.md` in full.** Walk every numbered rule against the diff. For each rule: holds, broken, or does not apply.
3. **Search for the usual leaks:**
   - a model, collection of models, or `->toArray()` / `->toJson()` of a model passed to `Inertia::render`, `inertia()`, `Inertia::share`, or the shared props in `HandleInertiaRequests`
   - any read of `declared_gross_sales` outside `database/sql/metrics/`
   - a sales figure not produced by the privacy guard, or a numeric sales value in a public Data object
   - an admin field (owner name, contact number, ownership type, size category, employee count) in a public route, Data object, or page
   - a full record view without a `record_view_logs` write, or a list view that writes one
   - a threshold written as a literal instead of `config('cagayan....')`
   - a new page or export without the "Simulated data" label
   - a sales amount, owner name, or contact number in a log call, exception, or validation message
   - a new filter without a test for the hidden case
4. **Run the privacy tests** if the folders exist: `php artisan test --compact tests/Feature/Privacy tests/Arch`.
5. **Report findings by rule id,** each with file, line, what is wrong, who could see what, and the smallest fix. With no findings, list each rule with "holds" or "does not apply".
6. **Fix only when asked.**

## Done when

Every rule every numbered rule has a verdict for this diff, the privacy and architecture tests have run, and each finding has a file and line.
