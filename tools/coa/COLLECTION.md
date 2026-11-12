# COA PDF Collection

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
