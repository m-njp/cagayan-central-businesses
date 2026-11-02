# Plan History

Read the matching plan before changing a feature. This index separates unfinished work from completed or superseded decisions so improvements can refer to the original reasoning and verification.

Dates inside plans use the requested `yy-mm-dd` format in Asia/Manila. Filenames use `yy-mm-dd-short-name.md`, using the same date in metadata and filenames.

## Active and Deferred



| Plan | Status | Scope |
| :- | :- | :- |
| [Executive Summary View and Net Income](active/26-10-29-executive-summary-view.md) | In progress | Default Executive Summary table with charts, COA words Income, Expenses and Net Income, Financial Condition and Allotments for Tuguegarao City; CI and design checks pending |
| [Business Registry Records](active/26-10-28-business-registry-records.md) | In progress | Staff prepare and Admins review, record profile, form with map pin and documents, BIR and permit badges, public directory, presentation trend seeder |

## Archive

[Home Navigation and Data Labels](archive/26-10-30-home-navigation-data-labels.md): completed 26-10-30. Frontend checks and build passed; full CI stopped at an unreadable Python cache directory.

| Plan | Status | Reference |
| :- | :- | :- |
| [Finance Trends People Can Understand](archive/26-10-29-finance-trends.md) | Superseded 26-10-29 | Chart-led Trends default; Trends stays a view under the Executive Summary View plan |
| [COA Parser Hardening](archive/26-10-29-coa-parser-hardening.md) | Completed 26-10-29 | Two PDF engines with fallback and digit confirmation, crash isolation and run log, wrapped Appropriations label fix, all 201 Tuguegarao cells tested, camelCase Python; 77 parser tests passed |
| [Business Categories Revamp](archive/26-10-28-business-categories.md) | Completed 26-10-28, Focus Tag model superseded in part by D39 | Editable Business Types and Focus Tags, registered counts, local sort, search and paging, ten-color tag palette without a package (D38); full suite not run, light mode and edit forms not browser-checked |
| [Finance Ledger Table Redesign and No Simulated Data](archive/26-10-28-finance-ledger-redesign.md) | Completed 26-10-28 | Simulated data off by default, one evidence store, stored PDF links, ledger table, short Help, and the polish: About the Data fix, year order, shared surplus and deficit display, exact amounts on hover, M26 percent over the prior magnitude; Capitol Analytics checked; 306 PHP tests passed |
| [Make Analytics Understandable](archive/26-10-28-analytics-clarity.md) | Superseded 26-10-28 | Periods, local history and metrics stayed; layout, sources, Help and Compare replaced by the ledger redesign |
| [Category Hierarchy and Business Directory](archive/26-10-28-category-hierarchy.md) | Completed 26-10-28 | One Category per Business Type replacing Focus Tags (D39), starter taxonomy, categories page, read-only server-paged Businesses directory, registry groundwork; 304 PHP tests passed; directory rows and light mode not browser-checked |
| [Economic Map Cleanup](archive/26-10-28-economic-map-cleanup.md) | Completed 26-10-28 | Removed the map Measure selector, one page-anchored Source list, no repeated rows, Surplus or Deficit triangle; Capitol map, 375px and dark mode not browser-checked |
| [Dynamic Economic Map](archive/26-10-28-dynamic-economic-map.md) | Completed 26-10-28 | Public/Capitol annual finance maps, global Latest, coverage and shades, transactional refresh, dynamic review/Analytics year bounds; 270 PHP and nine frontend tests passed |
| [Finance Ledger and Analytics Program](archive/26-10-27-finance-ledger-analytics-program.md) | Completed 26-10-28 | COA parser, finance ledger with sign-off, Capitol review, workbook breakdowns, business records and privacy guard, analytics pages, reliability review, every figure signed off, single-folder consolidation; follow-ups listed |
| [Client Facing Polishing](archive/26-10-27-client-facing-polishing.md) | Completed 26-10-28 | Local-only quick sign in for the three demo roles and wish cloud sized by relative demand; shipped in the combined finance pull request |
| [Government Finances and Economic Context](archive/26-10-24-government-finances-economic-context.md) | Superseded 26-10-27 | Direction, Executive Summary collection and source rules; remainder continues in the Finance Ledger and Analytics Program |
| [Census Atlas Follow-Up](archive/26-10-27-census-atlas-follow-up.md) | Completed 26-10-27 | Barangay atlas cache revisit and missing-population tooltip fixes, plus corrections to the cleanup plan's reuse claims |
| [Single Source of Truth Cleanup](archive/26-10-27-single-source-of-truth-cleanup.md) | Completed 26-10-27 | Shared frontend/backend SSOT helpers, census loader/cache consolidation, three-copy line-count tightening, and obsolete branch cleanup |
| [Compact Home and Session Handoff](archive/26-10-25-compact-home-session-handoff.md) | Completed 26-10-25 | Compact cards, UTF-8 correction, session verification and PRs #16/#17 |
| [Home Economy and Wishes](archive/26-10-25-home-economy-wishes.md) | Completed 26-10-25 | Home actions, combined wishes, scalable ranked bars, town tooltips and copy rules |
| [Local Economy Selections](archive/26-10-25-local-economy-selections.md) | Completed 26-10-25 | Zero-request local filters/maps, bounded public catalog and durable interaction rules |
| [Cagayan Economy Polish](archive/26-10-25-cagayan-economy-polish.md) | Completed 26-10-25 | Compact stacked sections, icon views, stronger grid, town map and obsolete-code cleanup |
| [Cagayan Economic Performance](archive/26-10-25-cagayan-economic-performance.md) | Completed 26-10-25 | Tuguegarao finance and curated-wishes demo; final-only tests, CI, builds and browser verification |
| [Super Admin Dashboard Foundation](archive/26-10-24-super-admin-dashboard-foundation.md) | Completed 26-10-25 | Foundation, later corrections, source verification and final checks |
| [Public Landing Page](archive/26-10-23-public-landing-page.md) | Completed before this register was created | Merged PR #15 |

## Lifecycle

- Create a record for an agreed implementation plan using [the plan-history skill](../../.claude/skills/plan-history/SKILL.md). Resume an existing matching plan when possible.
- Keep accepted scope, dated changes, primary references, and actual verification evidence together. Keep deferred work visibly deferred.
- Archive the same file when completed, cancelled, or superseded; preserve the reason and link its successor. Future improvements get a new plan referring back to it.
- Plan updates do not authorize new scope or external actions. Credentials and protected records never belong in these documents.
