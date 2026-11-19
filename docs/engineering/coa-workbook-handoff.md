# COA Workbook Handoff

**Retired "26-11-14" (D49).** Workbook reading is removed. The Executive Summary is the only source of figures, the workbook reader and its commands no longer exist, and the last active workbook rows are frozen in `data/towns/finances/history/26-11-14-workbook-reading-retirement/`. Original workbooks, their register and public downloads remain. Everything below is history, kept to explain why workbook parsing was dropped: layouts differ between governments and years, and the final staged run still left 242 workbook findings in Extraction Limits. See the [retirement plan](../plan/archive/26-11-14-executive-summary-only.md).

Updated "26-11-13". Start here before changing workbook extraction or ingestion. The owner requested fewer, explicit fields, readable municipality folders, a documented handoff and removal of generated clutter.

## Current Scope "26-11-14"

The owner approved three workbook fields: Tax Revenue, Share from National Collections and Service and Business Income. This supersedes the nine-field scope in the historical record below. Workbook reader 1.8.0 removes Annex, SCBAA and expense-component extraction; the Executive Summary parser and its shared readers/grammar stay unchanged at 1.7.1.

Grants, Other Income and Total Revenue are read only as reconciliation evidence. Retired observations and diagnostics are frozen under `data/towns/finances/history/26-11-14-workbook-retirement/`, with a checksum manifest. The importer replays the historical figures, including Pending records, without reopening active intake; promotion never rewrites history. All originals, the workbook index, document identities and public individual/ZIP downloads remain intact. Source collection is independent of financial extraction.

Enrile has no separate National Collections line in its 2024/2025 performance statement. Its tax observations carry `may_include_national_share`; the corresponding warning holds automatic sign-off for a human to resolve. Do not substitute its cash-flow IRA receipts. Current sources still have other unresolved findings; coverage is not proof of correctness.

See the active workbook plan for actual verification and the dated history README for the deletion ledger. The following sections preserve the earlier collection and ingestion context, not current intake instructions.

## Current State and Authorized Scope

All 521 collected XLSX originals for report years 2014–2025 have outcomes. The two collections cover all 29 Cagayan governments, but not every government/year ZIP contains XLSX or a supported financial statement. The final ingestion adds **2,177 Pending observations**, using **nine existing Tuguegarao fields**. These are repeated observations across governments, report years and separately printed comparative years, not thousands of distinct fields.

| Field | Code | Required source scope |
| :- | :- | :- |
| Tax Revenue | `revenue.tax` | Consolidated Financial Performance |
| Share from National Collections | `revenue.national_share` | Consolidated Financial Performance |
| Service and Business Income | `revenue.service_business` | Consolidated Financial Performance |
| Shares, Grants and Donations | `revenue.grants` | Consolidated Financial Performance |
| Other Income | `revenue.other` | Consolidated Financial Performance |
| Business Tax | `local.business_tax` | Detailed General Fund Annex |
| Permit Fees | `local.permit_fees` | Detailed General Fund Annex |
| Market Operations | `local.market_operations` | Detailed General Fund Annex |
| Real Property Tax | `local.real_property_tax` | General Fund basic property tax, net of its discount |

The M21 revenue-source definitions date from "26-10-28", with the present reported-line revision on "26-11-03"; M22's four General Fund lines were documented "26-10-28". Git blame confirms the dates. See [M21 and M22](metrics.md#m21-revenue-sources). The owner's narrower nine-line **intake subset** was clarified on "26-11-13". Do not mistake the entire catalog for permission to ingest every line.

The first all-government import added 2,385 Pending observations. It included 208 `revenue.other_national_share` observations beyond Tuguegarao's extracted set. Those 208 newly inserted, still-unreviewed observations were removed under the owner's clarification. Their evidence remains archived; none was signed off. `revenue.other_national_share` remains a catalog definition, but is outside this batch's authorized intake subset. No metric definition was silently changed.

The final ledger contains 4,847 observations: all 2,670 pre-existing observations are preserved, plus 2,177 new Pending observations (1,800 FS and 377 General Fund Annex). All 1,702 previously published statistic rows remain unchanged. No new sign-offs, corroboration, publication or Executive Summary detail-scope expansion occurred. Eight pre-existing non-displayed workbook observations remain historical legacy records; no new budget/check-only records were ingested.

## UI Exclusions and Gray States

The live Tuguegarao 2025 review table and Analytics were checked on "26-11-13":

| UI condition | Meaning | Intake treatment |
| :- | :- | :- |
| Budget and Actual Amounts disabled | M23 is withdrawn from display | Do not promote Economic Services or 20% Development Fund figures as new intake |
| Revenue Sources and Business Taxes and Fees enabled | These are supported UI groups | Use only the nine authorized lines above |
| Shares, Grants and Donations or Other Income labeled Pending | A figure awaits verification | Pending is not an instruction to delete or exclude that field |
| Superseded badge | Another observation has taken precedence | Preserve source history; it is not a field-definition withdrawal |
| Not available, or a measure disabled for another municipality | Missing verified coverage or another availability reason | Do not infer a zero or a withdrawn definition from color alone |
| A verified entry locked behind a pen | Deliberate correction is required | Do not unlock or alter it as part of parsing |

The catalog's `displayed=0` check lines, expenses, sector components, retired utilization/local-share/per-resident calculations and calculated Other Revenue are outside new intake. Some are still read as arithmetic evidence. **Reading cells for recognition/checks is different from storing them as ledger fields.** The parser needs worksheet headings, fund/year labels and component evidence to find the requested figures safely.

The UI evidence is retained locally at `storage/app/source-review/coa/summaries/ui-disabled-measures.png`. Code references: [review query](../../app/Queries/FinanceReportQuery.php), [entry-line scope](../../app/Models/FinanceLine.php), [coverage selection](../../app/Queries/AnalyticsCatalogQuery.php), [measure control](../../resources/js/components/analytics/finance-toolbar.tsx), and [M23](metrics.md#m23-budget-and-actual-amounts).

## Where Files Live

- `data/towns/financial-statements/<report year>/<Municipality>/`: registered source copies. Newly added copies use names such as `Calayan-Financial-Statements-2014.xlsx` and `Calayan-Annexes-2014.xlsx`. Existing readable filenames were preserved.
- [Workbook index](../../data/towns/financial-statements/index.csv): all 516 registered workbook identities, readable municipality/year/kind, current path, SHA-256, official URL and original archive-member/collection paths.
- `storage/app/source-review/coa/excel-collection/workbooks/` and `excel-collection-2014-2019/workbooks/`: all 521 unchanged collection originals. Their original member names and provenance folders remain untouched.
- [Canonical finance guide](../../data/towns/finances/README.md): `documents.csv` is the active register; `extracted/` contains promoted observations; `issues/` retains attributed findings. `reviews.csv` and `manual-figures.csv` are unchanged.
- `storage/app/source-review/coa/summaries/`: readable CSV outcomes, current scope, Pending counts, exclusions, source indexes, no-XLSX coverage, source checks and the four reconciliations.
- `storage/app/source-review/coa/archives/`: checksum-verified historical evidence. Expanded snapshots, per-file JSON and obsolete one-off helpers are archived, not maintained executable workflows.

The old numeric folders, such as `0201501000`, were **PSGC municipality identifiers**, not financial amounts or malformed years. They remain metadata strings with leading zeros. The 482 newly registered copies were moved to readable municipality folders, their database/CSV paths updated, and 284 empty numeric folders removed. Workbook bytes did not change. The collection originals retain their original paths; the human index is the normal navigation entry point.

There are fewer registered workbook identities than originals: three AAPSI files are outside the finance parser; two Lasam 2018 FS originals are byte-identical; Peñablanca 2019 has two distinct zero-eligible Annex originals but one canonical government/year/kind slot. Both original variants remain preserved and documented, without double-counting their financial facts.

## Collection and Reader Contract

The maintained [collection guide](../../tools/coa/COLLECTION.md) and the XLSX parser guide (removed with the reader, D49) retain the detailed commands and regressions. The agreed contract is:

- Official COA Annual Audit Report ZIPs only, newest report year first within each collection. Sequential downloads retain existing pacing and checkpoint each completion. ZIP validation/extraction batches are 15 with bounded local workers and a final partial flush; PDF collection stays at 12.
- Keep all nested `.xlsx` members, including uppercase extensions and distinct duplicate filenames. Exclude `.xls`, `.xlsm`, PDFs and other archive contents. Reject unsafe archive paths and extraction-limit violations. Retain failed ZIPs for retry; delete a successful ZIP only after readable workbook validation and a durable success/no-XLSX checkpoint. Preserve source URL, municipality/year, archive/member names and checksums.
- Resume successful unchanged checkpoints instead of redownloading/rechecking them. Retrying a failed or changed source must revalidate its bytes. Original collection workbooks remain under ignored source-review storage and are never modified.
- Reader version 1.6.0 (1.5.1 at ingestion) recognizes observed underscore/revision/Financial_Statements/Appendices filename forms, then confirms contents. It applies the same read-only readers to CLI and uploads, uses strict text monetary tokens plus native numeric cells, withholds missing/error formula caches, conflicting years and ambiguous fund/sheet choices, and never recalculates/saves a source. A formula or zero-figure outcome is not silently repaired.
- Staged runs use fresh protected output directories, at most four local workers, per-file isolation/checkpoints and matching checksum/parser/configuration identities on resume. They retain cell evidence, section/line/amount completeness, checksums and withholding reasons. Check-only amounts and source arithmetic/comparison evidence remain separate from intake/publication candidates.
- Modern baseline/reader changes and historical baseline/reader changes were captured separately; changed observations were reopened against original cells before being called source-supported candidates. Checks and a confidence score do not constitute financial sign-off. No original Executive Summary/PDF extraction algorithm was changed by this ingestion/cleanup; the shared extractor version label had already moved to 1.5.1 during XLSX hardening.

The earlier archive plans preserve exact execution commands, dates and verification results. Closed snapshots and helpers are kept in the evidence ZIPs; maintained operations stay in Composer and `tools/coa/`.

## Layout and Source Limits

| Situation | Observed behavior | Required handling |
| :- | :- | :- |
| Modern Financial Performance | Reported revenue categories, sometimes split/merged labels | Match existing catalog meanings and explicit cells; never invent a remainder |
| Legacy Statement of Income and Expenses | Operating subtotal may exclude later finance cost, donations or subsidies | Trace statement hierarchy; the same label does not prove equal expense scope |
| General Fund Annex | GF, SEF or other fund blocks can share a sheet | Require explicit fund evidence; never substitute GF detail for consolidated totals |
| One Amount column without numeric year headers | Some older workbooks state one explicit matching period | Accept only the supported unique single-period layout; do not invent a prior year |
| Conflicting year header | A section states another period | Withhold it and retain the header evidence; no automatic reassignment |
| Duplicate mapped component labels | Two distinct rows can collide with one catalog line | Withhold ambiguous mappings as extraction limits, not a proven COA arithmetic error |
| Text monetary values | Some Tuao cells are valid amounts stored as strings | Use strict monetary-token recognition; malformed grouping/percentages/text stay withheld |
| Formula cells | Read saved cached results without calculation | Missing caches and Excel errors withhold affected amounts; never save or recalculate originals |
| No eligible lines | 232 of 521 files have no eligible catalog output; three more are out of scope | Preserve a reason; this does not mean the government has no income or expenses |

All zero-eligible files were inspected across stored sheets and relevant adjacent labels in the earlier hardening/historical work. This does not amount to OCR or arbitrary semantic recognition of embedded images/objects. Some supported-looking labels remain withheld because fund/year/amount selection is unresolved. Preserve that uncertainty rather than making all files produce numbers.

The collected ZIP outcomes include **47 government/year archives without XLSX**: 40 in 2014–2019 and seven in 2020–2025. `summaries/no-xlsx.csv` lists every pair and official archive checksum/URL. Other formats are **unconfirmed**: excluded member names were not retained by the original XLSX-only collection, and later COA reinspection did not obtain replacement ZIP bytes. Absence of XLSX does not prove XLS, XLSM or PDF-only content.

## Four Explained Expense Differences

The [original-source investigation](../plan/archive/26-11-13-historical-expense-differences.md) checked original XLSX cells and six rendered Executive Summary pages, with 24 independent arithmetic equations. All four 2014 source amounts were correctly transcribed:

| Municipality | Exact bridge from workbook subtotal to summary | Remaining qualification |
| :- | :- | :- |
| Calayan | 70,269,065.82 + Financial Expenses 1,821,027.03 = 72,090,092.85 | Same bridge holds in 2013; later subsidies remain outside summary expenses |
| Camalaniugan | 62,527,727.96 - subsidy/donations 566,749.00 = 61,960,978.96 | Both GF and SEF schedules reconcile; consolidated MOOE includes these separately scheduled deductions; not a stale-cache discrepancy |
| Claveria | 63,358,293.97 + Bank Charges 38,500.00 = 63,396,793.97 | Summary 2013 excludes its bank charge, unlike 2014 |
| Santa Praxedes | 34,559,911.33 + Donations 516,300.00 = 35,076,211.33 | Summary 2013 excludes its donation, unlike 2014 |

The arithmetic explanations do not establish the compiler's intent or authorize normalizing source definitions. Do not automatically add all below-subtotal rows. Raw findings remain historical evidence; the current workbook issues include the explanatory notes. Financial parsing, source review and sign-off remain separate.

Combined attributed checks across 2014–2025 record 56 restatement observations, two repeated-year warnings and 15 printed-total differences. Four differences are explained above; 11 other printed-total differences remain review items. Some checks concern non-intake components; they are evidence, not extra ledger fields. Do not label skipped components as proven report arithmetic errors.

## Safe Commands and Important Integration Gap

```powershell
composer data:finances:test
composer ci:check
composer validate --no-check-publish
composer data:finances:audit-workbooks -- --collection storage/app/source-review/coa/excel-collection-2014-2019 --output storage/app/source-review/coa/new-historical-review --workers 4
composer data:finances:audit-workbooks -- --collection storage/app/source-review/coa/excel-collection --output storage/app/source-review/coa/new-modern-review --workers 4
```

Staging uses fresh ignored outputs, at most four workers and per-file checkpoints. Resume only with matching source checksum, parser/code identity and configuration. A staged full-catalog candidate is not automatically authorized for nine-line promotion. Tests automatically remove temporary fixtures; use explicit `--basetemp` only for a short-lived debugging need, then remove it after recording results.

`composer data:finances` imports the **current curated CSVs**. The generic importer can delete stale Pending observations, so snapshot/diff before replacing its inputs. The next import must preserve existing figures and reviewed evidence unless an owner-approved correction explicitly identifies them.

**Integrated "26-11-13" (extractor 1.6.0, [plan](../plan/archive/26-11-13-workbook-trim-all-town-summary.md)).** `extract` reads the 516-row `financial-statements/index.csv` with PSGC codes from `town-facts.csv`; the legacy `sources.csv` is retired after identical output for its 34 files. The nine-field gate is code: the `intake` column of `finances/lines.csv` is honored by the extractor, uploads, promotion and `finances:import`, which refuses a new non-intake workbook row and accepts one only when that figure is already reviewed. Use `composer data:finances:workbooks`, which stages and prints the diff by default; `-- --promote` copies into the committed CSVs only when they are unchanged since staging. Check every changed value against its workbook cell before promoting.

`financial-statements/not-published.csv` is a dated 2026-10-27 listing, not the present coverage register. Keep it as historical provenance; use current documents and no-XLSX coverage to answer availability questions.

## Recommended Next Work

1. Integrate the collection-backed inventory and explicit nine-line intake gate into a supported promotion workflow before future bulk ingestion. Do not revive archived ad hoc scripts against changed baselines.
2. Review Pending candidates against their exact source cells, starting with ambiguous fund/year/formula cases and the 11 unexplained printed-total comparisons. Keep publication blocked while unresolved.
3. Extend only demonstrated unsupported layouts, with source-cell regressions. Do not drop all XLSX or guess amounts because formats vary.
4. If alternative-format investigation is requested, reacquire the exact official ZIP, verify its earlier archive hash and inspect member metadata before claiming XLS/PDF formats.

No commits, pushes or deployment occurred. Executive Summary/PDF extraction algorithms and the 15 parser modules were unchanged during this investigation, ingestion and cleanup. The shared extractor version was 1.5.1 at the time and is 1.6.0 since the 26-11-13 integration. Sources, sign-offs and published values are preserved; only the explicitly authorized Pending subset and readable source-copy paths changed.


## Completed Plans and Verification

- [All-government ingestion and scope correction](../plan/archive/26-11-13-workbook-ingestion-all-governments.md).
- [Cleanup and developer/AI handoff](../plan/archive/26-11-13-coa-workbook-cleanup-handoff.md).
- [Historical XLSX parsing](../plan/archive/26-11-13-historical-xlsx-staged-parsing.md), [parser hardening](../plan/archive/26-11-13-xlsx-parser-hardening.md), and [four source reconciliations](../plan/archive/26-11-13-historical-expense-differences.md).

Full repository CI passed: 481 Laravel tests and 3,519 assertions, frontend checks, Pint and PHPStan. The final scoped/path-organized data passed the focused import regression (1 test, 11 assertions). All current source paths/hashes, original source preservation, unchanged Executive Summary data and review files, and the nine-line Pending subset passed independent checks. Composer validation, local documentation links and whitespace checks passed. Archived raw proof is indexed beside the readable local report.

No PR, commit, push or deployment was requested or created. This document and the completed plan records are the handoff. The owner's local server was reused and answered HTTP200 on port 8000; data review is at `/capitol/finances`, while newly imported values remain unpublished until sign-off.
