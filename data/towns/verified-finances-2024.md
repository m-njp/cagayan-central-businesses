# Verified Local Government Finances 2024

Since 26-10-28 these pairs live in the finance ledger as sign-offs in [`finances/reviews.csv`](finances/reviews.csv), attached to the matching Executive Summary figures, which equal the workbook cells below to the cent (D35). The `revenue` and `expenditure` keys below describe the earlier `town_facts` storage.

Checked 26-10-24 against original COA annual audit report archives, not Wikidata values. The reporting entity is the named city/municipality, for the year ended December 31, 2024. Amounts are Philippine pesos, accrual financial-performance figures. These are neither private-business sales nor GDP. National tax allocations form part of government revenue.

`revenue` stores Total Revenue. The legacy `expenditure` key stores total **expenses**, including non-cash expenses and transfers/assistance/subsidy to other entities. It does not mean cash disbursements, appropriations or obligations. The interface labels are Government Revenue and Government Expenses. Each expense below was cross-checked against the archive's `03-…_Executive_Summary.docx` Financial Highlights/Results of Operations table. Source worksheets record transfers to as negative, hence subtraction in the cell references below. Stored cents replace the earlier rounded secondary-source values; display rounding is separate.

| Reporting Entity | Government Revenue | Government Expenses | Original COA Archive |
| :- | -: | -: | :- |
| Abulug | 196960688.39 | 179307711.01 | [2024 Annual Audit Report](https://www.coa.gov.ph/download/8393/cagayan/95278/abulug-annual-audit-report-2024.zip) |
| Alcala | 224744176.27 | 201010574.32 | [2024 Annual Audit Report](https://www.coa.gov.ph/download/8393/cagayan/95295/alcala-annual-audit-report-2024-2.zip) |
| Allacapan | 232465478.52 | 229812731.98 | [2024 Annual Audit Report](https://www.coa.gov.ph/download/8393/cagayan/95019/allacapan-annual-audit-report-2024.zip) |
| Tuguegarao City | 1465722146.22 | 1184642113.21 | [2024 Annual Audit Report](https://www.coa.gov.ph/download/8413/cagayan/95273/tuguegarao-city-annual-audit-report-2024.zip) |

## Reproduction References

- **Abulug:** `AAR/07-Abulug2024_Part1-FS.xlsx`, sheet `Performance`. Revenue `E15`; expenses `E22 - E27`. ZIP SHA-256 `791c1c441284da5fe35b74c719b1584494cc8d8a7a675e69a24b05eb47a4c497`.
- **Alcala:** `AAR/07-Alcala2024_Part1-FS.xlsx`, sheet `Perf`. Revenue `F16`; expenses `F23 - F26`. ZIP SHA-256 `317eaa3efc0c78f03b0475c73f9418fd7603c2353b90bdc48571939899c73bf4`.
- **Allacapan:** `AAR/07-Allacapan2024_Part1-FS.xlsx`, sheet `Financial Performance`. Revenue `D15`; expenses `D22 - D26`. ZIP SHA-256 `6d04932f8621075f0d6249a0a5926b6c508610280a14df22e9bc8924777f3f53`.
- **Tuguegarao City:** `AAR/07-TuguegaraoCity2024_Part1-FS.xlsx`, sheet `6 - Financial Performance 2024`. Revenue `D14`; expenses `D21 - D25`. ZIP SHA-256 `185eac83f47775eabbc53c1b825d21120c80ac76bdfc7fb72b0824f4bc7f0c07`.

## Audit Qualifications and Limits

All four 2024 executive summaries report a **qualified opinion**. Verification means faithful transcription of the reported amounts, not certification that the accounts are free of misstatement.

- Abulug: incomplete PPE inventory/property records and cleansing procedures affect reliability of property balances.
- Alcala: reported departures/errors in PPE, road networks and other payables affect the financial statements.
- Allacapan: inventory/reconciliation, semi-expendable assets and road-network discrepancies affect PPE; delayed recognition of depreciation also understates expenses according to the observations.
- Tuguegarao City: unadjusted bank reconciliation items, understated depreciation, PPE/inventory/disbursement misclassifications and DRRMF trust-liability errors affect the reported accounts. No clean-audit or investment-quality inference is made.

The supplied 2025 Tuguegarao executive summary repeats the same 2024 comparative income and expense amounts; it is a cross-check, not an imported 2025 time series. Bulk 2021–2026 source collection and restatement-aware comparisons remain deferred. Other towns' fiscal rows remain unchecked and are withheld from both workspace and public responses. Municipality/city accounts are never summed and labeled as the provincial government's accounts.
