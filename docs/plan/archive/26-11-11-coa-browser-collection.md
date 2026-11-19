---
title: "COA Browser Collection"
created: "26-11-11"
updated: "26-11-12"
status: completed
branch: "chore/maintainability-refresh"
base: staging
predecessor: "../archive/26-11-11-coa-collection-batches.md"
---

# COA Browser Collection

## Objective

On "26-11-11" the user requested automatic browser-download checkpointing and validation every 12 PDFs, with a live Executive Summary collection test for 2014 through 2019. Geographic scope continues as Cagayan's 28 municipalities and Tuguegarao City.

## Scope and Acceptance

- [x] Count existing registered reports and physical files before collecting.
- [x] Connect completed browser saves to checkpoints without per-file user commands.
- [x] Check groups of 12, flush the last partial batch, and retain individual failures.
- [x] Collect missing 2014 through 2019 Executive Summaries from observed COA links; preserve exact source URLs and document listed gaps.
- [x] Verify collected files and record actual counts and test results.

## Deferred or Excluded

No imports, financial sign-off, metric changes, publication, ZIP collection, new packages, commits or pushes. No assumption that a report is unavailable simply because browser access failed. New sources remain in ignored collection storage until reviewed.

## Decisions and Sources

"26-11-11": The registered source CSV and physical collection agree: 2020 to 2024 have 29 PDFs each, 2025 has 13, and 2014 to 2019 plus 2026 have none. A missing local 2026 file does not establish COA publication status. The live listing currently displays Cloudflare's security-verification page. The user has been asked to complete any required browser verification while independent tooling work continues.

## Verification and Handoff

Completed "26-11-12". The prior batch collector remains documented in the [completed plan](../archive/26-11-11-coa-collection-batches.md).

The listing's security verification completed while tooling work continued. Browser collection then used the observed year, Region II, municipality and city folders. Each popup was bound to its file ID and actual PDF URL. Downloads were sequential with a 3.5-second minimum between starts. The collector's new `watch` command ran alongside each browser queue, checkpointing stable saves, checking batches of 12 and flushing the final partial batch when the agent created a finish marker. No user per-file commands were needed.

All six requested years have 29 PDFs and 29 distinct canonical municipality/city identities: **174 PDFs, 747 pages, 37,949,540 original PDF bytes**. Every file passed PDF structure and rounded displayed-size checks, and has an exact byte count and SHA-256. Every archived copy and ZIP member matched its recorded SHA-256. There are no final download, validation, copy or coverage gaps. New source PDFs and manifests remain in ignored `storage/app/source-review/coa/browser-collection/<year>/`; they have not been imported or published. The combined PDF-only source bundle is `executive-summaries-2014-2019.zip` in that collection root (37,507,046 bytes; SHA-256 `9a37fe785de4819ce354f8664750a5b0e52c946ddc0b6d600b651ca04372a324`).

Source exceptions preserved with page evidence:

- Sanchez Mira 2019 is listed as Annual Audit Report, but its three-page PDF begins EXECUTIVE SUMMARY and identifies the municipality and audit year 2019.
- Santa Ana 2018 is listed as Executive Report, but its four-page PDF begins EXECUTIVE SUMMARY and identifies the municipality and audit year 2018.

One generic popup lookup initially returned Tuguegarao City's adjacent 2019 annual-report ZIP. It was excluded from the source collection and bundle, and recorded in the local `2019/excluded-downloads.json`; the correct summary PDF was subsequently downloaded and checked. File-ID-bound URL guards prevent that transition race. Repeated tree selection shifts, filename accents/punctuation and rounded sizes are recorded in `.claude/rules/coa-collection.md`.

Verification: **184 collection tests passed**, including all 158 previously stored PDFs, automatic watching in a separate process, partial browser transfers, batch boundaries, finish and timeout handling, malformed inputs, rounded sizes and unique/ambiguous normalized filenames. The full `composer ci:check` run passed formatting/lint, TypeScript, 30 frontend tests, Pint, PHPStan and 383 Pest tests (2,844 assertions). No frontend assets changed, so a build was not required. Source-copy and bundle checks independently confirmed all 174 SHA-256 values and ZIP CRCs. No financial figures were signed off, imported or published. No commit or push was performed.

Existing registered collection remains 29 PDFs per year for 2020 through 2024, 13 for 2025 and none for 2026. COA's inspected year list ended at 2025; this is an observation of that listing, not a claim about all possible publication locations. With the new source-review collection, complete local source coverage now spans 2014 through 2024; the 2025 register is still partial.
