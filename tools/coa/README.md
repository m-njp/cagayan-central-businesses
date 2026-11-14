# Finance Parser

Workbook reading is retired, "26-11-14" (D49): the Executive Summary is the only source of figures. The parser reads Executive Summary PDFs only (version 1.7.1). Original workbooks stay downloadable; the retired reader is described in the [handoff](../../docs/engineering/coa-workbook-handoff.md).

For browser download checkpoints, validation batches of 12, retries and resume commands, see [COA PDF Collection](COLLECTION.md).

Annual Audit Report ZIPs have a separate [Workbook Collection](COLLECTION.md#workbook-collection) command: batches of 15, all available XLSX files retained, and successful ZIPs deleted after durable checkpoints. Financial parsing remains a separate step.

Python reads official COA files; Laravel decides what may be stored. Parsing never verifies or publishes a figure.


`composer data:finances:test` uses a short ignored temporary fixture directory and removes it after success or failure. Pass pytest `--basetemp` explicitly only when retaining synthetic fixtures for debugging. Tests run on every core through pytest-xdist; add `-- -n 0` to run them in one process.

## Follow One Upload

1. `UploadFinanceReport` stores the file privately and queues `ExtractFinanceUpload`.
2. The job calls `python -u -m coa.intake`. `-u` sends output immediately instead of waiting for a buffer to fill.
3. `intake.py` confirms the file is a PDF by its content and turns the figures into one consistent output format.
4. `summary.py` reads PDF financial highlights. It uses pdfplumber first, PDFium for missing lines and independent digit checks. Text is cached only for this invocation.
5. `progress.py` sends optional events through a callback: a function the reader calls when it has something to report. The same readers work without one.
6. Laravel's `FinanceParserStream` rebuilds complete JSON lines from process output chunks. `FinanceParsingProgress` stores a bounded snapshot that the dialog requests while open.
7. Only a validated final result reaches `RecordParsedFigures`. Eligible figures wait for a different person's verification.

Amounts use `Decimal`. Citations identify the PDF page. The line catalog remains `data/towns/finances/lines.csv`.

## Automated Confidence

Fixed "26-11-12": the upload job's previous-figure omission check ignores catalog lines that are not eligible for intake (check-only totals such as `fs.total_revenue`), so they no longer cause a false 0. Genuinely omitted eligible figures still force 0. See the [archived fix](../../docs/plan/archive/26-11-12-coa-confidence-eligibility-fix.md).

Version 1.3.0 returns a report-level `confidence` score in the final upload result and the existing blur overlay. Start at 100, subtract 10 for each distinct warning and 25 for each distinct error, with a floor of 0. Info messages do not deduct points. The same finding from both readers counts once, using its strongest severity; document kind, issue kind, line, fiscal year and page distinguish findings. No extracted figures means 0. A failed job or an additional server-side integrity error also means 0. Earlier uploads without a score show no percentage until reparsed.

This is a transparent check score, not a measured probability of correctness. 100 means all configured checks passed; a shared reader mistake or an incorrect source may still escape detection. Below 100, the overlay asks the reviewer to cross-check flagged amounts and source pages. Scores never verify figures, override hard extraction errors, or require an approval note.

The existing libraries remain sufficient for this slice: [pdfplumber](https://github.com/jsvine/pdfplumber) supplies text/table evidence, and [pypdfium2](https://pypdfium2.readthedocs.io/en/stable/python_api.html) supplies another reading engine. Agreement now requires the same signed amount, line, fiscal year, definition and page, rather than the same absolute number anywhere. Malformed monetary cells, ambiguous years/sheets, conflicting duplicate rows, unsupported scales and incomplete component sums are flagged instead of guessed. Original PDFs and readable candidate evidence remain available for verification. New packages, OCR and learned confidence models are deferred until a demonstrated gap justifies them.

Version 1.4.0, "26-11-12" ([plan](../../docs/plan/archive/26-11-12-coa-accuracy-and-differences.md)):

- **One source of truth.** `composer data:finances:extract` always writes the committed CSVs, then prints what it added, removed and changed against the files it replaced (also in `logs/extract.log`). Read that diff before `composer data:finances`. Findings stay in `issues/` and nothing is published until signed off. `composer data:finances:audit` remains for a staged, read-only comparison. For workbook figures use `composer data:finances:workbooks`, which stages and diffs by default and promotes only with `--promote` ([XLSX.md](XLSX.md)).
- **Finding families.** Each issue row has a `family`: `report` (the document disagrees with itself as printed), `between_reports` (two reports differ) or `reading` (the parser's limit). `FAMILIES` in `coa/summary.py` is the one list. A row withheld because its cells wrap or split is `incomplete_row` (reading); only a line with a provably misprinted amount, such as `48,014,545,75`, is `malformed_amount` (report).
- **Reader choice.** A printing error in the report no longer switches readers. The fallback reading wins only with fewer missing core lines or more figures.
- **Confirmation.** The second reader must agree on line, year, sign, definition and page. When it cannot rebuild the row at all, the row's amounts must still be printed together on one line of that page whose label names the same line (pdfium splits Gattaran 2020's table).
- **Layouts.** A highlights heading with no rows is skipped for the next one (Lal-Lo 2014). A spaced dash after a word is label punctuation (Peñablanca 2022), and a dangling qualifier withholds the row instead of reading the label hyphen as a not-reported cell (Santo Niño 2019). The introduction's own town outranks an audit scope sentence naming another town, with an `identity_text_conflict` warning (Lasam and Pamplona 2017). A digit the text layer spaces off its group is joined only where the other reader prints the joined amount (Solana 2014). Findings state what is printed in pesos ("The change column prints ₱21,271,960.61, but 2016 minus 2015 is ₱26,039,217.37"). A second budget header one year behind is restored only when the previous report proves it (`header_years_stale`, Alcala 2021).

Version 1.2.0 adds historical Executive Summary layouts: `P=` currency, ungrouped monetary digits, unmarked percentages and longer wrapped labels. Readable current/prior amounts survive a malformed trailing change or percentage, with an explicit warning. Main monetary typos are never inferred. The "26-11-12" batch and its pending-review counts are recorded in [Government Finances](../../data/towns/finances/README.md#historical-and-remaining-2025-batch-26-11-12).

## Run It

Run in PowerShell from the repository root. Composer uses the pinned environment under `tools/coa/.venv`.

```powershell
composer data:finances:test
$parserSource = Join-Path (Get-Location) 'data/towns/executive-summaries/2025/Tuguegarao-City/Tuguegarao-City-Executive-Summary-2025.pdf'
composer data:finances:parse -- "$parserSource" 2025 --events --pace 0.5
```

Only an Executive Summary PDF is read; a workbook or archive is refused by its content. Without `--events`, intake prints one JSON result for compatibility.

With events, each line is one JSON object. `stage` and `scan` describe actual work; `figure` is provisional; `replace` replaces that document's provisional figures after checks; `issue` reports a source check; `result` carries the final result exactly once. A fatal file error emits `failure` and exits nonzero. Library diagnostics go to stderr, outside the progress feed.

Interactive uploads show actual page, sheet and matched-cell events at a half-second pace, requested by the owner on "26-11-08". Pacing is optional and spends at most 20 seconds per run (`PACE_BUDGET_SECONDS` in `coa/intake.py`). It changes when events appear, never the figures or validation. Normal CLI parsing does not wait unless `--pace` is supplied. The upload shows a blurred full-page overlay with the last seven real events (time, source location, line, exact amount). Parse Again starts a fresh attempt on the stored file and keeps the same verification rules.

## Verify a public report archive

Download the ZIP from About the Data, then run `composer data:finances:check-archive -- path/to/cagayan-coa-reports.zip`. The checker compares every ZIP entry against its manifest and the approved checksums in `data/towns/finances/documents.csv`. It exits with an error when a report differs, an entry is missing, or the archive contains an unexpected file. Add `--events` for JSON progress lines using the parser's event helper. The web download uses PHP's `zip` extension and the same approved checksums. The Python checker runs locally with the standard library.

## Troubleshoot

- **Waiting:** run `composer dev`, which includes the queue worker. Tasks that do not start become retryable after the configured queue wait.
- **Disconnected:** reconnect in the dialog. Closing it leaves the server job running.
- **Interrupted:** retry the stored file. A new attempt UUID prevents the earlier worker from changing the new attempt.
- **Warnings or missing sections:** check the cited page or cells. Readable figures remain pending; unsupported layouts do not become zeroes.
- **Unreadable file:** check the selected kind and source, then retry or use Enter Report.
- **Python cannot start:** check `COA_PYTHON` and the pinned environment. Tracebacks and server paths stay in Laravel's server log.
- **pytest temporary-directory permissions:** use `composer data:finances:test -- --basetemp <new-writable-directory>`. Pytest cleans this directory; never point it at existing work.

Bounds, progress limits, polling and process timeouts live in `config/cagayan.php`. Queue reservation exceeds the job timeout. No OCR or new dependency is required.
