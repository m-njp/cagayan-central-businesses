# Executive Summary Verification

Recorded "26-11-14" on `chore/maintainability-refresh`, following [D49](decisions.md#d49-executive-summary-only-and-parallel-tests) and the [handoff](executive-summary-handoff.md). The final integration checkpoint passed the production build and full `composer ci:check` through the normal pre-push hook for `0d573dd`. Fresh standalone runs passed 497 PHP tests / 3,678 assertions (458.336 seconds reported by Pest) and 694 parser tests (29.21 seconds). All 520 workbook originals and six frozen-history files match checksums. No fresh browser verification is claimed; screenshots and a final light/dark comparison remain PR-review limitations. Earlier failed runs and timings below are retained as historical evidence, not current gate status.

## Measured Pest Bottlenecks

The initial full profiling run used `php artisan test --parallel --log-junit storage/app/executive-summary-verification-junit.xml`. It ran 490 tests with 12 workers. Pest reported 579.454 seconds; command wall time was **581.845 seconds (9m42s)**. Result: 484 passed, two failed assertions and four errors. These are profiling measurements from that run, not a successful final-suite benchmark.

| Test | Seconds | What It Exercises |
| :- | -: | :- |
| FinanceImportTest: imports the committed COA data with every reviewed figure signed off and no workbook records | 396.652 | Full real-data import, review restoration and published figures |
| DatabaseSeederTest: preserves an existing demo account when the full database is reseeded | 354.299 | Full seeder, including the real finance import |
| DatabaseSeederTest: seeds demo accounts with UUIDs while model events are muted | 165.266 | Full seeder, including the real finance import |
| LoginPathOutOfBundleTest: built assets do not contain the login path | 20.007 | Production asset inspection, including test setup |
| SimulatedBusinessSeederTest: every year stays consistent with the previous year | 19.126 | Simulated renewal and status rules |
| FinanceParsingProgressTest: retry fences out the old job and failure callback | 17.432 | Upload retry and stale-worker protection |
| FinanceParsingProgressTest: real Executive Summary through the queue | 10.809 | Actual Python parser subprocess and pending figures |

The three full-import tests consume **916.216 seconds, 51.5% of the 1,779.215 summed test-case seconds**. Worker times overlap and include setup and contention. They must not be added to obtain elapsed wall time or treated as a measured serial baseline. The two full-seeder cases run in the same worker, leaving a long tail after most workers finish. Database samples showed active figure reads/updates and no lock wait in the observed samples; this does not establish the cost of every individual query.

The full committed-data and full-seeder coverage is retained. Reducing these tests or optimizing the importer needs a separate measured change. No PHP, PostgreSQL or Docker machine settings were changed.

## Failures Corrected

- The full-seeder database assertion rejected Laravel's parallel worker suffix. It now accepts only the intended test database or its numbered worker databases.
- An Economy request used `performance.expenses` but expected the retired `revenue.tax` field. The assertion now checks the requested Executive Summary field.
- Four import tests failed writing `lines.csv` after deleting and recreating a shared fixture path. Each generated fixture now uses a fresh UUID directory and cleans it up after the test. The subsequent targeted run passed all 76 tests in **20.397 seconds wall time** (18.819 seconds reported by Pest).
- The next full run exposed another Windows fixture error in `FinanceReviewEvidenceTest`: replacing a just-deleted CSV with a directory failed with `mkdir(): Permission denied`. Moving the CSV aside preserves the same directory-at-diagnostics-path case. All **29 evidence-review tests passed in 22.818 seconds wall time** (21.185 seconds reported by Pest). The pattern is documented in the testing guide and `.claude/rules/testing.md`.
- The two source-only workbook kinds needed the existing source formatter to recognize them as downloads. Their frontend regression test initially needed the same Node module-alias setup as its siblings; after that fixture correction, all **13 analytics frontend tests passed in 2.464 seconds wall time** (1.051 seconds reported by Node).

## Sources and Preservation

- All 516 previously registered workbook copies matched their SHA-256 checksums before changes. Both frozen retirement manifests matched every listed file.
- The collection holds 521 original copies, representing 520 distinct file hashes. Four distinct originals were absent from downloads: the 2023 Ballesteros, Lasam and Santa Teresita AAPSI workbooks, and Peñablanca's 2019 Appendices.
- The owner approved adding those four to downloads only. The register now contains 520 identities. New `aapsi_workbook` and `appendices_workbook` labels belong only to the source library, not the ledger enum, parser or intake configuration. The original collection files were copied without alteration; existing registered workbooks were retained.
- An actual source-library check validated all **520 individual workbook download lookups** and built and verified the complete ZIP: **868 reports, 184,193,021 bytes, no failures, 56.249 seconds**. The archive contains 348 PDFs and 520 workbooks. This was one corpus-level check for the owner's all-originals requirement, not a recurring check after documentation edits.
- All 25 output files in the saved 348-PDF staged extraction match the canonical output and the recorded run checksums. Its PDF source-register checksum also matches. Parser version remains 1.7.1. Existing extraction issues remain available for human review; identical output is not a claim that every unresolved figure is correct.
- Final preservation compared 569 starting files: the only changes were the approved source-register append and its README. The previous 516 register rows are byte-preserved. No parser-module, canonical output, existing workbook or history-file checksum changed.
- The approved retirement migration ran successfully. Before/after checks matched all 5,174 Executive Summary figure identities, values, statuses, citations, review keys, review dates and notes. The ledger now has 348 Executive Summary documents and no workbook documents.
- `composer data:finances` completed in **85.602 seconds**: 348 documents, 5,174 parsed figures, 0 new, 0 changed back to Pending, 0 removed, 0 manual figures, 2,379 verified figures.

## Parallel Savings

The handoff records parser tests at 115 seconds serial versus 28 seconds parallel: **87 seconds saved, 75.7% less time, about 4.1 times faster** for that historical comparison. It also records frontend command consolidation from 5.6 to 1.5 seconds: 4.1 seconds saved. Those are handoff measurements, not fresh matched benchmarks from this continuation.

There is no matching successful serial Pest baseline or agent-work timing log. A credible Pest or multi-agent time saving cannot be calculated from the failed historical runs or the sum of parallel worker durations. This continuation did not produce a wholly green aggregate run, so it does not claim a successful full-suite benchmark.

## Final Checks

| Check | Result | Measured Time |
| :- | :- | -: |
| Initial full Pest profile | 484/490 passed, two assertions failed, four fixture errors; subsequently corrected | 581.845s wall, 579.454s Pest |
| First CI attempt | Frontend checks and 33 Node tests passed; stopped at import-test line endings, fixed with Pint | 61.564s wall |
| Historical aggregate `composer ci:check` before final integration | Formatting, TypeScript, 33 Node tests, Pint and PHPStan passed; Pest 491/492 passed, one fixture error subsequently corrected | 486.608s wall (8m7s), 417.797s Pest (6m58s) |
| Targeted import, Economy and source-download coverage | 76 passed, including the two added source kinds | 20.397s wall |
| Targeted finance evidence-review coverage after final fixture fix | 29 passed | 22.818s wall |
| Targeted analytics frontend coverage after final formatter/test setup fix | 13 passed | 2.464s wall |
| Final `npm run check` | Passed | 5.850s wall |
| Final `npm run types:check` | Passed | 8.397s wall |
| Final production build | Passed | 20.418s wall |
| Pint on seven PHP files edited in this continuation | Passed | Not separately timed |
| Private-login-path check against the new production build | 1 passed, 130 assertions | 8.305s wall (7.074s Pest) |
| Python parser tests | 694 passed in the handoff; carried forward because parser modules remain unchanged | Historical 28s parallel, 115s serial |

The initial and latest full attempts have different fixtures, cache state and failure counts. Their runtimes describe those runs; the difference is not a controlled optimization benchmark. CI does not include a fresh Python parser suite or production build; the table distinguishes those checks explicitly. Static-analysis and unchanged-test results are carried forward only where their inputs remain valid. No full test suite was repeated solely for the later policy documentation edits.

The owner requested a durable targeted workflow during verification. The canonical policy is [Choose Checks From the Change](testing.md#choose-checks-from-the-change), linked by `AGENTS.md`, `CLAUDE.md`, the testing rules and team workflow. It distinguishes targeted working checks from integration/pre-push, keeps required frontend static/build checks, and requires honest reporting of full-run failures followed by targeted passes.

## Scoped Reviews

Static design review found and corrected the new source-kind classification issue. The existing source library keeps its layout, token styles, keyboard-accessible links, XLSX download indicator and local-history municipality/search behavior. The upload remains PDF-only and Revenue Sources remains removed. Desktop, phone and theme browser checks were excluded by the owner's instruction; no visual verification is claimed.

Privacy review traced the D49 finance changes and the four added source downloads, not unrelated working-tree changes from other sessions:

| Rule | Verdict and Evidence |
| :- | :- |
| P1 | Holds: public citations and archive members contain government reports, no individual business sales |
| P2 | Holds: sales guard unchanged; covered by the prior full run |
| P3 | Holds: dominance guard unchanged; covered by the prior full run |
| P4 | Holds: complementary suppression unchanged; no new sales breakdown |
| P5 | Holds: no sales filter or grouping changed |
| P6 | Holds: no derived private-business figure changed |
| P7 | Holds: public sales bands unchanged; no numeric sales added |
| P8 | Holds: original COA files are public government sources; upload/review requests retain role and municipality authorization |
| P9 | Does not apply to the added government-source downloads; business record views are unchanged |
| P10 | Holds: source citations remain explicit readonly Data objects, never models in page props |
| P11 | Holds: this archive contains original government sources; business sales exports and guard paths are unchanged |
| P12 | Does not apply: resident wishes and browser identity handling are unchanged |
| P13 | Holds: simulated-business labels and guard behavior are unchanged; privacy feature coverage passed in the prior full run |
| P14 | Holds: public source responses contain registered report metadata and bytes; no protected values or server paths were added |
| P15 | Holds: figure publication remains Verified-only; listing an original source publishes no Pending figure |

No blocking finding remained in that earlier continuation. The later integration/pre-push gate passed for `0d573dd`, as recorded at the top of this report.

## Import Lookup Optimization

Implemented "26-11-14" after the owner approved optimizing the real CSV importer while retaining the committed-data import and both complete database-seeder tests. No seeder, sign-off service, metric SQL, schema, CLI signature, dependency or machine setting changed.

### Profiling Method

The matched runs selected exactly three tests with `php artisan test --parallel --processes=2 --filter='imports.the.committed.COA.data|seeds.demo.accounts.with.UUIDs|preserves.an.existing.demo.account'`, with separate before/after JUnit files. Both passed **3 tests and the same 28 assertions**. Temporary hooks recorded import phases, Laravel query-event counts and SQL durations, per-test peak PHP memory, and normalized ledger/statistics hashes. They stored no bindings or row values and were removed afterward; `tests/Pest.php` was restored byte for byte.

The initial profiling registration was file-scoped and did not capture phase data. That uninstrumented run passed in 438.861 seconds and is excluded from the matched comparison. A small smoke test verified the corrected hooks before the instrumented baseline. No further full-data run was needed after the matched comparison. Artifacts remain locally under `storage/app/coa-import-optimization/`, including `comparison.json`, per-test JSON and the JUnit files.

### Matched Results

Combined command wall time: **456.851s to 379.458s**, saving **77.393s (16.9%)**, from about **7m37s to 6m19s**. These are matched targeted-run measurements, not a new whole-application CI benchmark or a statistical performance guarantee.

| Case | JUnit Seconds Before | JUnit Seconds After | Profiled Body Seconds Before | Profiled Body Seconds After |
| :- | -: | -: | -: | -: |
| Committed COA import | 125.269 | 96.264 | 121.902 | 92.795 |
| Full seeder creating demo accounts | 126.388 | 97.348 | 122.849 | 94.027 |
| Full reseed preserving an existing account | 325.656 | 277.475 | 324.545 | 276.341 |

JUnit includes setup and hooks outside the profiled body interval. Worker times overlap; their sum is not combined wall time.

| Case | Query Events Before / After | SQL Event Seconds Before / After | Peak PHP MiB Before / After |
| :- | -: | -: | -: |
| Committed COA import | 20,013 / 15,504 | 92.950 / 68.231 | 72 / 88 |
| Full seeder creating demo accounts | 20,431 / 15,922 | 94.050 / 69.276 | 74 / 90 |
| Full reseed preserving an existing account | 20,424 / 15,915 | 108.395 / 69.452 | 86 / 100 |

Each case eliminates **4,509 query events**, replacing 4,510 individual review-candidate lookups with one preload. Query counts and aggregated SQL time cover Laravel `QueryExecuted` events. Raw PDO metric execution and transaction-control commands are outside that event timing; the metric phase wall time below includes the full rebuild. Peak PHP memory is measured after resetting the peak counter at the start of each profiled case. The retained tradeoff is **14 to 16 MiB more peak memory**, with a maximum measured peak of 100 MiB.

### Phase Findings

| Import Phase | Committed Import Before / After Seconds |
| :- | -: |
| Read CSV inputs | 0.183 / 0.164 |
| Validate inputs | 0.160 / 0.161 |
| Guard reviewed evidence | 0.021 / 0.021 |
| Write lines/documents | 0.077 / 0.076 |
| Load/write parsed figures | 0.778 / 0.748 |
| Preload/replay reviews | 112.373 / 84.506 |
| Manual review phase | less than 0.001 / 0.006 |
| Rebuild finance metrics | 6.921 / 5.797 |

The complete committed dataset contains no manual figures, so manual write/review costs are negligible; separate small fixtures cover competing corrections. Review replay fell from 112.538 to 84.633 seconds in the first seeder and from 128.474 to 84.124 seconds in the reseed. The reseed's metric refresh remains the main long tail: **191.477 to 188.618 seconds**. Its SQL and locking were left unchanged. Other phase variation is ordinary run variation and is not attributed entirely to lookup removal.

### Retained Behavior and Coverage

The importer loads parsed review candidates once inside its existing transaction, using document, line, integer fiscal year and basis as the key enforced by `finance_figures_parsed_unique`. It selects only fields needed by replay/sign-off. Manual figures are loaded by the supplied public identifiers after parsed reviews; UUID lookup keys are normalized to PostgreSQL's lowercase representation. A later correction in the same group refreshes its model because an earlier sign-off can have superseded it. Candidate maps are local to one import invocation. Sign-off decisions, winner queries, locks, transactions, reviewer guards, report ordering, manual precedence, missing/changed-value warnings and batched inserts remain intact.

The new query-growth regression failed before optimization: increasing reviews from 2 to 8 increased unlocked figure reads from 4 to 10. It now passes with bounded reads. Added cases cover competing manual corrections, repeated verify/reject/verify decisions, normalized fiscal-year keys and missing/changed-value warnings. One proposed duplicate-observation fixture proved invalid against the existing database uniqueness constraint; both that new fixture and the unnecessary defensive branch were removed. No pre-existing test or assertion was removed.

After removing probes and formatting, the final targeted run passed **104 tests, 717 assertions, in 25.838 seconds wall time** (24.538s Pest). It covered small import regressions, sign-off, publication/rollback, review/evidence and one real Executive Summary PDF through the queue. Scoped Pint and PHPStan passed. No entire-application or entire-parser suite was rerun for this backend-only change.

Final review then caught an accepted-input edge case: uppercase CSV UUIDs did not match the preloaded lowercase UUID keys. A two-case manual-correction test reproduced it; normalizing the lookup fixed it. Both lowercase and uppercase cases now pass (**2 tests, 8 assertions, 2.588s Pest**), and scoped Pint/PHPStan pass again. Those focused results follow the 104-test run; they are not presented as a fresh combined 105-test run. The three benchmark cases contain zero manual figures, so this isolated manual-key correction does not invalidate their measured paths or require another full-data run.

All three before/after pairs have identical normalized hashes for **5,174 finance figures and 2,828 statistics rows**, excluding generated row/public identifiers and timestamps from cross-run comparison. Existing reimport identity assertions remain. Final checks matched **all 1,482 preserved files**, including original reports, registers, canonical output, frozen history, parser modules, download implementation, the unchanged sign-off service, both seeder test bodies, upload job, progress services, blur-view hook and pacing configuration.

### Development and Live Parsing

This optimization affects bulk CSV ledger loading. The single-report PDF upload uses the existing Python intake, streamed phase events and upload-recording path. Its blur overlay, scanning/checking/saving stages, completion behavior and **0.5-second configured pacing** are unchanged. Development iterations use small fixtures or the selected municipality/year PDF upload. Full-data loading is reserved for necessary seeding/integration or a justified benchmark; no new municipality/year bulk-import flags were added.

The optimization was complete at this point; aggregate CI was still pending then. The later integration/pre-push gate passed for `0d573dd`; its result does not change the matched benchmark timings above.
