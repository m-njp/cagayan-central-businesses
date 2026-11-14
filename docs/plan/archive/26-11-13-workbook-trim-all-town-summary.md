---
title: "Workbook Data Trim, All-Town Executive Summary and Parser Upkeep"
created: "26-11-13"
updated: "26-11-14"
status: superseded
branch: "chore/maintainability-refresh"
base: staging
predecessor: "26-11-13-coa-workbook-cleanup-handoff.md"
successor: "../active/26-11-14-executive-summary-only.md"
---

# Workbook Data Trim, All-Town Executive Summary and Parser Upkeep

## Superseded "26-11-14"

The owner retired all workbook reading later the same day (D49). The Executive Summary work in this plan stays. The three-field workbook reduction, its preservation path and its open verification items are closed without further work; see the [successor plan](../active/26-11-14-executive-summary-only.md).

## Objective

The owner asked for four things: all governments' data in the system so it can be seen; a trim of the **workbook (.xlsx)** fields to those that serve the app's two purposes, good governance and attracting business proponents; N/A in place of long "Not available" and Tuguegarao-only notes; and a reliable, maintainable workbook script. The Executive Summary fields stay. The owner also asked for a clearer public help than the right-hand sheet. Decision: [D47](../../engineering/decisions.md#d47-executive-summary-lines-for-all-governments-na-and-the-finances-guide).

## Findings

- `summary_detail_towns` alone produced "Loaded for Tuguegarao City only". Assets, Liabilities and Equity were already extracted for 27 to 29 governments in every year from 2014 to 2025.
- Earlier finding, superseded by the "26-11-14" source-specific validation below: Aparri's inspected summaries omitted budget execution, but this does not establish an omission across all governments from 2023 onward.
- The nine-field workbook intake limit was applied by hand and enforced by no code. `extract` still read the legacy 34-row `sources.csv`. 218 of 279 Annex files ended with `sheet_not_found`.

## Workbook Fields

Historical scope; superseded by the approved reduction under Owner Follow-up and Coverage Validation below.

Kept: the five Revenue Sources (M21) and the four General Fund Business Taxes and Fees (M22). Excluded from display: expense types, the Statement of Financial Position (it duplicates the Executive Summary), Cash Flows, Changes in Net Assets, SCBAA sector rows (M23 withdrawn) and Annex schedules. Waiting for the owner: whether Other Shares from National Taxes is added into Share from National Collections for comparability (an M21 change), and the SCBAA totals and Capital Outlay investigation.

## Scope and Acceptance

### Owner Follow-up and Coverage Validation

"26-11-14": the owner approved keeping the Executive Summary as the foundation, retiring active Annex extraction and its four displayed fields, and preserving originals and verification evidence. The subsequent coverage research and owner clarification settle the displayed workbook fields at **Tax Revenue, Share from National Collections and Service and Business Income**. Other Income and Shares, Grants and Donations leave the main presentation but remain readable as verification components. This supersedes the earlier nine-field presentation and active intake scope, not their historical observations or source definitions.

The owner explicitly prioritized accuracy over filling all 29 cells: uncertainty and inconsistency belong in Report Differences, with a human responsible for correction and source verification. Missing or unresolved values remain N/A or Pending, never inferred zeros. The owner first requested planning only, then explicitly authorized implementation on "26-11-14". The reduction is implemented and verification is in progress; source files and Executive Summary parsing remain unchanged.

- [ ] Retire active Annex extraction and presentation without deleting originals, existing observations, sign-offs or source evidence.
- [ ] Remove Other Income and Shares, Grants and Donations from the main presentation while retaining components needed to reconcile the workbook's own Total Revenue.
- [x] Settle three-field presentation versus universal coverage: retain the three fields, preserve gaps and send uncertain cases to human review.

Read-only validation used the current extracted CSVs for all report years and original 2025 sources. Fiscal-year coverage for Tax Revenue / Service and Business Income / National Collections is 21/20/20 governments in 2020, 26/26/25 in 2021, 27/27/26 in 2022 and 2023, and 29/29/28 in 2024 and 2025. Counts include separately printed comparative years and describe extraction, not verification. Other Income covers 24 governments and Shares, Grants and Donations 16 in 2025.

Enrile is the missing separate National Collections line in 2024 and 2025. Its 2025 workbook's `8. Consolidated Fin Per` reports Tax Revenue of 221,114,045.61 and Service and Business Income of 29,898,178.08, without a separate national share. Both tax observations already carry `may_include_national_share`; therefore 29 extracted tax figures do not establish 29 comparable locally generated tax figures. The `10. Consolidated Cash Flows` sheet separately prints IRA receipts of 213,393,317.00 for 2025 and 179,821,873.00 for 2024. Those cash-flow amounts are not substitutes for accrual revenue and were not imported or used to split Tax Revenue. Resolving the performance-statement scope requires its cited Notes 12-13 or equivalent authoritative evidence.

External research: [BLGF's explanation of LGU income sources](https://blgf.gov.ph/dof-unit-clarifies-lgu-income-data/) distinguishes local collections from national/external sources; [DBM Table F.2](https://www.dbm.gov.ph/wp-content/uploads/BESF/BESF2025/F2.pdf) separately presents local taxes, service/user charges, economic-enterprise receipts and national allotments. These support keeping national transfers as context, not treating government income as private-business sales or substituting cash receipts for COA revenue. Selected revenue lines must not be presented as an exhaustive decomposition of Executive Summary Income.

The earlier statement that Executive Summaries omit budget execution from 2023 onward was overgeneralized from Aparri. Direct 2025 inspection found budget execution figures in Santo Niño and Enrile. This follow-up does not change existing budget data or authorize replacement with SCBAA figures; any blanket absence reason needs a separate source-specific review.

### Implementation Plan for the Approved Reduction

Implement in this checkout on `chore/maintainability-refresh`; do not create a worktree, discard the substantial existing uncommitted work, commit, push or deploy. The owner ended planning-only work and authorized this implementation on "26-11-14". Earlier completed work and verification below remain historical evidence, not proof that this follow-up has passed.

1. **Capture preservation evidence before changing active scope.** Inventory source files and hashes, document registrations, every existing observation including Pending records, review decisions, manual corrections and published Executive Summary statistics. Use the current state rather than the older counts elsewhere in this plan. Keep the original XLSX files, source URLs, cell citations, formula inputs, definition variants and parser versions. Do not rerun collection or remove sources to lower a findings count.
2. **Separate active fields from historical and verification records.** In `data/towns/finances/lines.csv`, retain only the three approved workbook fields as displayed/active intake. Mark the two other revenue lines and four Annex lines retired from active intake/display, with their meanings retained in the catalog. Adapt the importer and promotion preservation path before applying those flags: currently the importer accepts non-intake workbook rows only when reviewed, while the owner requires Pending history to survive too. Preserve retired observations as explicitly historical records, replayable on a fresh database without reopening their intake or granting new automatic sign-offs. Prefer a clearly separated committed historical CSV input if keeping these rows in active extraction would undermine the intake gate. Do not delete or silently change any old value, review or citation. Keep Grants and Other Income source components in parser evidence because the full set is needed to reconcile the workbook's own printed Total Revenue.
3. **Retire active extraction, not source access.** Update normal batch extraction, staged promotion, single-file uploads and ZIP intake so Annex workbooks no longer generate new observations or upload choices. Skip withdrawn SCBAA sector extraction in the normal workflow; retain only checks actually needed by the selected revenue fields. Preserve document registration, original-file downloads, historical findings and reproducible historical evidence. The later cleanup decision requires deleting the forensic Annex reader too; preserve results and source evidence instead of its financial parsing logic. ZIP processing must ignore retired members without treating an otherwise valid FS upload as incomplete. No source bytes may be rewritten or recalculated.
4. **Keep human review effective for retained fields.** Confirm and cover the route from a parser finding to Report Differences, Pending review, source inspection, manual correction and independent authorized sign-off. In particular, `national_share_not_separate` / `may_include_national_share` must prevent automatic publication of an ambiguous tax definition: the current `CorroborateFinanceFigures::HOLDING_KINDS` does not include this warning. Repeating the same ambiguous amount in two reports or reconciling its statement total does not resolve its scope. Preserve existing human decisions; do not silently reverse them. Give reviewers the specific municipality, report year, affected fiscal year, source document/cell and reason. Do not add a new review system or blanket manual approval requirement for clean figures already covered by the approved automatic rules. Keep the existing centavo findings and at-most-one-peso automatic agreement policy.
5. **Update presentation without redesigning the screens.** Public Economy and Capitol Analytics show the three workbook fields, and Finance Data Entry removes the retired fields/upload choice. Keep the Executive Summary foundation, local cached selections, per-town fetch behavior and existing page layout. Update the Government Finances Guide and sources consistently. The three selected fields are not necessarily the whole of revenue: do not show a percentage composition or a stacked total implying they exhaust Income. Inspect the existing revenue stacked-area chart and use a presentation that makes the selected-series scope clear without an invented Other remainder. Keep government finance labels distinct from private-business sales and measured economic activity. N/A, Pending, hidden, loading and error states remain distinct from zero.
6. **Stage, inspect, then apply.** Run the narrowed extraction to a new ignored staging directory, print the diff and verify every changed retained amount against its original source cell. Separate removal from active presentation from destruction of historical records. Prove that Executive Summary values, source identities, manual decisions and source checksums remain unchanged. Only after this proof use the existing promotion/import workflow and refresh metrics; publishing fewer retired series is expected, altering the foundation's amounts is not. Do not run broad automatic corroboration merely to fill gaps. Record actual outcomes, remaining human-review cases and verification evidence here.

### Follow-up Acceptance and Verification

- [ ] Only the three approved workbook fields appear in active finance entry, public Economy, Capitol Analytics and their guide; Executive Summary fields stay intact.
- [ ] Normal batch, ZIP and direct upload flows produce no new Annex or withdrawn sector observations and do not emit fresh out-of-scope extraction warnings.
- [ ] Full revenue components still reconcile against the workbook's own printed Total Revenue; omitted display fields do not create a false mismatch or fake completeness.
- [ ] Every original file/hash, historical observation including Pending records, source citation and review decision survives the reduction and a repeat import. A fresh database can replay retained historical evidence.
- [ ] New non-intake observations remain refused; historical replay does not become a bypass for new retired data or changed evidence.
- [ ] Enrile's ambiguous tax/national split remains flagged for human review and is not guessed from Cash Flows. No automatic sign-off can resolve a definition ambiguity merely through repeated amounts.
- [ ] Historical retired findings remain inspectable and clearly distinguishable from current extraction outcomes. The 2,360 count is historical baseline evidence, not a target to reduce by suppressing active-field problems.
- [ ] Source-aware regression cases cover complete three-field revenue, nonzero undisplayed components, missing national share, unreadable formulas, retired Annex ZIP members, preservation of retired Pending/reviewed rows, stale-row cleanup, repeat import and human correction/sign-off permissions.
- [ ] Run `composer data:finances:test`, focused finance import/upload/corroboration/report-differences/analytics tests, and `composer ci:check` on PostGIS. If frontend/CSS/Vite files change, also run `npm run check`, `npm run types:check` and `npm run build`.
- [ ] Check public and Capitol views on desktop/phone and light/dark themes; verify loaded-data filters and Back/Forward cause zero application requests. Reuse the running dev server.
- [ ] Update metrics M21/M22, decisions, the workbook handoff, parser guides, finance data README, design guidance, roadmap and applicable agent rules for the approved scope; perform design/privacy reviews where required before any later commit.

The broader collection is 2014-2025, all 29 city/municipal governments, not Santo Niño alone and not the provincial government's accounts. Current XLSX coverage is not historical universal coverage. The 2025 extraction counts are Tax Revenue 29, Service and Business Income 29, National Collections 28, Other Income 24, Grants 16, Business Tax 13, Permit Fees 13, Market Operations 11 and Real Property Tax 9. Exact-match exported verified-review coverage observed during this session was 23, 23 and 22 governments for the three retained fields respectively; those counts can change after legitimate review. Never equate extracted, verified and semantically comparable coverage.

### Earlier Acceptance Checks

- [x] Remove the Executive Summary detail gate (`summary_detail_towns`, `summary_detail_lines`, reason `not_loaded`); tests updated.
- [x] Import and corroborate the newly loaded Executive Summary lines (D45), with reviewed figures and published statistics preserved.
- [x] Per-town loading for Economy and Capitol Analytics after the full catalog reached 3.3 MB (owner choice, 26-11-14).
- [x] Missing finance figures read N/A with the reason in the tooltip; row notes keep only coverage and restatement.
- [x] Government Finances Guide in the blur overlay replaces the Help sheet: contents links, why-it-matters lines, the four budget steps, Reading N/A; row labels and N/A cells open it at their entry.
- [x] Guide content, product guide and a coverage test (every displayed line and every reason explained).
- [x] Design, frontend, metrics M35, AGENTS and Home copy updated; D47 recorded.
- [x] Workbook script reliability: current inventory, `intake` gate in code, module split, Annex layouts, self-checks, one command (linked session, extractor 1.6.0).
- [x] Verification layer after the Enrile/Solana misattribution (extractor 1.7.0, 26-11-14): sheet-government check, content-based file type, cross-government duplicates, plausibility, and sign-off only on two-report agreement or newest-year reconciliation.
- [x] SCBAA totals and Capital Outlay investigation, reported before adoption: not adopted (see SCBAA Investigation).
- [ ] Focused tests, full CI, build and browser checks (public and Capitol, Abulug and the aggregate, 375 px, dark mode).

## SCBAA Investigation

"26-11-13", read-only. COA's Statement of Comparison of Budget and Actual Amounts was compared with the Executive Summary's Current Appropriations and Current Obligations for every Financial Statements workbook from 2014 to 2022 (155 files). Aparri 2022 matches to the cent (final 516,226,208.86, actual 300,507,842.91). Of the 78 totals that could be read, only 6 match both lines, 6 match appropriations only and 2 obligations only. The rest differ by amounts that suggest a different scope rather than a misreading, such as exactly ₱50,000,000 for Enrile 2015 and an actual above the final budget for Lal-Lo 2015. Another 53 layouts had unclear columns, 19 had no SCBAA sheet and 5 had no grand total. The SCBAA therefore cannot stand in for the Executive Summary's Budget Execution lines; 2023 to 2025 stay N/A with the reason "Not in the COA report for this year". Capital Outlay is printed only per sector, with no grand total, and shares the same scope uncertainty, so it is not proposed as a field.

## Change History

| Date | Change | Reason / Reference |
| :- | :- | :- |
| "26-11-14" | Approved three displayed workbook fields, retirement of active Annex extraction, preservation of source/history/checking components, and human review of uncertainty; detailed follow-up remains planning only | Owner follow-up after all-government coverage validation; Enrile demonstrates why extraction coverage alone does not establish comparable scope |
| "26-11-14" | Exact checks with a one-peso sign-off limit; Other Share joins the national share; revenue proven against the workbook's own total | Owner: flag every difference, even ₱0.01, sign off within ₱1.00, review anything larger; most Other Share lines are non-zero (62 of 102) |
| "26-11-14" | Verification layer: no figure shown on trust; newest year shown without a marker when it passes the rule | Enrile 2019 Annexes carried Solana's sheet; owner asked that the script prove its figures |
| "26-11-14" | Finances load per town: first page carries the Sum of Reporting Towns and the selected town; other towns fetch once and stay cached | All-town data made the page 3.3 MB and exhausted dev tools memory; owner chose per-town loading |
| "26-11-13" | Plan approved after the owner narrowed the trim to workbook fields, required governance and business purposes, a reliable script, and a fuller public guide | Owner review of the plan |

## Verification

Before: 1,702 published statistic rows, 4,847 figures (1,255 verified, 2,507 pending).

After import "26-11-13": 2,540 new Pending figures, none changed or removed; 1,255 existing sign-offs intact. `finances:corroborate` signed off 2,076 figures printed identically in two COA reports; `finances:export` added exactly 2,076 rows to `reviews.csv` and removed none. Assets is signed off for all 29 governments; published statistics grew from 1,702 to 2,749 rows.

Page size "26-11-14": Economy fell from 3.3 MB (HTTP 500 in local dev tools) to 0.43 MB; one town loads in 0.1 MB. Browser: selecting a new town made one request; Back and Forward made none.

Verification layer "26-11-14": Enrile 2019 Annexes (byte-identical to COA's official ZIP member) keep a sheet headed "LGU SOLANA, CAGAYAN" whose figures equal Solana's own reports; 1.6.0 read it as Enrile's (six figures, all Pending, never published). Fixed in 1.6.1 and found two more: Lasam 2014 Annexes (only sheet headed Allacapan, figures match neither, withheld) and Amulung 2021 (an Iguig budget sheet, not an intake field). After the fix no amount appears under two governments. Corrected Enrile figures are confirmed by two Enrile reports (2018 Permit Fees ₱690,980.00; 2019 ₱593,107.42). Sign-off under the new rule: 1,218 two-report figures and 139 newest-year figures (100 Executive Summary, 39 reconciled workbook revenue); 3,041 verified, 1,650 Pending, 3,565 published statistic rows. Plausibility holds 70 large swings for a person. Accuracy sample (`composer data:finances:sample`, 54 figures, every kind and era): 0 errors; about 300 are needed to show under 1%. Python 827 tests and corroboration tests (20, safeguard verified to fail when removed) passed.

Final rules "26-11-14" (extractor 1.7.1): every difference is a finding, even ₱0.01; at most ₱1.00 is a `minor_difference` that does not hold sign-off, and two reports at most ₱1.00 apart are signed off with the later amount and both amounts in the note (22 figures). Other Share from National Taxes is added into Share from National Collections: all 121 changed values were checked to equal the old value plus a cited cell labelled Other Share. Revenue completeness is proven against the workbook's own printed Total Revenue (125 workbooks also equal Executive Summary Income; 54 differ from it by COA's definition, not a misreading); a blank component counts as nothing only when that year's total proves it. Of 30 newly read figures, 27 reconcile to the centavo and 3 are held. Nine earlier automatic sign-offs that the new findings cover were returned to Pending. Result: 3,131 verified, 1,555 Pending, 3,657 published statistic rows, no amount under two governments; 2025 Revenue Sources cover 23 governments (from 10). Python 832 tests and 22 corroboration tests passed.


## Workbook Reduction Implementation "26-11-14"

Approved follow-up supersedes the earlier nine-field scope and the optional forensic-reader clause. Workbook reader 1.8.0 retains three active fields, internal complete-revenue checks and human review of uncertain definitions. Removed Annex, SCBAA and expense extraction, associated selectors, upload/result branches, retired UI/guide entries and the unused stacked-revenue chart variant. Financial Condition has its own stacked chart and stays intact. The dated history README is the removal ledger.

Frozen history holds 1,062 original observations and 3,577 baseline workbook findings with checksums. Importer replay preserves Pending/reviewed history on a fresh database; promotion cannot rewrite it. Source registers and public download routes are unchanged. Two Gonzaga source-label line breaks were normalized to spaces in active CSV metadata to avoid trailing whitespace; amounts, years, locations and source-cell inputs are unchanged.

Before/after complete staged runs proved identical 5,174 Executive Summary observations and 588 Executive Summary diagnostic rows. All 1,217 retained workbook observations have unchanged amounts, definition variants, citations and source-cell inputs; workbook parser-version metadata moves to 1.8.0. Twelve protected shared/PDF modules and 869 inventoried source/register files matched their baseline hashes. No source document identity changed. Pending findings remain intentionally unresolved.

Final checks, database preservation evidence, browser verification and measured code reduction are recorded below when complete.
