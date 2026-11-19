# COA Statement Workbooks

Workbook reading is retired, "26-11-14" (D49): the Executive Summary is the only source of figures. These workbooks are kept as downloadable originals only: `index.csv` feeds the public source library and ZIP download, and nothing reads their figures. Paragraphs below about intake fields and `composer data:finances:workbooks` are history.

Current register: [index.csv](index.csv), updated "26-11-14". It lists **520 registered workbook identities** from the 2014–2025 collections, with municipality, report year, kind, current file, checksum, official URL and original member/collection paths. All 521 collection originals remain unchanged; one identical duplicate explains the difference. The owner approved adding the three AAPSI files and Peñablanca's 2019 Appendices to downloads only. `aapsi_workbook` and `appendices_workbook` are source-library labels, never ledger document kinds or extraction inputs.

## Browse by Municipality

```text
2014/
  Calayan/
    Calayan-Financial-Statements-2014.xlsx
    Calayan-Annexes-2014.xlsx
2015/
  Camalaniugan/
    Camalaniugan-Financial-Statements-2015.xlsx
    Camalaniugan-Annexes-2015.xlsx
```

The 482 newly registered copies now use readable municipality folders and document names. Older readable source names remain unchanged. Numeric folders formerly used PSGC municipality identifiers, not years or amounts; those identifiers remain in metadata. Use [the active finance document register](../finances/documents.csv) for application citations.

## What Enters the Ledger

Only the **nine Tuguegarao reference fields** are in the current all-government intake: five reported consolidated revenue sources (Tax Revenue, Share from National Collections, Service and Business Income, Shares/Grants/Donations, Other Income) and four General Fund details (Business Tax, Permit Fees, Market Operations, basic Real Property Tax net of discount). New observations remain Pending. M23 budget/sector lines and check-only amounts are not new intake. Other Shares from National Taxes is outside this narrower owner-approved subset even though it remains in the broader catalog.

See the [developer/AI handoff](../../../docs/engineering/coa-workbook-handoff.md) for exact codes, UI exclusions, layout limits, source differences, coverage and safe commands. Totals still come from Executive Summaries; no workbook figures were substituted for those totals.

## Historical Registers and Safe Updates

`index.csv` is the workbook register the extractor reads (since 26-11-13; the legacy 34-row `sources.csv` is retired). `not-published.csv` records the COA listing checked on 2026-10-27 and remains historical provenance, **not current coverage**. The collection journals and active finance register carry the newer coverage.

Use `composer data:finances:workbooks` to stage and diff, then `-- --promote` after checking every changed value against its cell. The nine-field scope is enforced by the `intake` column of `finances/lines.csv`. Unsupported layouts stay withheld; no workbook is recalculated or saved.
