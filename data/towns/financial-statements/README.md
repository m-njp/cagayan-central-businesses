# COA Statement Workbooks

Two Excel files from each COA Annual Audit Report ZIP. They hold the breakdowns that the Executive Summaries leave out:

| File | From the ZIP | What the parser reads |
| :- | :- | :- |
| `<Town>-FS-<year>.xlsx` | `AAR/07-…_Part1-FS.xlsx` | Statement of Financial Performance: Local Taxes, National Tax Allotment Share, Fees and Business Income. The SCBAA (budget and actual) sheet: the 20% Development Fund and Economic Services |
| `<Town>-Annexes-<year>.xlsx` | `AAR/11-…_Part4-Annexes.xlsx` | Detailed General Fund statement: Business Tax, Permit Fees, Market Operations, Real Property Tax |

The parser in `tools/coa/` reads them into `../finances/` (D34). Definitions are M21 to M23 in `docs/engineering/metrics.md`. Sheet names differ in every town, so the parser chooses sheets by their title rows and labels.

## Coverage

17 town-years, from the archives already downloaded:

| Fiscal year | Towns |
| :- | :- |
| 2024 | Abulug, Alcala, Allacapan, Tuguegarao City |
| 2025 | Alcala, Amulung, Baggao, Enrile, Gattaran, Iguig, Peñablanca, Piat, Rizal, Solana, Santo Niño, Tuao, Tuguegarao City |

These are the workbook pairs on disk, not the loaded publication coverage. Since 26-10-28 only Tuguegarao City workbook breakdowns for 2024 and 2025 load into the ledger (`cagayan.finances.workbook_towns`). Other towns and years show Not available for these breakdowns. Totals still come from the Executive Summaries.

`not-published.csv` lists the 16 towns with no 2025 Annual Audit Report on the COA site when it was checked on 2026-10-27. COA lists the 2020 to 2024 reports for every town; collecting the rest is separate work.

## sources.csv

One row per workbook:

| Column | Meaning |
| :- | :- |
| `year`, `psgc_code`, `town`, `kind` | `kind` is `fs_workbook` or `annex_workbook` |
| `file`, `bytes`, `sha256` | The copy in this folder |
| `archive_member`, `archive_sha256` | Its name inside the ZIP, and the ZIP's checksum |
| `source_url`, `retrieved_on` | The exact COA download and the date it was downloaded |

## Adding Workbooks

1. Download Annual Audit Report ZIPs from https://www.coa.gov.ph/reports/annual-audit-reports/aar-local-government-units/ (Region II, then Cities or Municipalities, then Cagayan). COA blocks plain HTTP clients, so use a browser.
2. Write a CSV listing each ZIP with the columns `archive,source_url,retrieved_on`. `archive` is the local path; keep COA's file name, such as `Abulug-Annual-Audit-Report-2023.zip`.
3. Run `python tools/coa/run.py add-workbooks <that csv>`. It copies the two workbooks here and updates `sources.csv`.
4. Run `composer data:finances:extract` and check `../finances/issues/`.
5. Run `composer data:finances`. The new figures load as pending until someone signs them off.

Keep the ZIPs outside git, in `storage/app/source-review/coa/`.
