---
title: "COA 2025 Income and Expenses Verification"
created: "26-11-12"
updated: "26-11-12"
status: completed
branch: "chore/maintainability-refresh"
base: staging
predecessor: "../archive/26-11-12-coa-accuracy-and-differences.md"
---

# COA 2025 Income and Expenses Verification

## Objective and Authorization

"26-11-12": The owner requested checking the remaining 32 Income and Expenses figures against the stored COA PDFs and finishing verification so 2025 covers all 29 municipalities/city. This authorizes this specific owner-directed PDF review batch, following the earlier Tuguegarao exception to D35. Do not describe it as independent human review or two-report corroboration under D45.

## Acceptance

- [x] Inspect the cited PDF pages for all 16 municipalities, including labels, year columns, units and amounts.
- [x] Preserve a per-municipality evidence table and source hashes; resolve any discrepancy before signing off.
- [x] Publish only the 32 checked 2025 actual Income and Expenses figures through the existing ledger import and sign-off path.
- [x] Confirm 29/29 published coverage for Income, Expenses and derived Net Income, with no changes to existing values or unrelated reviews.
- [x] Preserve sign-offs in reviews.csv and pass repository CI and diff checks.

## Exclusions

No parser, metric, UI, schema or package changes. No other fiscal years or finance lines are approved by this batch. No commit, push or deployment.

## Verification

Initial database: 29 registered 2025 Executive Summaries, 13 verified and 16 pending Income/Expenses pairs.

`composer data:finances` completed with 384 documents and 2,670 parsed figures: 0 new, 0 values changed back to Pending, 0 removed. Exactly 32 figures gained verification; the verified count is now 1,255. `php artisan finances:export` saved 2,340 sign-offs and 0 manual figures. The reviews.csv diff is exactly 32 additions, with no existing rows removed or changed.

Before/after SQL fingerprints of all 2,670 finance figures agree: values and source provenance `f0f2fc82922d9b3a73643da0ed2579b0`; all non-target figures including their review metadata `44ab498be6316a40f99e6efe6619cdb6`. Timestamps are excluded. No existing amount, citation, status or review note was changed outside the 32 target figures.

Final SQL confirms 29 Verified Income and 29 Verified Expenses figures for fiscal year 2025, with none Pending on those two lines. `stats_finance_year` has 29 reporting towns out of 29 for all three published metrics:

| Metric | Sum of Reporting Towns PHP |
| :- | -: |
| Income | 10121818234.39 |
| Expenses | 8931384849.99 |
| Net Income | 1190433384.40 |

The Home/Capitol map query and Analytics catalog read these same statistics. No frontend change or build is needed for this data-only batch. Full `composer ci:check` passed: formatting/lint, TypeScript, all 33 frontend tests, Pint, PHPStan with zero errors, and 481 Laravel tests with 3,517 assertions. The Laravel suite took 591.283 seconds after the local service recovery. `git diff --check` passed. No application code, source amounts or findings changed. No commit or push was made.

The first CI run passed frontend checks, frontend tests, Pint and PHPStan, then its test process exited without results as the local Docker engine became unavailable. The direct retry stalled during the outage. Restarted Docker Desktop and ran `composer db:up` successfully, without changing Docker files or deleting data. SQL after service recovery still confirmed 29/29 coverage for all three metrics. Stopped only the two stalled PHP test processes started by this chat and reran full CI against the healthy database.

## PDF Page Evidence

Reviewed "26-11-12" by the coding agent at the owner's explicit direction. All 32 ledger amounts match the rendered 2025 Financial Performance column to the cent. All columns are actual pesos, with no thousands/millions scaling. Each table explicitly identifies 2025 and the 2024 comparative column. Net Income below was independently transcribed from the images as an arithmetic cross-check; it is derived by M19 in the application and is not separately approved as a stored figure. Page numbers are PDF page indices (one-based); Aparri prints ii and the others print i.

| Municipality | Income PHP | Expenses PHP | Net Income PHP | PDF Page |
| :- | -: | -: | -: | :- |
| Abulug | 235214818.90 | 207388786.09 | 27826032.81 | [page 1](https://www.coa.gov.ph/download/9032/cagayan/104145/abulug-executive-summary-2025.pdf) |
| Allacapan | 271508545.87 | 259606702.37 | 11901843.50 | [page 1](https://www.coa.gov.ph/download/9032/cagayan/104146/allacapan-executive-summary-2025.pdf) |
| Aparri | 388606216.64 | 381876056.60 | 6730160.04 | [page 2](https://www.coa.gov.ph/download/9032/cagayan/104214/aparri-executive-summary-2025.pdf) |
| Ballesteros | 202037512.04 | 185715792.72 | 16321719.32 | [page 1](https://www.coa.gov.ph/download/9032/cagayan/104149/ballesteros-executive-summary-2025.pdf) |
| Buguey | 219169697.02 | 196598582.68 | 22571114.34 | [page 1](https://www.coa.gov.ph/download/9032/cagayan/104217/buguey-executive-summary-2025.pdf) |
| Calayan | 272413038.35 | 237270144.54 | 35142893.81 | [page 1](https://www.coa.gov.ph/download/9032/cagayan/104222/calayan-executive-summary-2025.pdf) |
| Camalaniugan | 187828643.63 | 167842641.71 | 19986001.92 | [page 1](https://www.coa.gov.ph/download/9032/cagayan/103812/camalaniugan-executive-summary-2025.pdf) |
| Claveria | 230137431.32 | 201769845.71 | 28367585.61 | [page 1](https://www.coa.gov.ph/download/9032/cagayan/104065/claveria-executive-summary-2025-3.pdf) |
| Gonzaga | 370487142.12 | 332645167.36 | 37841974.76 | [page 1](https://www.coa.gov.ph/download/9032/cagayan/104226/gonzaga-executive-summary-2025.pdf) |
| Lal-Lo | 459266139.43 | 430119457.63 | 29146681.80 | [page 1](https://www.coa.gov.ph/download/9032/cagayan/103813/lal-lo-executive-summary-2025.pdf) |
| Lasam | 261644326.15 | 243250741.42 | 18393584.73 | [page 1](https://www.coa.gov.ph/download/9032/cagayan/104150/lasam-executive-summary-2025.pdf) |
| Pamplona | 188941423.43 | 161991617.10 | 26949806.33 | [page 1](https://www.coa.gov.ph/download/9032/cagayan/104067/pamplona-executive-summary-2025-2.pdf) |
| Sanchez Mira | 219190155.81 | 200562695.71 | 18627460.10 | [page 1](https://www.coa.gov.ph/download/9032/cagayan/104069/sanchez-mira-executive-summary-2025.pdf) |
| Santa Ana | 334416612.72 | 335322877.96 | -906265.24 | [page 1](https://www.coa.gov.ph/download/9032/cagayan/104228/sta-ana-executive-summary-2025-2.pdf) |
| Santa Praxedes | 132030057.33 | 99529650.68 | 32500406.65 | [page 1](https://www.coa.gov.ph/download/9032/cagayan/104071/sta-praxedes-executive-summary-2025.pdf) |
| Santa Teresita | 217347074.80 | 158613620.15 | 58733454.65 | [page 1](https://www.coa.gov.ph/download/9032/cagayan/103814/santa-teresita-executive-summary-2025.pdf) |

### Source Integrity

Every stored PDF SHA-256 matched its database source record before review. Rendered pages, page text extracts and the before-review CSV are retained locally in ignored `storage/app/source-review/coa/review-261112-2025/`. Canonical originals live under `data/towns/` at the paths registered in `data/towns/finances/documents.csv`.

"26-11-12" cleanup at the owner's request: removed the temporary `pending.json`, `approve_csv.py`, `review_sources.py` and their compiled Python cache. Preserved all 16 rendered page images, 16 text extracts, the before-review CSV, canonical COA PDFs, this evidence record and saved sign-offs. SHA-256 checks confirmed that retained local evidence and `reviews.csv` were unchanged. This file cleanup needs no application test rerun; the completed CI evidence above still applies.

| Municipality | SHA-256 |
| :- | :- |
| Abulug | `e4c3cbe638fbc8587723279ea239d55d4929226e634bff535903a7dd58697351` |
| Allacapan | `b9c3e32f9cdb3e5550a0ce9b53591d99e5df686aea07ae61ce59b9d36979d39c` |
| Aparri | `8e8a04c5eca1f4c800e58aeef6a9995bcee82fb57f37f3e7edf799e1d75c3ebf` |
| Ballesteros | `cfc4f8bd25c3ac0167f72c6feca8378747abd3236a0b806a163864467d8920fc` |
| Buguey | `d5a1d53cd854722d31d27868e31e86412350dbb075e9c61ddb18ef68e4084bbe` |
| Calayan | `5b3731889c70d0742a5b690517423cd8106f0c8b195e87461e1cadf4e34c2c35` |
| Camalaniugan | `73b8ec4dda319a97a98d63d2578ae7a397d6748ba76d9b6229e66f40be8b63c8` |
| Claveria | `c3319e7657da5ba1d84584af05696ba03dad9c230924cb6bc2f154c02873545a` |
| Gonzaga | `56b082bdc8d5864c583cbcf0e16698aaea2b666f153d81b77883668478217c08` |
| Lal-Lo | `80027e7b25323902efedbcf2ab284cee6ee89e85a3b743ecb3ac463b072332d1` |
| Lasam | `dbb557cd4d0e0c661ba42108748e94a92134061b3eb708e22c5dba360cdb3d36` |
| Pamplona | `eb4be9023cf734ccaef79f223046ce64dacad97249e1502f56ff2bba90998211` |
| Sanchez Mira | `7f622d814b1de21433de6633afc6763e02cb3f53209f8c7b8689fb27c999b60c` |
| Santa Ana | `d0b3e7ffb7b0ce2283822331c0f64fbb03a0c7fa54c3e67409dfddc6fd1d896f` |
| Santa Praxedes | `0abf98dc78a5a1ba930e1ac25820d0312eeb00a9f063311a8103a293c7658de4` |
| Santa Teresita | `297172a08b8df010110e65c229cd919cb5ae74f57cffd03ca120b6c6254431fb` |

### Existing Findings Retained

- Camalaniugan: the PDF prints 2025 Expenses 167842641.71 and 2024 Expenses 147580506.66, whose difference is 20262135.05; its change column instead prints 14901255.42. The printed Net Income change is 8098907.57 rather than 8118940.24. The 2025 Income less Expenses equals the printed Net Income 19986001.92 exactly. Approve the two absolute 2025 amounts as printed; retain both source-change warnings. Application changes are calculated under M26, not copied from COA's change column.
- Lal-Lo: the equity-change sign note concerns another line; Income and Expenses restatement notes concern the 2024 comparative column. Both 2025 amounts and their Net Income agree. No 2024 approval or source-issue changes in this batch.
- Santa Praxedes: restatement notes concern 2024. The 2025 amounts agree with the rendered page and produce its printed Net Income.
- Santa Ana: Expenses exceed Income by 906265.24, matching the report's parenthesized Net Income. Preserve the loss.

The remaining twelve reports have no Executive Summary findings in the 2025 issues file. No source amount, extractor result or finding is changed by this review.

