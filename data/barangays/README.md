# Barangay Population

Population of Tuguegarao City's 49 barangays for the database, plus browser map datasets for all 820 barangays across Cagayan Province. The CSV, `tuguegarao-population.csv`, is loaded into the `barangays` table by `php artisan barangays:import` (`composer data:towns` and `composer db:reset` run it too). About the Data loads the town-specific browser datasets described below.

Prepared on October 23, 2026.

## The file

| Column | Example | Notes |
| :- | :- | :- |
| `psgc_code` | `0201529001` | Must match `data/boundaries/tuguegarao-barangays.geojson` |
| `barangay` | `Annafunan East` | For people reading the file. The import ignores it |
| `year` | `2024` | Census year |
| `population` | `5000` | Whole number, no commas |
| `source_url` | `https://...` | The official release. Required, `https://` only |
| `checked_on` | `2026-10-23` | When a team member checked it. Blank until then |

## Source

PSA, 2024 Census of Population, Region II Statistical Tables, Cagayan tab, as of July 1, 2024: https://psa.gov.ph/statistics/population-and-housing/node/1684077888

The 49 Tuguegarao City barangay values were matched by name to the existing PSA PSGC codes. They add up exactly to the Cagayan tab's Tuguegarao City total, 167,297. The workbook's other province tabs, including Batanes, were not used. Database population imports currently cover Tuguegarao City; the public browser explorer covers all 29 Cagayan towns.

## Province-wide browser maps

`maps/<municipality_psgc_code>.json` contains one town's barangay geometry and 2024 population. These files are loaded individually through Vite dynamic imports, never all at page startup. They cover 820 barangays across Cagayan's 29 towns. Only the workbook's Cagayan sheet was read.

Population was joined by normalized barangay name within each town, stripping punctuation, accents, and parenthetical aliases. Every name matched uniquely and every barangay sum equals its town's workbook total; the 29 totals equal 1,284,676. Tuguegarao's 49 values equal the existing CSV and sum to 167,297.

Geometry: faeldon/philippines-json-maps, master, `2023/geojson/municities/hires/bgysubmuns-municity-<9-digit-town-code>.0.1.json`. Properties are normalized to the existing 10-digit PSGC schema. Tuguegarao retains the existing geometry. The source's MIT license is retained in `maps/LICENSE.txt`. Geometry is 2023, population is 2024; the census workbook does not supply boundaries. These simplified shapes are for comparison, not surveying.

`shade` is prepared at import time using M18 equal-count groups, with the current `towns.shades` value of 5. Larger groups come first when the count is not divisible by five, matching PostgreSQL ntile. No metric aggregation runs in the browser.

The October 24 boundary recheck records local display aliases Carig Sur, Carig Norte, and Pengue-Ruyu in `data/boundaries/README.md`. Their PSGC codes, census values, and geometry are unchanged.
