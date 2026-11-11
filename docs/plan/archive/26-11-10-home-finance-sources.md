---
title: "Home Finance Chart and Public Sources"
created: "26-11-10"
updated: "26-11-10"
status: completed
branch: chore/maintainability-refresh
predecessor: 26-11-09-home-focus-view.md
---

# Home Finance Chart and Public Sources

The Home map answers how the selected area's finances changed and where those figures came from. Show the history beneath the current figures and keep the fullscreen comparison bare, with the original blur and readable text.

- [x] Share the loaded area history between the Home hero and fullscreen. Restore the 55% light and 75% dark blur tints.
- [x] Add the public Sources page and direct report links in the map. Group the approved stored reports by municipality and report year, deduplicate citations, and credit the census and boundary sources.
- [x] Verify the public catalog and links, both themes and viewport sizes, the production build, and PostGIS CI.

Use the existing throttled report route. Private uploads, pending evidence, and missing files stay out of the catalog. Object storage is deferred. This follows the [Home Focus View](26-11-09-home-focus-view.md), [Maintainability Refresh](26-11-09-maintainability-refresh.md), and [Finance Filters and Business Activity](26-11-09-finance-filters-business-activity.md).

## Review Changes

"26-11-10": The owner moved the chart back to fullscreen only and requested a monochrome fullscreen table and higher contrast graph labels. About the Data replaces the Sources title and lists reports with file icons and a municipality search. Province totals link to the report catalog. The bulk download checks the exact archived bytes against the signed-off ledger checksums, shows the shared preparation feedback and includes a manifest for independent verification.

## Verification

"26-11-10": The initial fullscreen tint measured 55% in light mode and 75% in dark mode, both with a 16 px blur. The chart kept its history when the fiscal year changed. A Tuguegarao City report URL returned an inline PDF at its cited page and an annex link returned an XLSX attachment. The initial PostGIS CI run passed 379 PHP tests.

"26-11-10" review: At 1440 px and 375 px in both themes, the fullscreen axes and legend used the foreground color, the table rows were transparent, and the page did not overflow. The Home map link landed at the report catalog. Municipality search showed direct PDF and workbook links without a disclosure. The download reached its completion message. The Python checker verified all 162 reports in the live ZIP. The production build passed.

Final PostGIS CI passed 380 PHP tests and 2,779 assertions. The independent checker rejected an archive containing altered report bytes.
