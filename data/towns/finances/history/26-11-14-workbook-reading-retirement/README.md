# Workbook Reading Retirement

Owner-approved "26-11-14" ([D49](../../../../../docs/engineering/decisions.md)). Workbook (XLSX) reading is retired. The Executive Summary is the only source of government finance figures.

This folder freezes every workbook row that was still active when reading stopped. `manifest.json` holds the SHA-256 of each file.

| File | Rows | What It Holds |
| :- | -: | :- |
| `figures.csv` | 1,217 | Tax Revenue, Share from National Collections and Service and Business Income observations from the Financial Statements workbooks, with cell citations and inputs |
| `issues.csv` | 423 | The workbook findings from the last extraction: 242 Extraction Limits, 170 Between Reports and 11 Within the Report |
| `reviews.csv` | 1,381 | Sign-offs on workbook figures, including the lines retired earlier the same day |
| `documents.csv` | 516 | The workbook document records that were in `documents.csv` |

Rows were moved byte for byte out of `extracted/`, `issues/`, `reviews.csv` and `documents.csv`. Executive Summary rows were not touched.

Nothing reads this folder. `finances:import` loads only Executive Summary documents and figures, and a migration removes workbook figures, documents, uploads and lines from existing databases. The earlier [26-11-14-workbook-retirement](../26-11-14-workbook-retirement/README.md) folder is frozen the same way and is no longer replayed.

The original workbooks are unchanged under `data/towns/financial-statements/`, registered in `index.csv`, and stay downloadable from About the Data.
