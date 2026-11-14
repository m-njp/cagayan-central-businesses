---
paths:
  - "tools/coa/**"
  - "storage/app/source-review/coa/**"
---

# COA Source Collection

Corrections from the live collection on "26-11-12":

- COA's tree shifts while a year or region loads. Wait for loading to finish, read fresh state and confirm the intended category expanded before selecting its child. Scope selections to observed category IDs; a generic visible Cagayan link can select the city branch instead of municipalities.
- Bind every popup to the observed file ID. Its actual download URL must equal the inventoried official PDF URL before saving. A generic popup link returned an adjacent ZIP once; never count that ZIP as an Executive Summary.
- Rounded KB/MB labels are not exact advertised byte counts. Preserve the label, record a rounded-display check, and compute the saved file's exact byte count and SHA-256 separately.
- Preserve COA's original title and URL. A mislabeled entry becomes an Executive Summary only after inspecting the PDF, with its identifying page evidence recorded.
- Browser saves can replace ñ with n or change punctuation. Use only unique normalized filename matches; never guess among ambiguous candidates. Keep checkpoints per file and validation every 12, including a final partial flush.

Workbook collection, approved "26-11-13":

- Annual Audit Report ZIPs use a separate collector with batches of 15; PDFs remain at 12. Keep every XLSX, record provenance, then delete only the successfully checked ZIP after its success journal is flushed.
- ZIP URL basenames can have revision suffixes that are absent from browser save names. Record the actual saved basename; preserve the observed URL unchanged. Direct browser writes to `.zip` may briefly appear stable before completion; require the end-of-archive record and retry changed fingerprints rather than accepting a partial save.
- Publish complete staged workbooks atomically. On Windows use same-volume rename, which refuses existing targets; do not rely on hard-link creation being permitted in a restricted process. Capture retained fingerprints after staging cleanup.

# COA Parsing

Corrections from the 26-11-12 accuracy work ([record](../../docs/plan/archive/26-11-12-coa-accuracy-and-differences.md)):

- Every value change in the `composer data:finances:extract` diff is checked against its PDF page before importing. A passing suite is not evidence that amounts are right.
- A finding is attributed to the report (`report` family) only when the printed page proves it. Split rows, damaged text and reader disagreements are `reading`. Never say which side of a contradiction is wrong (for example a heading versus its amounts) without another report proving it.
- Never loosen the hardening tests in `tools/coa/tests/test_evidence.py` to pass a change; change the code. Repairs to text (joining split digits, restoring stale headings) need independent proof: the other reader plus the row's arithmetic, or the neighbouring report.
- Messages are formal and complete: line, years, report and amounts in pesos (`peso()`, `lineLabel()`). One-decimal amounts are not a finding.
- Edit Python with Unicode or regex escapes through the Edit tool or a script file, not shell heredocs.

Verification corrections from "26-11-13":

- Use `composer data:finances:test` with its automatic short-lived fixture directory. An explicit `--basetemp` is for retained debugging fixtures only; remove those synthetic files after recording the result. Keep baseline/final review evidence separate from disposable test collections.
- Check that the existing PostGIS container is healthy before full repository CI. A stopped Docker engine can leave Pest waiting without progress. A sandbox-only unreadable PHPStan configuration is an environment restriction; retry the authorized check outside the sandbox instead of repeatedly running the same failing command.


Owner corrections from "26-11-13" cleanup:

- All 29 governments and 2014–2025 are authorized, but new intake uses only the nine observed Tuguegarao fields in [the handoff](../../docs/engineering/coa-workbook-handoff.md). The broader catalog is not permission to expand fields. Other Shares from National Taxes was removed from this batch; no new M23/check-only figures are authorized.
- Distinguish a withdrawn measure (`displayed=0`, M23) from Pending, Superseded, missing coverage or a locked verified entry. Gray UI styling alone is not an exclusion rule. Tuguegarao's Revenue Sources and Business Taxes and Fees are enabled; Budget and Actual Amounts is disabled.
- Use municipality folders for registered source copies; keep PSGC strings and hashes in metadata. Preserve collection originals and update database/CSV paths with checksum verification when organizing copies.
- `financial-statements/index.csv` is the workbook register and the `intake` column of `finances/lines.csv` is the nine-field gate (26-11-13). Stage with `composer data:finances:workbooks`, check every changed value against its cell, then promote; never edit the gate to let a new field through without an owner decision.
- Never trust a file's label. COA's files can keep another government's sheet (Enrile 2019 Annexes held Solana's statement). Keep the sheet-government check, the cross-government duplicate check, reconciliation and two-report agreement in place; never loosen them to raise coverage (26-11-14).
- Remove disposable test fixtures after recording results. Archive closed baselines/final evidence with per-member checksums before deleting expanded copies. One-off inspection scripts belong in historical evidence, not active workflows; maintained commands remain in Composer/tools/coa.

Current owner-approved reduction, "26-11-14" (D48), supersedes the nine-field instructions above:

- Active workbook intake/display is Tax Revenue, Share from National Collections and Service and Business Income only. Grants, Other Income and printed Total Revenue remain internal reconciliation evidence.
- Delete Annex, SCBAA and workbook expense extraction rather than retaining a disabled or forensic parser. Source collection, registrations and all individual/ZIP downloads remain intact.
- Frozen history preserves retired observations including Pending records and their original diagnostics. Never promote into history or use historical replay as new intake.
- Executive Summary parser modules, PDF readers, shared monetary grammar and their version remain unchanged. Prove identical PDF amounts, years, citations and findings before completion.
- Missing national-share scope holds automatic sign-off for human correction. Do not infer it from Cash Flows or use identical ambiguous amounts as proof.

Workbook reading retired, "26-11-14" (D49), supersedes every workbook parsing and intake instruction above:

- The Executive Summary is the only source of figures. There is no workbook reader, no `intake` column and no Revenue Sources. Do not rebuild them or fix workbook parsing without an owner decision.
- Workbook collection, `financial-statements/index.csv` and the public downloads stay. Never delete or rewrite an original.
- `data/towns/finances/history/` is frozen evidence. The importer never reads it and nothing promotes into it.
- Parser tests run in parallel (`composer data:finances:test`); add `-- -n 0` to debug in one process. Never run it at the same time as another test command.
