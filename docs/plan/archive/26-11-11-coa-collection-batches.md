---
title: "COA Collection Batches"
created: "26-11-11"
updated: "26-11-11"
status: completed
branch: "chore/maintainability-refresh"
base: staging
predecessor: "../archive/26-10-24-government-finances-economic-context.md"
---

# COA Collection Batches

## Objective

The user requested checks after every 12 downloaded PDFs, an immediate checkpoint for each download, isolated failures, and faster processing of large collections on "26-11-11".

## Scope and Acceptance

- [x] Tracked collection command accepts browser completion/failure events and scans existing downloads.
- [x] Durable per-file checkpoints, batches of 12 and an explicit final flush survive restarts.
- [x] Bad files are recorded individually; other files in the batch finish.
- [x] Unchanged verified files are reused; local validation uses bounded workers.
- [x] Tests cover batch boundaries, restart, failures, retries and changed files.
- [x] Existing local helper entry points and documentation lead to the tracked workflow.

## Deferred or Excluded

No live COA downloads, database imports, publication, new dependencies or financial sign-offs. Browser download pacing remains sequential with the previously recorded 3.5-second minimum. The original browser automation source was not preserved; the new command handles completed downloads rather than impersonating that browser API.

## Decisions and Sources

"26-11-11": Continue from the [original collection](../archive/26-10-24-government-finances-economic-context.md). Keep downloaded sources separate from committed finance inputs. Use existing pdfplumber for local PDF structure checks, bounded threads for independent files, and an append-only event journal instead of rewriting the full manifest after each file. Browser commands must run sequentially against one journal.

## Verification and Handoff

Final collection suite: **174 passed in 6.14 seconds**. `git diff --check` passed. No live downloads, imports, commits or pushes were performed.

"26-11-11": Added `composer data:finances:collect`, an fsynced JSONL journal with OS writer locking and interrupted-tail recovery, batches of 12, up to four local validation workers, explicit flush/retry/recheck, cached stat/metadata identities and per-year manifests. Full inventories include missing/failed downloads; successful manifests contain verified files only. Reports refresh at batch boundaries to avoid per-file full-manifest rewrites.

The two ignored local helper files were updated as compatibility entry points. The organizer reuses the pinned validator, avoiding its previously unavailable pypdf dependency. These local edits are not distributed by Git; portable behavior is in `tools/coa/coa/collection.py` with the usage guide at `tools/coa/COLLECTION.md`.

Verification: original full Python suite 107 passed before adding corpus coverage; collection suite checks all 158 stored PDFs, batch boundaries, restart, failures, retry/recheck, journal locking, manifests, malformed inventories and the Composer runner's repository-relative paths. The corpus check caught indirect content-stream references; the validator now resolves them before decoding. Local organizer smoke test recorded one corrupt PDF and copied the valid PDF without aborting. Composer command help and local helper compilation passed. A 12-file local sample with expected SHA-256 values passed with zero failures: one worker 0.239 seconds, four workers 0.217 seconds, cached resumes 0.010 to 0.011 seconds. These are one local sample, not network throughput claims. Initial sandbox pytest temporary-directory and PHPStan configuration reads failed; tests passed with a fresh repository-local temporary directory. The escalated `composer ci:check` retry passed formatting/lint, TypeScript, 30 frontend tests, Pint, PHPStan and all 383 Pest tests (2,844 assertions). No frontend assets changed, so a production build was not required.
