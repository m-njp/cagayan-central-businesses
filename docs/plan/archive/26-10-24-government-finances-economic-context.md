---
title: "Government Finances and Economic Context"
created: "26-10-24"
updated: "26-10-27"
status: superseded
branch: null
base: staging
predecessor: null
---

# Government Finances and Economic Context

## Objective and Status

Retain clearly labeled government revenue alongside economic activity, household conditions, business information, and community interests. The project remains a business-information platform, not an auditing system or an investment-recommendation service. Document gathering and historical-screen implementation are explicitly deferred to another session.

## Future Scope

- [ ] User collects COA fiscal-year 2021–2026 reports, when published, and selected BOI/PSA originals.
- [ ] Verify each extracted measure against the original statement and notes, recording exact URLs, LGU/year, pages or cells, units, calculations, restatements, and audit qualifications.
- [ ] Build client-facing history of comparable government figures with absolute and percentage changes. Missing/non-comparable years and a zero prior-year percentage denominator remain unavailable.
- [ ] Add appropriate verified economic and household context at its actual source geography and period.

## Decisions and Sources

- 26-10-24: COA records the reporting government's finances. Government revenue is not private-business sales, GDP, or a good/bad economy score. National transfers also affect it.
- 26-10-24: Province GDP must not become a town/barangay figure. Full-year 2026 actuals require completion of the year and release of the relevant report.
- 26-10-24: The supplied Tuguegarao Executive Summary 2025 contains financial highlights and audit findings; project statements describe their reporting period, not necessarily current conditions.
- Definitions: [product clarification](../../product/mvp-scope.md#october-24-2026-clarification-revenue-and-economic-status), [planned metrics](../../engineering/metrics.md#government-finances-approved-26-10-28), and [roadmap](../../team/roadmap.md#future-phase-historical-local-government-finances).
- Original-download folders are under ignored `storage/app/source-review/`; no reports or credentials are embedded in the register.

## Change History

| Date | Change | Reason / Reference |
| :- | :- | :- |
| 26-10-24 | Established this deferred record | Preserve the agreed direction while dashboard work continues independently |
| 26-10-27 | Collected 2020 to 2025 Executive Summaries into `data/towns/executive-summaries/` | User request; see "Executive Summary Collection Completed" |

## Handoff

No source-collection session or historical-screen implementation is authorized by completing the separate dashboard-foundation plan. Resume when the user provides the documents or requests this phase.


## 2025 Collection Authorized and Completed

26-10-27: The user explicitly resumed only fiscal-year 2025 source downloads for Cagayan within Region II, while personally collecting 2024. A later correction restricted downloads to ZIP archives. This supersedes the earlier blanket collection deferral only for that subset; financial extraction, verification and historical-screen work remain deferred.

- Downloaded 13 original 2025 ZIPs from observed official COA links: Alcala, Amulung, Baggao, Enrile, Gattaran, Iguig, Penablanca, Piat, Rizal, Solana, Santo Nino, Tuao and Tuguegarao City. Exact source spellings are retained in the local manifest.
- 16 municipalities had no ZIP listed in the inspected 2025 Cagayan folder. The inspected provincial folder had no Cagayan provincial-government report. Missing-source observations are recorded separately rather than filling gaps with another year.
- Files: ignored `storage/app/source-review/coa/2025/archives/`. Source URLs, dates, byte lengths, SHA-256, ZIP member names and integrity results are recorded in JSON/CSV manifests. All 13 archives passed full ZIP CRC checks, totaling 40,726,066 bytes. Nothing was imported into town_facts or the demo fixture.
- Standard HTTP requests returned 403; browser DOM inventory plus the documented browser file downloader succeeded. Two PDFs downloaded before the ZIP-only correction are excluded from the final archive set and separately recorded. A standard-library Python helper organizes and rechecks local originals without making network requests.
- Only local download/source artifacts and this dated plan record changed; no application code or database changed, so application tests/builds were not rerun. Archive verification is not financial-content verification. No commit, PR or deployment requested for this collection.


26-10-27 follow-up: User requested extraction into the project collection. Extracted all 13 authorized ZIPs into `data/towns/annual-audit-reports/2025/<town>/`, matching existing 2024 town-folder names and preserving AAR/AAPSI_APMT subfolders. All 169 extracted report files (42,917,425 bytes) passed per-file SHA-256 comparison with ZIP contents. Source manifests and a short collection README accompany them. ZIP paths were checked for traversal, absolute paths, symlinks, duplicate targets and conflicting existing files before writing. Original ZIPs and every 2024 file were preserved. This is source organization only, not financial-content verification or a database import.


## Historical ZIP Collection Resumed

26-10-27: User authorized the same ZIP-only download, extraction and provenance process for 2023, 2022 and 2021. The repeated 2022 in the request is treated as one year. Scope remains Cagayan's 28 municipalities and Tuguegarao City within Region II, preserving existing 2024/2025 originals. User explicitly requested subagents: local-only archive/coverage workers run independently while one primary browser queue handles all COA requests. Use bounded sequential batches, minimum spacing, no parallel COA downloads, persistent checkpoints and exponential backoff for retryable errors; stop on repeated failures rather than hammering the host. Source verification and application imports remain deferred. Actual counts and validation will be recorded on completion.


## Executive Summary Source Correction

26-10-27: The user explicitly superseded ZIP/full-report collection with Executive Summary PDFs only for fiscal years 2025, 2024, 2023, 2022 and 2021. Previously obtained ZIPs and extracted reports remain archived intact but are not treated as this requested collection. Standalone public summaries are organized under `data/towns/executive-summaries/<year>/<town>/`, with exact source URLs and validation manifests. Raw browser inventories/checkpoints live in ignored `storage/app/source-review/coa/executive-summaries/<year>/`. Existing downloaded summaries can be reused only when their exact source ID/year matches. No new ZIP downloads, automatic metric extraction, database imports or source-publication changes are authorized.

Only the primary browser worker contacts COA, using a 3.5-second minimum interval between report starts and sequential file saves, with one retry after 15 seconds on a download error and a persistent checkpoint per success. Local-only subagents match all 29 town identities and verify/organize PDFs with pypdf/page-tree/content-stream parsing and SHA-256. Rate limits or repeated failures pause the queue rather than spawning parallel requests. The user's completion reminder means notify in this conversation after downloads and checks finish.


## Executive Summary Collection Completed

26-10-27: The user extended the summary collection to fiscal years 2020 to 2025, asked to fill the 2021 gaps, drop the extracted report archives from `data/`, and commit the collection on its own `data/` branch.

- `data/towns/executive-summaries/`: 158 PDFs. 2020 to 2024 have all 29 towns; 2025 has the 13 towns COA had published. One `sources.csv` (PSGC code, path, pages, bytes, SHA-256, exact COA URL, retrieval date) replaces the per-year manifests, which held local machine paths.
- The extracted 2024 and 2025 report archives (`data/towns/annual-audit-reports/`) are not committed. The user removed their spreadsheets from `data/`; the Word documents, AAPSI/APMT PDFs and extraction notes are kept outside git in `storage/app/source-review/coa/removed-from-data/`, and the 2025 ZIPs in `storage/app/source-review/coa/2025/archives/`.
- Downloads ran through Claude in Chrome, one file at a time with a 3 second gap; COA blocks plain HTTP clients. Every PDF matched COA's listed size and passed the pypdf page-tree check. Helpers live in ignored `storage/app/source-review/coa/`.
- File integrity only: no figure was extracted or verified, nothing was imported, and `town-facts.csv` is unchanged. Extraction, verification and the history screen stay deferred.


## Superseded

26-10-27: The user requested the full extraction, ledger and analytics work, so the deferred remainder of this plan continues in the [Finance Ledger and Analytics Program](../archive/26-10-27-finance-ledger-analytics-program.md). The user also excluded audit opinions and infrastructure project lists from extraction and display. The source rules recorded above (no business-sales reading, no provincial totals from town accounts, no partial-year 2026 figures) carry forward.
