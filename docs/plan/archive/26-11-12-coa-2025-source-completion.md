---
title: "COA 2025 Source Completion"
created: "26-11-12"
updated: "26-11-12"
status: completed
branch: "chore/maintainability-refresh"
base: staging
predecessor: "../archive/26-11-11-coa-browser-collection.md"
---

# COA 2025 Source Completion

## Objective

The user requested checking COA for the remaining 2025 Executive Summaries and downloading missing PDFs with the same automatic checkpoints and checks every 12 files on "26-11-12".

## Scope and Acceptance

- [x] Compare the current official Cagayan municipality and city listing against the registered 13 summaries.
- [x] Download missing official PDFs sequentially with the existing watcher, checkpoints and batches of 12.
- [x] Verify the final partial batch, copied sources, canonical geographic coverage and bundle checksums.
- [x] Preserve exact URLs and source metadata and report remaining gaps.

## Decisions and Sources

"26-11-12": The live listing contains 29 Executive Summaries for 2025. Thirteen file IDs match existing registered sources; 16 are missing locally. Continue the [historical browser collection](../archive/26-11-11-coa-browser-collection.md) workflow with exact popup IDs, official PDF URL guards, sequential download spacing and the existing folder watcher. New PDFs remain in ignored source-review storage; do not import finance figures or alter the application source register.

## Deferred or Excluded

No database import, financial sign-off, publication, ZIP report downloads, package changes, commits or pushes. Preserve existing files and unrelated uncommitted work.

## Verification and Handoff

Completed "26-11-12". Downloaded 16 missing PDFs: Abulug, Allacapan, Aparri, Ballesteros, Buguey, Calayan, Camalaniugan, Claveria, Gonzaga, Lal-Lo, Lasam, Pamplona, Sanchez Mira, Santa Ana, Santa Praxedes and Santa Teresita. All observed source URLs and original titles are retained. Existing collector code is unchanged.

The watcher saved a durable checkpoint for each browser save and validated the first 12 automatically. The final four passed on the finish-marker flush. All 16 passed PDF structure and rounded COA size-label checks, with exact saved byte counts and SHA-256 recorded. No transfer or final validation failures occurred. New PDFs total 3,391,261 bytes.

Reused the 13 registered originals after checking their recorded byte counts and SHA-256 values; none were downloaded again. Copied all 29 into ignored `storage/app/source-review/coa/browser-collection/2025/pdfs/<municipality>/`. Final coverage is 29 PDFs, 147 pages, 29 distinct canonical municipality/city PSGC identities and no missing towns. All copied sources and ZIP members matched their expected SHA-256; ZIP CRC checks passed.

The complete local bundle is `storage/app/source-review/coa/browser-collection/2025/executive-summaries-2025.zip` (6,221,665 bytes; SHA-256 `41001f02ed42581bdb9fefb4ef594401aaf0dd544b2b8c5db9a177645a9bd002`). `sources.csv`, `source-manifest.json` and `coverage.json` accompany it. `inventory-missing.json` and `checkpoint.jsonl` retain the observed missing-file inventory and automatic processing history.

Only source artifacts and plan/roadmap documentation changed in this follow-up. No application or collector code changed, so application tests/builds were not repeated; the substantive checks were source structure, size, SHA-256, geographic coverage and bundle CRC/member verification. The prior collector test and CI evidence remains in the predecessor plan. `git diff --check` passed. No database imports, publication, sign-offs, commits or pushes occurred. The registered application CSV still contains 13 entries for 2025; the additional 16 remain in source-review storage for later ingestion. Complete local source coverage now spans 2014 through 2025.
