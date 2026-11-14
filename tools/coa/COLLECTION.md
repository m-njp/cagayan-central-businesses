# COA PDF Collection

For all-XLSX extraction from Annual Audit Report ZIPs in batches of 15, see [Workbook Collection](#workbook-collection). The PDF workflow below retains batches of 12.

The tracked collector checks locally saved Executive Summary PDFs. The browser still downloads from observed COA links, sequentially, with the previously recorded 3.5-second minimum between starts. This command makes no HTTP requests. The original browser download automation was not saved, so call `completed` immediately after each browser save and `failed` after a download error.

For automatic checkpoints without a command after every download, use the browser folder watcher below instead of `completed`.

## Automatic Browser Connection

The agent first inventories the observed COA links, then starts one watcher pointed at the browser's actual download directory. The user does not run per-file commands. The watcher recognizes expected filenames after two stable observations (normally within one second), skips `.crdownload`, `.part` and `.download` transfers, and saves a durable checkpoint for each completed PDF. The existing batch collector validates every 12 files with isolated failures and bounded local workers.

```powershell
composer data:finances:collect -- --inventory $coaInventory --state $coaState watch --downloads 'C:/Users/ninomarcos/Downloads' --finish-file 'storage/app/source-review/coa/browser-run-finished.flag'
```

Start the watcher once, before the browser download queue. Use a fresh finish-marker path for each browser run. After the browser has finished its queue, the agent creates that marker; the watcher checks the last partial batch, records expected PDFs that never arrived as individual download failures, writes reports and exits. Without a marker it finishes after 120 seconds with no relevant file activity. `--idle-seconds` and `--poll-seconds` adjust these intervals. An interruption flushes recorded files and leaves unrecorded IDs resumable.

Use the exact saved filenames and folder returned by the browser. A unique match may also ignore accents, capitalization, punctuation and repeated spaces, such as Niño versus Nino. Ambiguous matches are left unrecorded rather than guessed. If the browser adds a duplicate-number suffix or uses another directory, update the inventory/folder before starting the queue. A saved PDF with a wrong byte count or invalid structure is recorded as a validation failure while the rest continue. The watcher makes no COA network requests and does not solve browser security challenges.

The marker is a local workflow signal, not an application endpoint. No web server, browser extension installation or additional package is required. The watched inventory remains fixed during one run; use a separate run per discovered year or resume with an updated inventory. Browser errors with no saved file are recorded at the end as missing downloads. The watcher holds the journal's writer lock; issue `failed` with a more specific browser error after the watcher exits, rather than opening a second writer.

The example uses `$coaInventory` and `$coaState`, defined in the next section. The agent starts and finishes this process for a managed collection; the commands document how the connection works.

## Collection Commands

Run all commands in PowerShell from the repository root. Use one inventory and checkpoint journal per collection. Inventory records contain `id`, `category`, `title`, `url`, `fileName` (a basename), and either `size` (an exact advertised byte count) or `sizeLabel` (COA's displayed size, such as `160 KB`). An optional `sha256` is checked when supplied. Titles must identify an Executive Summary and fiscal year. Preserve the exact observed COA URL; do not construct one from an ID.

An exact `size` must match byte for byte. A `sizeLabel` uses binary units and allows one unit of the displayed precision for rounding; it is never reported as an exact advertised-byte match. The verified journal entry records `listedSizeCheck` as `exact_bytes` or `rounded_display`, plus the saved PDF's exact byte count and SHA-256. Preserve `sourceTitle` and page evidence when a mislabeled COA entry is classified from the PDF itself.

Before each browser selection, wait for the tree's loading indicator to disappear and inspect the current category state. Use explicit observed category and file IDs; expanding a year can shift other rows while loading. For a download, wait for `#tree-wpfd-box[data-id="<observed-id>"] .downloadlink`, require its URL to equal the inventoried official PDF URL, and only then call `downloadMedia`. Never use the first generic popup download link: an adjacent annual-report ZIP can remain visible during a popup transition. The browser controller retains the 3.5-second minimum between download starts and stops for security challenges rather than bypassing them.

```powershell
$coaInventory = 'storage/app/source-review/coa/executive-summaries/coa-executive-summaries-2024-2025-inventory.json'
$coaState = 'storage/app/source-review/coa/collection-2024-2025.jsonl'

# Immediately after the browser saves this PDF:
composer data:finances:collect -- --inventory $coaInventory --state $coaState completed 95272 'C:/Users/ninomarcos/Downloads/Tuguegarao-City-Executive-Summary-2024.pdf'

# If a browser download fails, record it and continue with the next file:
composer data:finances:collect -- --inventory $coaInventory --state $coaState failed 95277 'Browser download timed out'

# At the end, validate the final batch even when it has fewer than 12 PDFs:
composer data:finances:collect -- --inventory $coaInventory --state $coaState flush

# Rebuild the current report after a restart without validating files:
composer data:finances:collect -- --inventory $coaInventory --state $coaState status
```

Every completed download appends and flushes its own checkpoint to disk before validation. Twelve pending PDFs trigger one batch, using up to four independent local workers. Each failure is journaled with its ID, stage and error; the other files finish. Retrying a file appends a new event, preserving the failure history. An OS lock prevents overlapping commands from corrupting the journal and is released on process exit. An interrupted final journal line is discarded on restart; earlier corruption is reported rather than ignored.

Each checked batch writes `<state>.report.json` (replacing the `.jsonl` suffix), with verified files, individual failures, pending IDs and unrecorded IDs. It also writes per-year `inventory.json` and `manifest.json` under `<state-stem>-manifests/`. Only verified files enter these manifests; the full inventory remains available for coverage checks. Use `--manifest-root <folder>` before the subcommand to choose another manifest directory. `completed` and `failed` append checkpoints immediately; reports refresh at batch boundaries, `flush`, `scan` or `status`.

Atomic report replacement retries temporary Windows file locks up to six attempts, waiting at most 0.75 seconds in total. A persistent lock still raises an error and preserves the previous report; the per-download journal remains the recovery source.

Exit codes: **0** means no recorded failures, **1** means individual file failures were recorded and processing continued, **2** means a command, inventory or journal error. A browser queue must continue after code 1, and stop to investigate code 2. After fixing a download, call `completed` again with that ID and path.

## Resume Existing Downloads

```powershell
composer data:finances:collect -- --inventory $coaInventory --state $coaState scan --downloads 'C:/Users/ninomarcos/Downloads'

# Explicitly retry unchanged PDFs that failed validation:
composer data:finances:collect -- --inventory $coaInventory --state $coaState scan --downloads 'C:/Users/ninomarcos/Downloads' --retry-failed

# Force every local file through validation again for a final integrity review:
composer data:finances:collect -- --inventory $coaInventory --state $coaState scan --downloads 'C:/Users/ninomarcos/Downloads' --recheck
```

Scanning checks groups of 12 and flushes the remainder automatically. Missing files are recorded individually. Unchanged verified files reuse their recorded result based on resolved path, size, modification time, creation/change time and inventory metadata. Changed files are checked again. This cache avoids rereading PDFs during routine resumes; use `--recheck` for a fresh byte-level review. `--workers 1` through `--workers 4` before the subcommand controls local worker count. Worker count does not change COA request concurrency.

Validation checks the official HTTPS URL, title/year format, listed size, PDF header/EOF, SHA-256, nonempty page tree, positive page dimensions and decoding of every content stream. It avoids text layout extraction and financial parsing. These checks establish file integrity, not financial accuracy, and do not identify a wrong municipality printed inside an otherwise valid PDF. Retain the separate canonical municipality coverage check and human source review.

## Legacy Local Helpers

The ignored `storage/app/source-review/coa/build_download_manifests.py` entry point now delegates to this collector, using `collection.jsonl` and the existing `executive-summaries/` manifest folder. It continues past missing or invalid PDFs. The local organizer now records validation/copy errors per file rather than aborting the whole invocation, reuses this validator and starts the pinned Python environment instead of requiring an additional pypdf installation. These ignored compatibility edits are local to this checkout; the versioned collector, tests and this guide are the portable workflow.

The existing `summary_coverage.py` accepts `--inventory` and `--manifest` paths to the generated per-year files. It still reconciles all 29 geographic identities. The organizer accepts `--manifest` pointing to a generated per-year manifest; never feed it a combined multi-year manifest. Organizer output describes the supplied manifest, so use the full inventory coverage report to distinguish failed/missing downloads from unavailable reports. No collection command imports or publishes finance figures.

## Workbook Collection

`composer data:finances:collect-workbooks` reuses the PDF collector's journal, lock, atomic reports, browser watcher and bounded workers. It performs no network requests. The browser downloads sequentially, with the existing 3.5-second minimum between starts, using the observed official ZIP URL bound to each file ID. Inventory links must be discovered from COA, never guessed.

Each inventory entry contains `id`, `title` ending in `Annual Audit Report <year>`, `url`, `fileName`, `psgcCode`, `townFolder` and either `size` or `sizeLabel`. Preserve the original title, URL and displayed size. `fileName` is the actual browser save basename, which can differ from the URL filename. Unique normalized matches tolerate accents, punctuation and case; ambiguous matches are not guessed. Inventory order determines queue order: 2025 back through 2020 for the approved collection. Each year covers Cagayan's 28 municipalities and Tuguegarao City; province reports are excluded.

```powershell
$coaWorkbookInventory = 'storage/app/source-review/coa/excel-collection/inventory-2025.json'
$coaWorkbookState = 'storage/app/source-review/coa/excel-collection/collection-2025.jsonl'
$coaWorkbookOutput = 'storage/app/source-review/coa/excel-collection/workbooks'

composer data:finances:collect-workbooks -- --inventory $coaWorkbookInventory --state $coaWorkbookState --output $coaWorkbookOutput --members 40 --bytes 104857600 watch --downloads 'C:/Users/ninomarcos/Downloads' --finish-file 'storage/app/source-review/coa/excel-collection/finished-2025.flag'
```

Use a fresh finish marker per watcher run. The agent starts the watcher before its queue and creates the marker when downloads finish. The example limits are the current `cagayan.finances.uploads.archive_members_max` and `archive_bytes_max`; supply those configured values rather than inventing new thresholds. `--workers` accepts 1 through 4 and affects local checks only.

The separately authorized historical collection runs 2019 back through 2014, for the same 29 governments, under `storage/app/source-review/coa/excel-collection-2014-2019/`. Substitute that directory, the observed year inventory and a fresh finish marker in the command above. Its sources remain separate from the completed 2020–2025 collection. Both collections use the unchanged shared ZIP collector and batches of 15. [Completed historical collection record](../../docs/plan/archive/26-11-13-coa-excel-collection-2014-2019.md).

Before historical acquisition, document the current parser/collector version and findings in the implementation record. Save observed source titles, actual URLs and rounded displayed sizes before preparing each year's inventory. The local `prepare_inventory.py` helper maps canonical aliases and refuses incomplete or duplicate geographic coverage; it does not discover URLs or download files. Browser-save basename matching may omit numeric URL revision suffixes, but source URLs stay verbatim and each completed checkpoint records the actual download path. Close the previous popup before opening another, and require both popup ID and download URL to match the intended source.

Every stable completed ZIP receives a flushed checkpoint immediately. A directly written ZIP must have its end-of-archive record before the watcher considers it complete. Every 15 pending ZIPs trigger validation and extraction, with isolated failures. The final partial batch is flushed on completion, idle timeout or a normal interruption. Forced termination leaves journaled work resumable.

Validation checks the official HTTPS URL, advertised size, source fingerprint and optional checksum, archive CRCs, safe paths, duplicates and extraction bounds. Only `.xlsx` entries, case-insensitively, are staged; `.xls`, `.xlsm`, PDFs, nested ZIPs and other content are never extracted. Each workbook passes internal ZIP/size checks and an openpyxl worksheet scan without being rewritten. This verifies readable files, not financial accuracy. Original member names, exact bytes, SHA-256, source URL and checkpoint timestamps are recorded.

Retained files are grouped as `<output>/<year>/<townFolder>/<id>-<archive-hash-prefix>/<original-member-path>`. Nested paths distinguish equal basenames. Existing different files are never overwritten. A durable success checkpoint precedes deletion of the downloaded ZIP. A readable ZIP with no XLSX records `no_xlsx` before deletion. Validation failures retain the ZIP; cleanup failures retain verified results plus `cleanupError` and are retried by `flush`. Deletion applies only to that inventoried download path and only while its fingerprint and checksum still match.

Use the same inventory, state, output and limits with these subcommands:

- `completed <id> <actual-path>` checkpoints an explicit browser save and checks a full batch if ready.
- `failed <id> <error>` records a browser failure without stopping other files.
- `flush` checks the remainder and retries cleanup.
- `status` rebuilds reports without extracting or deleting.
- `scan --downloads <folder> --retry-failed` resumes already downloaded ZIPs and flushes the remainder. Use exact saved basenames in the inventory for scans.

An unchanged successful entry whose ZIP was deleted is reused while retained workbook fingerprints and the output folder match. Changed or missing workbooks invalidate that cache and require restoring the source ZIP. Reports and per-year manifests retain source provenance after ZIP deletion. Exit codes are 0 for no recorded failures, 1 for file or cleanup failures, and 2 for invocation/inventory/journal errors. Continue the queue after code 1; investigate code 2. No command parses financial figures or imports, verifies or publishes ledger records.
