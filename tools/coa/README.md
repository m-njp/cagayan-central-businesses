# Finance Parser

Python reads official COA files; Laravel decides what may be stored. Parsing never verifies or publishes a figure.

## Follow One Upload

1. `UploadFinanceReport` stores the file privately and queues `ExtractFinanceUpload`.
2. The job calls `python -u -m coa.intake`. `-u` sends output immediately instead of waiting for a buffer to fill.
3. `intake.py` checks the file, selects the reader, and turns its figures into one consistent output format.
4. `summary.py` reads PDF financial highlights. It uses pdfplumber first, PDFium for missing lines and independent digit checks. Text is cached only for this invocation.
5. `workbook.py` scores XLSX sheets by their contents, reads each sheet once, and closes the workbook after selecting the sheets.
6. `progress.py` sends optional events through a callback: a function the reader calls when it has something to report. The same readers work without one.
7. Laravel's `FinanceParserStream` rebuilds complete JSON lines from process output chunks. `FinanceParsingProgress` stores a bounded snapshot that the dialog requests while open.
8. Only a validated final result reaches `RecordParsedFigures`. Eligible figures wait for a different person's verification.

Amounts use `Decimal`. Citations identify the PDF page or contributing workbook cells. The line catalog remains `data/towns/finances/lines.csv`.

## Run It

Run in PowerShell from the repository root. Composer uses the pinned environment under `tools/coa/.venv`.

```powershell
composer data:finances:test
$parserSource = Join-Path (Get-Location) 'data/towns/financial-statements/2025/Tuguegarao-City/Tuguegarao-City-FS-2025.xlsx'
$parserOutput = Join-Path (Get-Location) 'storage/app/private/parser-preview'
composer data:finances:parse -- "$parserSource" fs_workbook 2025 "$parserOutput" --members 40 --bytes 104857600 --events --pace 0.5
```

Kinds: `executive_summary` for PDF, `fs_workbook` or `annex_workbook` for XLSX, and `archive` for recognized workbooks inside ZIPs. Archive members use fixed output filenames. Without `--events`, intake prints one JSON result for compatibility.

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
