---
title: "Live Finance Parsing"
created: "26-11-08"
updated: "26-11-08"
status: in-progress
branch: feat/finance-report-redesign
base: staging
predecessor: ../archive/26-10-29-coa-parser-hardening.md
---

# Live Finance Parsing

## Objective

Implement the owner's approved plan on "26-11-08": faster readable Python, real incremental progress and results in the existing finance upload workflow. The first screen answers: what is the parser doing and what has it found?

## Scope and Acceptance

- [x] Shared parser preserves financial output, decimal arithmetic, citations and dual-engine checks; workbook sheets are read once and PDF text reused within an invocation.
- [x] Optional flushed JSON-line events preserve default intake JSON compatibility; clear functions, types and beginner guide.
- [x] Full-page blur overlay with actual timestamped scan/cell events (last seven, no scrollbar), exact amounts, issues; close/reopen without cancelling. Supersedes the fixed four-stage strip and the centered table dialog, both rejected by the owner (26-11-08).
- [x] Durable bounded progress, authorized UUID status/retry endpoints, non-overlapping background polling, atomic attempt ownership and safe timeout recovery.
- [x] Readable sections remain pending for independent verification; structural failures do not save figures. Retry or reparse stored files without uploading again.
- [ ] Python and PostGIS regression tests, representative before/after benchmarks, real queued execution, frontend checks/build, CI, design/privacy and browser verification.

## Deferred or Excluded

No OCR, packages, cancellation, publishing, commit, push, deployment, Docker changes or unrelated screen redesign. Existing uncommitted edits remain intact. Loaded result interactions are local; only live status requires background requests.

## Decisions and Sources

"26-11-08": owner selected existing finance import, results/current scan by default, partial success, retry only, clear code with a short guide. Centered expandable dialog chosen using delegated design judgment.

Use Laravel process callbacks, Python unbuffered output, openpyxl read-only workbooks with explicit close, sequential PDFium. Sources: [Laravel](https://laravel.com/docs/13.x/processes), [Python](https://docs.python.org/3/using/cmdline.html), [openpyxl](https://openpyxl.readthedocs.io/en/stable/optimized.html), [PDFium](https://pypdfium2.readthedocs.io/en/stable/python_api.html).

## Change History

| Date | Change | Reason |
| :- | :- | :- |
| "26-11-08" | Approved plan recorded | Owner requested implementation |
| "26-11-09" | Info notes no longer shown as warnings, stored file names stripped from messages, bottom strip of Figures and Elapsed (no years), overlay moved onto the reusable `FocusDialog` with fade in and out | Owner liked the output; four red triangles were info notes drawn as warnings; asked for a status strip and a reusable blur |
| "26-11-08" | Replaced the centered table dialog with a full-page Gaussian blur overlay: bare centered Geist Mono content, one status line, a thin scan bar, and a fixed seven-line log (time, source location, line, exact amount, animated check) with no scrollbar; results table, badge, counts and Full Activity removed. Trimmed unneeded backend: deleted `coa/benchmark.py` and its Composer script, folded the pacing budget into `PACE_BUDGET_SECONDS`, dropped server-side "Found X" rewriting plus `activeLine` and `lastFigureKey` | Owner rejected the dialog as all caps, hint heavy, space hungry, literal, unscrolled-to-fit and static; asked for fewer lines of code |
| "26-11-08" | Replaced the rejected fixed four-step strip with actual numbered events, an active scanner, rolling activity, animated row insertion and exact amounts; added owner-requested half-second interactive pacing with a 20-second budget and Parse Again | Owner said the first result looked hardcoded and too fast to follow. Unpaced parsing and benchmarks remain fast. |

## Verification and Handoff

"26-11-09" (progress-test pipe repair): [release CI run 42535794025](https://github.com/m-njp/cagayan-central-businesses/actions/runs/42535794025) exposed intermittent JSON decoding failure in `testCommandFlushesEventsBeforeExitAndKeepsPlainJsonCompatible`. It mixed buffered text `stdout.readline()` with `communicate()`, whose POSIX reader can bypass prefetched bytes. Use an unbuffered binary pipe for both reads; keep strict JSON decoding, early progress, single result and plain-output equality assertions. This fixes the test reader without changing parser output or discarding blank lines. The targeted test passed 20 consecutive Windows runs; all 95 parser tests passed locally. Linux verification belongs to the fix PR.

"26-11-09" (GitHub CI repair): attempts 2 and 3 of run `42501106770` failed the real queued-workbook test. The workflow never created `tools/coa/.venv`, although the job defaults to its Python executable. Reproduced the same line 139 failure locally with a missing executable; restoring the environment passed all eight progress tests (42 assertions). CI now runs the existing `composer data:finances:test` before the PHP checks to initialize the pinned environment and test the parser. No assertion was weakened or skipped. GitHub verification is pending the updated workflow run.

Baseline: 83 tests passed; five intake tests initially hit pytest temporary-directory permissions and then all passed with a writable base directory. No parser behavior failures in the baseline.

"26-11-08": Before the pacing revision, all 94 Python tests and 21 targeted PostGIS tests passed, with PHPStan, frontend formatting/lint, TypeScript and build green. Full CI ran 373 PHP tests: 372 passed; the existing committed-data sign-off test found 12 pending figures rather than zero. Its test and finance CSVs are unchanged, and the predecessor Data Entry plan already records the outstanding human sign-off. No sign-offs were fabricated to clear this test.

Real execution: Tuguegarao City's 2025 PDF scanned nine pages through both engines, found 20 figures and no issues. The queued app run completed, discovered 20 and saved zero new figures because they already exist. An actual Windows pipe failure exposed non-UTF-8 output; ASCII JSON plus an explicit UTF-8 child environment fixed it, with a cp1252 regression test. Browser verified source citations, exact amounts, close/reopen, refresh, keyboard Escape, desktop light/dark and 375px width (375px document, 343px dialog, no page overflow). The paced revision was run on the real FS workbook: observed sheet scanning with zero figures, then nine growing figures at event 27, with a loading indicator, current source cell, rolling numbered activity and highlighted rows. A live screenshot was captured outside tracked source files.

Paced revision checks: 95 Python tests and 22 targeted PostGIS tests passed; PHPStan, TypeScript, frontend formatting/lint and build passed. The last layout refinement reduces the default feed to two recent events so new result rows remain visible, with the full history expandable.

Overlay redesign checks ("26-11-08"): 95 Python tests, 22 targeted PostGIS tests, PHPStan, TypeScript, frontend formatting/lint and build passed. No browser verification at the owner's request, so the new layout is unseen until an upload is run. The benchmark script below was deleted after these figures were recorded.

Benchmark, three timed runs per case plus separate Python allocation tracing; identical financial fingerprints: PDF 1.3051 to 1.4644 seconds, Python peak 47,332,164 to 6,844,379 bytes; FS 0.1637 to 0.0917 seconds; annex 0.3551 to 0.2703; ZIP 0.6468 to 0.4312. PDF elapsed time regressed 12.2% in this sample while memory fell substantially; no universal speed claim. The allocation peak excludes native library memory and app latency.

A final repeat during frontend building retained identical fingerprints and memory improvement but had noisier timings (PDF 2.2770 seconds, FS 0.1264, annex 0.3717, ZIP 0.5236). These concurrent-load samples are not evidence of a universal speedup. Default batch/benchmark execution never incurs interactive pacing.

Privacy review: P1 to P7, P9, P11 and P12 are not applicable to government-finance parsing; no business sales, individual business record or resident text is involved. P8 holds through town authorization and UUID/year scoping. P10 holds through explicit readonly Data objects. P13 holds through the existing workspace label. P14 holds: raw process diagnostics remain server-side and safe issue messages are returned. The full suite included the existing privacy tests. Design review found no raw colors, gradients, title separators or invalid punctuation in changed UI; exact money, token type, keyboard dismissal, reduced-motion classes, bounded tables and live status regions are present.

Environment: Docker Desktop started and the existing Compose PostGIS service created and became healthy. Existing volumes were preserved; no Docker files were changed. The additive progress migration was applied. Base seeding reached an existing simulated-permit duplicate, so the volume was not reset; finance import and metrics refreshed successfully. Port 8000 was freed at the owner's request and this app restarted. A stale Vite/React development graph produced a white page; the working preview uses the verified compiled assets, with the project's queue listener running.
