---
title: "COA Excel Collection for 2014–2019"
created: "26-11-13"
updated: "26-11-13"
status: completed
branch: "chore/maintainability-refresh"
base: staging
predecessor: "../archive/26-11-13-coa-excel-collection.md"
---

# COA Excel Collection for 2014–2019

## Objective and Authorization

On "26-11-13" the owner explicitly directed the collection sub-agent to download and extract COA Annual Audit Report ZIPs for 2014 through 2019 while the main agent checks parsing and ingestion. Continue newest first, from 2019 back through 2014, for all 29 Cagayan local governments. This is additional collection scope beyond the completed [2020–2025 collection](../archive/26-11-13-coa-excel-collection.md).

## Scope and Acceptance

- [x] Discover observed official COA ZIP links and reconcile availability for all 29 canonical geographic identities in each year.
- [x] Download sequentially with existing minimum 3.5-second pacing and immediate durable checkpoints.
- [x] Reuse the existing ZIP collector with batches of 15, bounded local workers, partial flush and resumable failures.
- [x] Retain all available nested `.xlsx` files only; preserve original member paths, exact bytes, checksums and source provenance.
- [x] Reject unsafe archive paths and enforce configured extraction bounds. Keep failed archives for retry; delete successful ZIPs only after durable success, including recorded readable no-XLSX outcomes.
- [x] Reconcile every retained file against manifests and every government/year against observed availability.
- [x] Verify that the existing 300 workbooks from 2020–2025 remain unchanged.
- [x] Maintain a readable collection report and exact resume commands, then archive only after acceptance checks pass.

## Boundaries and Storage

Use separate ignored storage at `storage/app/source-review/coa/excel-collection-2014-2019/`. The completed `excel-collection/` sources are read-only. Do not collect Executive Summary PDFs in place of Annual Audit Report ZIPs. Financial parsing, imports, sign-offs, metric refreshes, publication, new packages, commits and pushes are excluded.

The [collection guide](../../../tools/coa/COLLECTION.md#workbook-collection) and [collection rules](../../../.claude/rules/coa-collection.md) apply. PDF batches remain 12; ZIP batches remain 15.

## Dated Progress and Evidence

| Date | Progress | Evidence |
| :- | :- | :- |
| "26-11-13" | Owner explicitly authorized historical ZIP collection by sub-agent | Parent-assigned 2014–2019 scope; separate collection storage |
| "26-11-13" | Started official-link discovery | CUA has native Brave available but no enabled browser surface; built-in browser creation reports unavailable |
| "26-11-13" | Acquisition is pending browser access | Official listing retrieval returned HTTP 403; native Brave app approval timed out after 300 seconds; no historical ZIP URLs or downloads discovered |
| "26-11-13" | Owner directed the main agent to continue acquisition and document changes/findings first | Resume this plan; prior access failures remain history, not proof of current access or report unavailability |
| "26-11-13" | In-app browser became available; official listing and 2019 Region II branches loaded normally | Observed categories 203 (2019), 238 (Region II), 254/326 (municipal Cagayan), 253/323 (city Cagayan); 29 actual ZIP links inventoried |
| "26-11-13" | Started historical downloads and immediate checkpointing | First saved ZIP: Tuguegarao City 2019, observed file ID 3540; existing collector reported one pending completed download |
| "26-11-13" | Completed 2019 collection | 29/29 verified ZIP outcomes, 45 retained XLSX, two readable no-XLSX archives, no failures/pending/unrecorded; final partial batch flushed |
| "26-11-13" | Completed 2018 collection | 29/29 verified ZIP outcomes, 46 retained XLSX, two readable no-XLSX archives, no remaining failures; final partial batch flushed |
| "26-11-13" | Completed 2017 collection | 29/29 verified ZIP outcomes, 12 retained XLSX, 22 readable no-XLSX archives, no failures/pending/unrecorded |
| "26-11-13" | Completed 2016 collection | 29/29 verified ZIP outcomes, 43 retained XLSX, four readable no-XLSX archives, no failures/pending/unrecorded |
| "26-11-13" | Completed 2015 collection | 29/29 verified ZIP outcomes, final partial batch flushed; Iguig's changing browser-save fingerprint was retried successfully without a new download |
| "26-11-13" | Completed 2014 collection and initial full independent audit | 29/29 verified ZIP outcomes, 40 retained XLSX, four no-XLSX archives; all six years total 174 verified outcomes and 221 readable workbooks |

### Documentation Before Resuming

The completed [XLSX parser implementation](../archive/26-11-13-xlsx-parser-hardening.md) records version 1.5.0, the 300-file baseline/comparison, source-cell inspections, all-sheet zero-result review, 789 Python tests and full CI (481 Laravel tests). The [XLSX guide](../../../tools/coa/XLSX.md) describes strict amounts, formula caches/errors, year/fund selection, extraction versus report findings, unchanged-source checks and staged review boundaries. The [collection guide](../../../tools/coa/COLLECTION.md#workbook-collection) records the separate ZIP workflow, checkpoint reuse, batches of 15, safe extraction and deletion rules. No historical collection script change is claimed before it is implemented and verified.

Historical collection uses separate inventories and journals for each year under `storage/app/source-review/coa/excel-collection-2014-2019/`, with workbooks beneath its `workbooks/` directory. Discover actual official links before creating inventory entries. Start each year's watcher before its sequential download queue; checkpoint each completed save, extract batches of 15 and flush the final partial batch. Reconcile 29 geographic identities per year and account separately for download failures, readable no-XLSX archives and genuinely observed unavailable entries. Preserve the existing 300-file collection and canonical finance/source registers. No financial parsing or ingestion of newly collected historical workbooks is authorized here.

2019 acquisition findings: Alcala (ID3610) and Rizal (ID3597) URLs include a `-2` suffix absent from the actual browser-saved basename. Updated only those inventory basenames from observed saves and resumed the existing watcher; validated successes were reused. Popup overlays must be closed before opening the next file, and both popup ID and actual URL are checked. The current collector required no code change. Source title/URL/displayed-size observations remain in `observed-2019.json`, the PDF mislabel in `discovery-findings-2019.json`, and durable results in `collection-2019.jsonl` and `collection-2019.report.json`.

Before the 2018 queue, saved the local, network-free `prepare_inventory.py` acquisition helper alongside the observed inventories. It maps source titles through the existing canonical town aliases, rejects non-ZIP entries, requires exactly 29 distinct geographic identities and refuses to overwrite an existing inventory. Based on the observed COA browser-save behavior, its matching basename omits numeric URL revision suffixes; source URLs are preserved verbatim and actual saved paths remain in each checkpoint. The first 2018 saves confirmed this behavior for Santo Niño, Santa Teresita and Rizal. This is local acquisition metadata preparation, not a change to the shared collector/parser. Independent verification is maintained under `verification/`; its 2019 report confirms all prior 300 originals, 28 canonical CSVs and three registers remain unchanged.

2018 acquisition finding: Gonzaga ID8923 initially failed the recorded fingerprint/listed-size guard while its browser save was still changing. The watcher observed the changed fingerprint, checkpointed/retried the same downloaded ZIP and completed validation successfully. The journal preserves the initial failure and later success; no re-download or weakened check was needed. The final 2018 report has zero failures/pending/unrecorded and all 29 ZIP outcomes.

## Verification and Handoff

Acquisition and extraction completed for all six years. Independent final source and resume verification is recorded below. The earlier access failure does not represent missing or unavailable reports.

### Access Recovery and Initial Failure History

Superseding update "26-11-13": The in-app browser is now reachable. Normal navigation loaded the official listing and observed historical report tree. The first 2019 ZIP downloaded successfully and received its immediate checkpoint. The earlier HTTP403/native-approval failures below are historical acquisition evidence, not a current blocker. `observed-2019.json` preserves 30 observed entries: 29 ZIPs mapped to canonical identities plus one PDF mislabeled as an Annual Audit Report (Sanchez Mira, ID3582), recorded separately and excluded from the ZIP queue. Actual URLs and displayed rounded size labels are preserved; no links were guessed.

Observed official listing: `https://www.coa.gov.ph/reports/annual-audit-reports/aar-local-government-units/`.

Read-only web retrieval returned `(403) Forbidden`. CUA reported no enabled browser surfaces; `cua.createBrowserTab('iab', officialListing, {visible:false})` returned `Browser is not available: iab`. The native Brave window existed on its existing LinkedIn tab. Binding that window with `cua.getApp({windowId:66868})` waited 300 seconds for Computer Use app approval and returned `Computer Use app approval timed out`. No native browser navigation or tab modification was performed, no challenge was solved, and the timed-out approval call will not be repeated automatically.

At the initial failed attempt, historical discovery stood at **0 observed ZIP URLs and 0 downloads**. Availability for all 174 government/year combinations was unobserved, rather than unavailable. The blocked listing response contained no year, Region II or Cagayan entries usable as source evidence. An official-domain search did not provide a historical Cagayan ZIP URL. This initial state was superseded by the successful browser acquisition above.

The main agent later obtained a working in-app browser, navigated the official year/Region II/Cagayan branches, and completed all 174 downloads. Existing 2020–2025 collection artifacts and the 300 original workbooks remain untouched; no parser, import, sign-off, refresh or publication action is part of this acquisition task.

Read-only SHA-256 verification after the access failure matched all 300 existing workbooks against their completed collection manifests. `git diff --check` passed. No collection or parser code was changed by this historical discovery attempt.

The initial acquisition blocker is preserved in `storage/app/source-review/coa/excel-collection-2014-2019/ACCESS-STATUS.md`; final results and current status are under `verification/` and the per-year collection reports.

### Final Collection Outcomes

| Report Year | Governments and ZIPs | Retained XLSX | Readable ZIPs Without XLSX |
| :- | -: | -: | -: |
| 2019 | 29 | 45 | 2 |
| 2018 | 29 | 46 | 2 |
| 2017 | 29 | 12 | 22 |
| 2016 | 29 | 43 | 4 |
| 2015 | 29 | 35 | 6 |
| 2014 | 29 | 40 | 4 |
| Total | 174 | 221 | 40 |

All six final reports have zero failures, pending downloads or unrecorded entries. Every successful ZIP was deleted after its durable success checkpoint, including the 40 explicit `no_xlsx` outcomes. Retained original workbooks total 48,433,955 bytes. No `.xls`/`.xlsm` conversion or financial interpretation was performed; a no-XLSX archive is a collection outcome rather than a claim about absent government finances.

The final independent audit checks canonical geographic coverage, inventory/journal/report agreement, official source provenance, original archive member paths, safe unique destinations, workbook size/fingerprint/SHA-256, internal ZIP and worksheet readability, exact physical workbook inventory, and absence of all 174 processed ZIP paths. It also authenticates the earlier parser protection snapshots and verifies unchanged hashes for the 300 existing originals, 28 canonical finance CSVs and three source-register CSVs. Final artifacts: `verification/REPORT.md` and `verification/verification.json` beneath the historical collection directory.

### Acquisition Findings and Script Boundaries

- The shared collector and parser required no changes during historical collection. Existing 15-ZIP batches, bounded local validation, immediate checkpoints, safe extraction, final partial flush and guarded cleanup were reused.
- COA source URLs were read from file-ID-specific popups. URL revision suffixes remain in provenance while actual browser save names are recorded. Popup overlays are closed before another file is selected.
- The 2015 discovery initially exposed two Region II links because the page also expanded 2025. DOM ancestry identified the 2015 branch before selection. Subsequent navigation was scoped to the observed year parent; no report links were inferred from category numbers.
- Three initial fingerprint failures were retained and then recovered by the watcher from the same saves: Gonzaga 2018 (ID8923), Iguig 2015 (ID29749) and Iguig 2014 (ID45221). Journals preserve failure and success. Checks were not weakened and no replacement download was needed.
- The local metadata preparation and independent verification helpers write only ignored review artifacts. Their purposes and limitations are documented above; they do not import, sign off, refresh metrics or publish figures.

### Reproduction and Safe Resume

The original acquisition used the existing browser session and the observed official URLs. For each year, the agent started this watcher before its sequential download queue (2019 had documented filename-metadata resumes), maintained at least 3.5 seconds between download starts, then created the corresponding fresh finish marker after its queue ended:

```powershell
$historicalCoaRoot = 'storage/app/source-review/coa/excel-collection-2014-2019'
$historicalCoaYear = 2014
composer data:finances:collect-workbooks -- --inventory "$historicalCoaRoot/inventory-$historicalCoaYear.json" --state "$historicalCoaRoot/collection-$historicalCoaYear.jsonl" --output "$historicalCoaRoot/workbooks" --members 40 --bytes 104857600 watch --downloads 'C:/Users/ninomarcos/Downloads' --finish-file "$historicalCoaRoot/finished-$historicalCoaYear-new-run.flag" --idle-seconds 600

# After that browser queue finishes, the managing agent creates its marker in a separate terminal:
New-Item -ItemType File -Path "$historicalCoaRoot/finished-$historicalCoaYear-new-run.flag"

# Completed collections reuse success checkpoints and deleted ZIPs:
composer data:finances:collect-workbooks -- --inventory "$historicalCoaRoot/inventory-$historicalCoaYear.json" --state "$historicalCoaRoot/collection-$historicalCoaYear.jsonl" --output "$historicalCoaRoot/workbooks" --members 40 --bytes 104857600 scan --downloads 'C:/Users/ninomarcos/Downloads'

& tools/coa/.venv/Scripts/python.exe -B "$historicalCoaRoot/verification/verify.py" --require-all --resume-proof
```

Select each observed year inventory, 2019 back through 2014. Do not reuse a finish marker or run a second writer against the same journal. Successful resume performs no new download or extraction. A missing or changed retained workbook invalidates that success and requires restoring its observed source ZIP. The pinned Python environment and existing configured 40-member/100-MiB limits were reused; no packages were added.

### Final Acceptance Evidence

Completed "26-11-13". The independent `--require-all --resume-proof` run passed all six years with 174 canonical government/year outcomes, 221 readable XLSX originals, 48,433,955 retained bytes, 40 no-XLSX outcomes and zero verification failures. All 174 success checkpoints were reused through the real collector `completed()` method with validation, cleanup, checkpoint writes and report exports guarded to fail; none was attempted. Every journal and retained workbook hash/fingerprint remained unchanged. The verifier also rechecked the protected 300 earlier originals, 28 canonical finance CSVs and three source registers.

The main agent's browser tool outputs showed 174 successful saved paths and the final aggregate `savedHistoricalZips: 174`; each of the three later local fingerprint retries had one browser download. This agent observation, distinct from an independent network capture, is recorded in `browser-queue-receipt.json`. The independent journal checks establish same-path, same-size, same-creation-time retry consistency and later stable modification timestamps. Both evidence types are kept separate.

The historical run made no change to the shared collector/parser. Those same current modules had already passed 789 COA Python tests and full repository CI (481 Laravel tests, 3,517 assertions) during the completed [parser implementation](../archive/26-11-13-xlsx-parser-hardening.md). This collection added only source-review acquisition/verification artifacts and documentation; no repeat financial extraction, imports, sign-offs or refreshes were run. Composer validation, document/link checks and `git diff --check` passed at closure. Existing unrelated uncommitted work remains preserved; no commit or push was performed.
