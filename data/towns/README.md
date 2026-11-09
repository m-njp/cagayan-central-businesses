# Town Figures

Real public figures for Cagayan's 29 towns. The Home and workspace economic maps show population from this file only in Latest, alongside annual Government Revenue, Government Expenses and Surplus or Deficit from the finance ledger ([`finances/`](finances/README.md), D35). The other measures stay in the file for later. One file, `town-facts.csv`, loaded into the `town_facts` table by `php artisan towns:import` (or `composer data:towns`). `composer db:reset` loads it too.

Prepared on October 23, 2026.

## The file

One row per town, measure, and year:

| Column | Example | Notes |
| :- | :- | :- |
| `psgc_code` | `0201529000` | Must match `data/boundaries/cagayan-municipalities.geojson` |
| `town` | `Tuguegarao City` | For people reading the file. The import ignores it |
| `measure` | `population` | One of the measures below |
| `year` | `2024` | The year the figure is for |
| `value` | `167297` | A plain number: no commas, no peso sign, no percent sign |
| `source_url` | `https://...` | Where the figure was read. Required, `https://` only |
| `checked_on` | `2026-10-23` | The date a team member checked the figure against the official release. Blank until then |

Measures:

| Measure | Unit | Years in the file |
| :- | :- | :- |
| `population` | residents | 2020 census, 2024 census (POPCEN) |
| `barangay_count` | barangays | 2020 (PSA PSGC) |
| `income_class` | 1 to 6, the DOF income class | 2024 |
| `poverty_incidence` | percent of the population | 2023 (PSA small area estimates) |

## How to update

1. Add or edit rows in `town-facts.csv`. For a new census year, **add** rows with the new year. Keeping the old ones keeps the history.
2. Run `composer data:towns`. It checks every row first and imports nothing if a row is wrong, listing each problem with its line number.
3. Refresh the Home page. Latest uses the newest verified census population. Finance controls select one absolute fiscal year across all towns; explicit years omit population. Unchecked rows stay in the source file but are withheld from responses.

The CSV is the source of truth: every import replaces the whole table with the file.

## Where the figures came from

Each `source_url` points to the original release used for that figure. The 2024 population values below were checked directly against the Cagayan tab of the PSA Region II Statistical Tables workbook:

| Measure | Original source | `source_url` |
| :- | :- | :- |
| `population` 2024 | PSA, 2024 Census of Population, Region II Statistical Tables, Cagayan tab | https://psa.gov.ph/statistics/population-and-housing/node/1684077888 |
| `population` 2020 | PSA, 2020 Census of Population and Housing, counts declared official July 2021 | https://psa.gov.ph/content/2020-census-population-and-housing-2020-cph-population-counts-declared-official-president |
| `poverty_incidence` 2023 | PSA, 2023 city and municipal poverty estimates (February 6, 2026) | https://psa.gov.ph/content/881-cities-and-municipalities-recorded-poverty-incidence-20-percent-or-lower-2023 |
| `income_class` 2024 | DOF Department Order 074-2024 (RA 11964), published by BLGF | https://blgf.gov.ph/wp-content/uploads/2025/01/DOF-DO-074.2024.pdf |
| `revenue`, `expenditure` 2024 | Each town's audited financial statements, Commission on Audit (the source Wikidata records for these values) | https://www.coa.gov.ph/reports/annual-audit-reports/aar-local-government-units/ |
| `barangay_count` | PSA PSGC page for the town | `https://psa.gov.ph/classification/psgc/barangays/<code>` |

Land area was dropped: it could not be traced to an official document, and nothing shows it.

The PSA source page and attached workbook were checked directly. All 29 town populations and all 820 barangay populations match the original Cagayan sheet, totaling 1,284,676; Tuguegarao totals 167,297. No other province tabs were used.

## Verification and Publication

Only rows with `checked_on` and a source URL can reach the atlas responses. Older verified rows remain eligible when a newer row is unchecked. This applies to both public and workspace maps. A verification date means source transcription was checked, not that the government's accounts are free of audit qualifications.

Government Revenue and Government Expenses moved to the finance ledger on 26-10-28 (D35). The four 2024 pairs checked against COA workbooks (Abulug, Alcala, Allacapan and Tuguegarao City, see [Verified Local Government Finances 2024](verified-finances-2024.md)) were the ledger's first sign-offs. Since 26-10-28 every loaded parsed figure is signed off, after the reliability review in [the finance data README](finances/README.md#reliability-review-26-10-28).

## COA Source Documents

[`executive-summaries/`](executive-summaries/README.md) holds COA Executive Summary PDFs for fiscal years 2020 to 2025. The parser in `tools/coa/` reads them into [`finances/`](finances/README.md), where they wait for review before the finance ledger publishes them.

Province population/count totals require all towns to have the same reference year. City/municipal government accounts are not aggregated into a provincial-government finance figure (M27).

The 29 population rows for 2024 have `checked_on=2026-10-23`. Other measures stay unchecked until compared directly with their originals. Replace the source URL with an exact document when checking a figure, then fill in the verification date. Never mark a generic source page as evidence that a number was inspected.
