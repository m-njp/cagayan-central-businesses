# COA Executive Summaries

Executive Summary PDFs from the Commission on Audit (COA) annual audit reports for Cagayan's 28 municipalities and Tuguegarao City. The parser in `tools/coa/` reads them into [`../finances/`](../finances/README.md) (D34). Figures reach the screen only after review and sign-off (D35).

Collected on October 27, 2026 and November 11 to 12, 2026 from https://www.coa.gov.ph/reports/annual-audit-reports/aar-local-government-units/ (Region II, Cagayan, Cities and Municipalities folders). All 348 originals for 2014 through 2025 are registered here. The later collection and Downloads relocation were verified by SHA-256 before promotion.

## Layout

```
executive-summaries/
  sources.csv
  <year>/<Town>/<OriginalFilename>.pdf
```

Town folder names are ASCII versions of the town names. Each town's PSGC code is in `sources.csv`.

| Fiscal year | Towns | Notes |
| :- | :- | :- |
| 2014 | 29 | |
| 2015 | 29 | |
| 2016 | 29 | |
| 2017 | 29 | |
| 2018 | 29 | |
| 2019 | 29 | |
| 2020 | 29 | |
| 2021 | 29 | |
| 2022 | 29 | |
| 2023 | 29 | |
| 2024 | 29 | |
| 2025 | 29 | The remaining 16 were collected on November 12, 2026 |

Two COA listing labels differ from the PDFs' contents: Sanchez Mira 2019 is labeled Annual Audit Report and Santa Ana 2018 is labeled Executive Report. Both begin EXECUTIVE SUMMARY on page 1 and identify the municipality and requested audit year. Their original filenames and source URLs are preserved.

## sources.csv

One row per PDF:

| Column | Meaning |
| :- | :- |
| `year` | Fiscal year the report covers |
| `psgc_code` | Town code, matches `data/boundaries/cagayan-municipalities.geojson` |
| `town` | Town name |
| `file` | Path inside this folder |
| `pages`, `bytes`, `sha256` | Size and checksum of the file as downloaded |
| `source_url` | Exact COA download link |
| `retrieved_on` | Date the file was downloaded and checked |

Every file was checked as a readable PDF with a valid page tree, and its checksum was recorded at download time. That is a file integrity check only. Since 26-10-28 the extracted government figures are published through the finance ledger after sign-off, replayed from `../finances/reviews.csv`. They no longer live in `town-facts.csv`. See [Government Finances](../finances/README.md#reliability-review-26-10-28) for the reliability review.

## Adding a Year

Use the browser and automatic checkpoint/batch workflow in [`tools/coa/COLLECTION.md`](../../../tools/coa/COLLECTION.md). Preserve observed source URLs, verify PDFs and hashes, then promote the source files and metadata into this register. Do not regenerate extracted figures or import the ledger merely to add documents to the Sources page.

The public Sources library serves only registered PDFs inside this directory and verifies their checksums on download. Listing an original document does not publish or verify its extracted figures. Existing finance citations retain their separate sign-off requirement.
