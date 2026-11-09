# COA Executive Summaries

Executive Summary PDFs from the Commission on Audit (COA) annual audit reports for Cagayan's 28 municipalities and Tuguegarao City. The parser in `tools/coa/` reads them into [`../finances/`](../finances/README.md) (D34). Figures reach the screen only after review and sign-off (D35).

Collected on October 27, 2026 from https://www.coa.gov.ph/reports/annual-audit-reports/aar-local-government-units/ (Region II, Cagayan, Cities and Municipalities folders).

## Layout

```
executive-summaries/
  sources.csv
  <year>/<Town>/<Town>-Executive-Summary-<year>.pdf
```

Town folder names are ASCII versions of the town names. Each town's PSGC code is in `sources.csv`.

| Fiscal year | Towns | Notes |
| :- | :- | :- |
| 2020 | 29 | |
| 2021 | 29 | |
| 2022 | 29 | |
| 2023 | 29 | |
| 2024 | 29 | |
| 2025 | 13 | Alcala, Amulung, Baggao, Enrile, Gattaran, Iguig, Penablanca, Piat, Rizal, Solana, Sto. Nino, Tuao, Tuguegarao City. The other 16 towns had no 2025 report on COA's site on October 27, 2026 |

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

Download the new summaries one at a time from the COA page above, then use the helpers in `storage/app/source-review/coa/` (ignored by git): `build_download_manifests.py`, `organize_executive_summaries.py`, then `tidy_data_folders.py` to rebuild `sources.csv`. COA blocks plain HTTP clients, so downloads go through a browser.
