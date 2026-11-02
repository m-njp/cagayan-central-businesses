# Metric Definitions

Every number on screen has one definition here, one SQL implementation, and one test. If a judge asks how a number is computed, the answer is in this file.

The plain-language definitions come from the product document. This file makes them exact enough to code and to test. Where the product document left a choice open, the choice made here is marked **Choice** and listed in `decisions.md` for the adviser to confirm.

## Building blocks

For an area `a` (a barangay, a town, or the province), a business type `t`, and a year `y`:

- `Active(a, y)`: businesses whose status in year `y` is `new` or `renewed`.
- `New(a, y)`, `Renewed(a, y)`, `NotRenewed(a, y)`, `Closed(a, y)`: businesses with that status in year `y`.
- `Sales(a, y)`: the sum of declared gross sales of businesses active in `a` in year `y`.
- `Pop(a)`: census population.
- `Y`: the latest data year. `T = Y + 1`: the outlook year.

All of these read from `stats_area_year`. A type filter, a size filter, or both narrow the same sums.

## Three states every value can be in

Code and screens must keep these apart. They are never shown as zero.

1. **A value.** Shown.
2. **Not available.** The inputs do not exist, for example a rate whose denominator is zero, or a change from a year with no data. Shown as "Not available".
3. **Hidden.** A privacy rule suppressed it. Shown as "Hidden to protect individual businesses". See `privacy-rules.md`.

## Display rounding

- Counts: whole numbers.
- Rates and shares: percent with one decimal.
- Density: one decimal.
- Specialization: two decimals.
- Money for administrators: whole pesos with separators, or millions with one decimal on charts.
- Money for the public: a band only.

## M1. Net growth

- Number: `New(a, Y) - NotRenewed(a, Y) - Closed(a, Y)`
- Percent: that number divided by `Active(a, Y - 1)`
- Not available when `Active(a, Y - 1)` is zero.

## M2. Business density

- `Active(a, Y) / Pop(a) * 1000`
- Read as registered active businesses per 1,000 residents.

## M3. Total declared sales

- `Sales(a, Y)`
- On-screen note: it measures declared business activity, not the income of residents or of the barangay government.
- Always passes through the privacy guard.

## M4. Sales per business and share of total

- Sales per business: `Sales(a, Y) / Active(a, Y)`
- Share of total: `Sales(a, Y) / Sales(parent of a, Y)`
- Both are hidden whenever `Sales(a, Y)` is hidden.

## M5. Rank and rank change

- Rank areas of the same level by the chosen measure, highest first. Ties share a rank and the next rank is skipped (1, 2, 2, 4).
- Rank change: `rank in Y - 1` minus `rank in Y`. Positive means the area moved up.
- When ranking by sales, areas with a hidden figure are listed unranked at the end.

## M6. Dominant type

- By count: the type with the largest `Active(a, t, Y)`.
- By sales: the type with the largest `Sales(a, t, Y)`, among types whose figure is not hidden.
- Ties: show all tied types.

## M7. Concentration

- By count: the dominant type's `Active` divided by the area's `Active`.
- By sales: the dominant type's `Sales` divided by the area's `Sales`. Hidden when either figure is hidden.
- On-screen note: a high value means the area depends on one kind of business.

## M8. Specialization

- `(Active(b, t, Y) / Active(b, Y)) / (Active(city, t, Y) / Active(city, Y))`
- Above 1 means the barangay has more of that type than is typical for the city.
- Shown only for types with at least 3 active businesses in the barangay, because smaller counts swing wildly. The minimum is in config.

## M9. Renewal rate

- `Renewed(a, y) / Active(a, y - 1)`
- Not available when `Active(a, y - 1)` is zero.

## M10. Survival rate

By business type, city wide. For the cohort of businesses with status `new` in year `c`:

- One-year survival: those with status `renewed` in `c + 1`, divided by the cohort size.
- Two-year survival: those with status `renewed` in both `c + 1` and `c + 2`, divided by the cohort size.
- The most recent one-year figure uses cohort `Y - 1`. The most recent two-year figure uses cohort `Y - 2`.
- Show the cohort size beside every rate. Not available when the cohort is empty.

## M11. Outlook, businesses

For barangay `b`, projecting year `T` from data up to year `Y`.

Step 1, renewal rates by type, city wide:

- `r(t, y) = Renewed(city, t, y) / Active(city, t, y - 1)`
- `r_mid(t)`: the average of `r(t, y)` over the three most recent years. **Choice.** Use two years if only two exist.
- `r_low(t)` and `r_high(t)`: the lowest and highest `r(t, y)` over all past years.
- If a type has no usable rate, use the all-types city rate.

Step 2, businesses kept:

- `Kept_mid(b) = sum over t of Active(b, t, Y) * r_mid(t)`, and the same with `r_low` and `r_high`.

Step 3, expected new registrations:

- `n_mid(b)`: the average of `New(b, y)` over the three most recent years.
- `n_low(b)` and `n_high(b)`: the lowest and highest of those three years.

Step 4, the projection:

- `businesses_mid = round(Kept_mid + n_mid)`, and the same for low and high.

One sentence for the pitch: "We take this year's businesses, apply how often each type renewed in past years, and add the usual number of new registrations."

## M12. Outlook, sales

- `s(t, y) = Sales(city, t, y) / Active(city, t, y)`: average declared sales per business of type `t`.
- `g(t)`: the average yearly growth of `s(t, y)` over the three most recent year pairs.
- `P_mid(b, t)`: projected businesses of type `t`, which is `Active(b, t, Y) * r_mid(t)` plus the average of `New(b, t, y)` over the three most recent years.
- `sales_mid(b) = sum over t of P_mid(b, t) * s(t, Y) * (1 + g(t))`
- `sales_low = sales_mid * businesses_low / businesses_mid`, and the same for high. **Choice:** the sales range follows the business range.
- Hidden whenever the barangay's current sales figure is hidden.

## M13. Backtest error

The same method, run as if standing in an earlier year, compared with what actually happened.

- Run M11 and M12 for every target year that already has real figures and at least two earlier years of rates. With data from 2020 through 2026, that is target years 2023, 2024, 2025, and 2026.
- For one barangay: the average over those target years of `abs(projected - actual) / actual`, skipping years where the actual is zero.
- For the city headline: `sum of abs(projected - actual)` divided by `sum of actual`, across all barangays and target years. This keeps tiny barangays from dominating the figure.
- Also report how often the actual value fell inside the low to high range.
- Shown beside every projection, in words: "Tested on 4 past years: off by X percent on average."
- On simulated data this shows that the method runs, not that it is accurate. Say so on screen.

## M14. Early warning

Raised for a barangay, or for a business type city wide, when either holds:

- Non-renewals rose two years in a row: `NotRenewed(Y - 2) < NotRenewed(Y - 1) < NotRenewed(Y)`, and `NotRenewed(Y)` is at least 3. The minimum is in config and stops flags on tiny counts.
- The outlook points down: `businesses_high(T) < Active(Y)`.

Each flag states which condition raised it.

## M15. Public sales band

- Take the area's total declared sales after the privacy guard.
- If hidden, the public sees the hidden message, not a band.
- Otherwise show the band that contains the total. Band edges are in config and are an open decision (O2).

## M16. Peer group and below peer average

- Peer group: barangays in the same town with the same urban or rural class.
- Peer median: the median density (M2) of the group.
- A barangay is "below peer average" when its density is lower than the peer median by more than the margin: `density < median * (1 - margin)`. The margin is in config and is an open decision (O4).
- Wording on screen is always "below peer average". Never "underperforming".

## M17. Wish count

- The number of wishes for a business type in a town.
- Shown beside `Active(town, t, Y)`, the number of such businesses already registered there.
- Meaning: expressed interest by voluntary respondents, not measured demand. Per the user correction, do not repeat this as page-description copy.

## Town figures (home page map)

Real published figures, separate from business records. Public Home and Capitol economic maps use `stats_finance_year` for Income, Expenses and Net Income (M19). The finance ledger is authoritative; there is no separate map finance store.

- **Latest:** one absolute newest fiscal year with a verified revenue/expense pair for at least one town. Never mix fiscal years or substitute an older town observation. Calendar changes alone do not advance Latest.
- **Year:** available verified fiscal years, descending. Explicit years with missing records read Not available, never zero.
- **Population:** latest verified `town_facts` census context is visible only in Latest. Explicit fiscal-year views omit population values, source links and shading. The independent public barangay census explorer remains census-based.
- **All towns:** financial totals are Sum of Reporting Towns (M27), with metric-specific coverage. They do not represent the provincial government's accounts. Latest census context is separately labeled Cagayan Province.
- **Publication:** import and review batches rebuild finance statistics in their transaction; a refresh failure rolls back publication. Pending or rejected figures remain withheld; latest-report restatements retain their lineage.

Legacy `TownProfile.revenue`/`expenditure` fields remain available for compatibility; the economic maps render only the shared annual finance dataset.

### M18. Map shade

- On the province map, towns. On the About the Data map, the selected Cagayan town's barangays. Each set is shaded on its own.
- On the independent census explorer, places with a population are split into `towns.shades` equal-count groups by population, lowest first (`ntile`). Shade 1 is the fewest people.
- On the map every dot is the same size. The shade sets the dot color, from `--map-1` to `--map-5`.
- A place without a population has no shade.

Province population/count totals require complete town coverage in the same reference year. Missing source links are not attributed to another town’s source. City/municipal accounts are never summed and shown as the provincial government’s revenue or expenses.

Economic maps shade towns by the selected M19 finance measure and fiscal year, with `ntile(towns.shades)` computed in SQL over available town values only. Null aggregate rows are excluded from town groups. Zero and negative Net Income values are valid; missing observations receive no shade. Shade order breaks equal-value ties by PSGC code for stable results. Finance shades are a numeric scale, not a prosperity or performance rating. Barangay boundaries carry no financial shade.

## Government Finances (Approved 26-10-28)

**Status: approved by the user on 26-10-28.** A definition changes only with a human-approved doc change. See the [Finance Ledger and Analytics Program](../plan/archive/26-10-27-finance-ledger-analytics-program.md) and D35.

Revenue + Economic Status is the approved information direction. Government finances sit alongside economic output, household conditions, business activity, and community interests. It is not a composite rating.

Government revenue includes local and external sources. It is not private-business sales and not GDP. Preserve source-specific labels and definitions; real GDP growth uses constant-price output, not government revenue growth. Publish a measure only at the source's supported geographic level: Cagayan Province GDP cannot stand in for Tuguegarao City or barangay GDP. See the October 24 clarification in the product scope.

COA measures describe the reporting local government, not the output or sales of private businesses. Income/revenue, expenses/expenditure, cash outflows and obligations are not interchangeable. Each line below names its source and basis. Audit opinions, audit findings and infrastructure project lists are excluded (D35).

### Sources and Years

| Source | What it contains |
| :- | :- |
| Executive Summary | COA Executive Summary PDF: financial highlights for all 29 towns |
| FS | Part 1 financial statements workbook: Statement of Financial Performance, and Statement of Comparison of Budget and Actual Amounts (SCBAA) |
| Annex | Part 4 Annexes workbook: detailed General Fund performance (Annex C-1) |

- **Fiscal years:** 2019 to 2025. Fiscal 2019 comes only from the prior-year column of the 2020 reports.
- **2026:** waits for its report. No budget or partial-year figure stands in for it.
- **Basis:** accrual actual, unless the value is marked as a final budget.
- **Precision:** exact cents in storage.

### Observation States

Every finance and business observation has one state. When it is not shown, it also has a reason. "Not available" and "Hidden" are never shown as `0`.

| State | Meaning | Shown as |
| :- | :- | :- |
| `shown` | Signed off (D35) | The value |
| `not_published` | COA published no report or workbook for that town and year | Not available; the reason is in the tooltip |
| `not_reported` | The document exists but does not contain the line | Not available; the reason is in the tooltip |
| `not_collected` | No document for that town and year is loaded yet, such as a COA workbook not yet added (user approval, 26-10-28) | Not available; "The COA workbook for this year has not been added yet" in the tooltip |
| `pending_review` | Extracted or entered, not yet signed off | The value is withheld everywhere except the Capitol review screen; elsewhere Not available with "Awaiting sign-off" in the tooltip |
| `hidden` | Business privacy guard, P2 to P6 | Hidden, with the reason |

### M19. Income, Expenses, Net Income

Renamed 26-10-29 by user approval to the words COA prints (formerly Government Revenue, Government Expenses, Surplus or Deficit). The codes and formulas are unchanged. They always appear under the Government Finances heading, or as "Government Income" where no such heading surrounds them (the economic map legends), so they are never read as business income.

| Measure | Code | Definition and source labels |
| :- | :- | :- |
| Income | `performance.revenue` | Total government income for the year, including the share of national taxes. Executive Summary: "Income", "Total Income", "Total Revenues". FS: "Total Revenue" |
| Expenses | `performance.expenses` | Total expenses, including non-cash expenses and transfers to other entities. It is not cash disbursements or obligations |
| Net Income | `performance.surplus` (derived) | Income minus Expenses, computed in SQL. The summaries' own "Net Income" is read only to check their arithmetic, not stored: only 61 of 158 reports print it, and two (Amulung and Rizal 2025) print one that is not Income minus Expenses |

- **Display:** a negative amount reads in accounting parentheses, `(₱6,542,951.65)`. Net Income carries no triangle and no green or red; triangles mark changes only (M26).
- **Variants:** when a source label states a different definition, the figure carries a `definition_variant`. Example: "Includes Subsidy from Other Funds".

### M35. Financial Condition and Budget Execution Lines

Approved 26-10-29 by the user for the Executive Summary view. Each is the amount the Executive Summary's highlights table prints, as reported; nothing is computed.

| Line | Code | Group | Source labels |
| :- | :- | :- | :- |
| Assets | `position.assets` | Financial Condition | "Assets", "Total Assets" |
| Liabilities | `position.liabilities` | Financial Condition | "Liabilities", "Total Liabilities" |
| Government Equity | `position.equity` | Financial Condition | "Government Equity", "Net Assets/ Equity" |
| Current Allotments | `budget.allotments_current` | Budget Execution | "Allotments – Current" |
| Continuing Allotments | `budget.allotments_continuing` | Budget Execution | "Allotments – Continuing" |
| Continuing Obligations | `budget.obligations_continuing` | Budget Execution | "Obligations – Continuing" |

- **Coverage:** loaded for the towns in `cagayan.finances.summary_detail_towns`, Tuguegarao City since 26-10-29. Other towns read Not available, "Loaded for Tuguegarao City only" (reason `not_loaded`); their parsed rows stay in `data/towns/finances/extracted/`.
- **Checks:** Assets equals Liabilities plus Government Equity within ₱1.00, checked by the parser for every report.
- **Not stored:** Continuing Appropriations. Tuguegarao prints only dashes (2020) or omits the line.
- **Meaning:** Appropriations are the budget passed by ordinance; Allotments release it to offices; Obligations are commitments charged against allotments, not cash paid. Current lines belong to the year's budget; Continuing lines to unused earlier budget that remains available.

### M20. Budget Utilization (Retired)

Retired 26-11-03 by the owner. It divided Current Obligations by Current Appropriations, while the obligation rate in the [DBM guide](https://www.dbm.gov.ph/images/pdffiles/2016-BUDGET-NG-BAYANTagalog.pdf) divides obligations by allotments. The figures themselves stay in Budget Execution; no rate is computed or shown.

### M21. Revenue Sources

Revised 26-11-03 by the owner. Every line is a reported amount from the FS Statement of Financial Performance, labeled with the statement's own words:

| Revenue source | Code |
| :- | :- |
| Tax Revenue | `revenue.tax` |
| Share from National Collections | `revenue.national_share` |
| Other Shares from National Taxes | `revenue.other_national_share` |
| Service and Business Income | `revenue.service_business` |
| Shares, Grants and Donations | `revenue.grants` |
| Other Income | `revenue.other` |

- The statement's Total Revenue is stored as `fs.total_revenue` (a check line, not displayed). The parser checks that the lines add up to it within ₱1.00. For Tuguegarao City 2025 they sum to ₱1,679,019,473.60, equal to Income.
- Each figure cites the worksheet cell for its year (prior-year values sit in a different column from current-year values).
- **Retired 26-11-03:** the calculated Other Revenue remainder (Income minus three sources) is replaced by the reported lines. Locally Generated Share was retired because consolidated income does not establish BLGF's Total Regular Income denominator ([BLGF definitions](https://blgf.gov.ph/wp-content/uploads/2020/01/MC-004-2019-DO-No-075-2018-Establishing-the-LGU-Fiscal-Sustainability-Scorecard-FSS-in-the-BLGF.pdf)).

### M22. Business Taxes and Fees

From the Part 4 Annex detailed statement, General Fund only:

| Line | Code | Note |
| :- | :- | :- |
| Business Tax | `local.business_tax` | |
| Permit Fees | `local.permit_fees` | |
| Market Operations | `local.market_operations` | |
| Real Property Tax | `local.real_property_tax` | Basic tax, net of its discount |

These figures are always labeled General Fund. They are never added to, or compared with, the consolidated totals in M19 and M21. Community Tax was removed on 26-10-28.

### M23. Budget and Actual Amounts

**Withdrawn from display 26-11-03 by the owner.** The SCBAA amounts are reproducible, but its 20% Development Fund heading covers current appropriations only, while the Executive Summary reports a larger development fund from different amounts (2025: ₱54.56M actual of ₱224.54M in the workbook, against ₱147.46M of ₱340.15M in the Executive Summary). A reader cannot tell which is right from the dashboard alone. The lines stay parsed and stored (`displayed` 0) and return only with a definition that reconciles the two sources.

Renamed 26-11-03 from Development Spending to the statement's own title. From the SCBAA (Statement of Comparison of Budget and Actual Amounts) sheet, Current Appropriations:

| Line | Code |
| :- | :- |
| 20% Development Fund | `sector.development_fund` |
| Economic Services | `sector.economic_services` (agriculture, trade, tourism and other economic offices) |

- **Value:** the sum of every actual amount (personnel, operations, capital outlay, other) listed under the heading.
- **Budget:** the sum of the final budget column is stored with basis `budget_final`.
- **Inputs:** every summed cell and its amount is stored with the figure (`finance_figures.inputs`), for example 2025 Economic Services actual K61:K64 and final budget G61:G64, so the sum can be checked against the workbook.
- **Utilization (retired 26-11-03):** actual ÷ final budget gave 24.30% for the 2025 Development Fund while the Executive Summary reports 43.35% from different amounts. No rate is computed; both amounts stay.
- **Unknown headings:** the parser recognizes the other sector headings only to know where each section ends. Spending under headings outside the catalog is reported as an issue, never added to these two lines.

### M24. Spending by Sector (Withdrawn)

Withdrawn 26-10-28 by the user. Health, education, social welfare and other sector spending, per-sector capital outlay, and expense types are not stored or displayed. The SCBAA headings proved too inconsistent across towns for a reliable cross-town comparison, and they fall outside the business focus.

### M25. Per Capita (Retired)

Retired 26-11-03 by the owner. Income and Expenses per Resident are no longer computed or shown.

### M26. Year-over-Year Change

- **When computed:** only for consecutive fiscal years where both values are shown and share the same `definition_variant`.
- **Absolute change:** current value minus prior value.
- **Percent change:** absolute change ÷ the size of the prior value (its absolute value) × 100, shown to one decimal. There is none when the prior value is zero. Dividing by the size, not the signed value, keeps the sign of the percent equal to the sign of the change, so a loss that deepens from (₱10 million) to (₱20 million) is -100.0 percent, a fall, and Net Income of ₱25 million to a loss of (₱34.9 million) is -247.1 percent. Changed 26-10-28 on the user's correction; for positive values the result is unchanged.
- **Missing years:** never bridged, imputed, or carried forward.
- **Restated years:** use the restated figure. The tooltip says it was restated, and the earlier figure stays in Capitol history.
- **No rating:** an increase is not automatically an improvement, and no good or bad rating follows from a change.

### M27. Sum of Reporting Towns

- **Label:** a total across towns for one line and year is always labeled "Sum of Reporting Towns", never provincial government finances.
- **Coverage:** always stated, for example "13 of 29 towns".
- **Year-over-year change (M26):** computed only over the towns shown in both years, and that matched count is stated.
- **Comparing towns:** use the median of the reporting towns. Missing towns are listed last and unranked, as in M5.

### M28. Businesses by Industry or Business Category

- **Counts:** Active, New and Closed businesses per year, as defined by the `business_years` status rules.
- **Grouping:** by the Business Type's PSIC industry, or by its Business Category (D39).
- **Categories cross industries:** a business has one type and a type has one category, so category counts add up to the town total. Industry and category counts are separate breakdowns and are never added together.

### M29. Declared Sales by Industry or Business Category

Not shown since 26-10-29: the user removed Declared Sales and Share of Town from Business Trends, and the analytics response no longer carries M29 or M30. The definitions stay for any later approved view.

- **Value:** M3 for the group's businesses in a year.
- **Privacy guard:** always applies:
  - P2 and P3 on each group.
  - P4 across the cells of a breakdown.
  - P6 for derived change and share.
  - P7: the public sees bands only.
  - Business Category sales are bands for every audience, because a category crosses industries (P4, D39).
- **Label:** every view shows the "Simulated data" label.

### M30. Industry Share of Town

- **Count share:** the industry's active businesses ÷ the town's active businesses × 100.
- **Sales share:** the industry's declared sales ÷ the town's declared sales × 100. It is hidden whenever either sales figure is hidden (P6).

### M31. Business Year-over-Year Change

- **Definition:** M26 applied to M28 and M29.
- **Hidden figures:** a change derived from a hidden figure is hidden (P6).

### M32. Permit Status

Approved 26-10-29 with the registry plan (D40).

- **Definition:** from the business's latest `business_permits.valid_until`: Expired when before today, Expiring when within `cagayan.registry.permit_expiring_days` of today, otherwise Valid. A business with no permit row is No Permit on File.
- **Computed** in SQL. It is a status label on the record, not a warning or a notification.

### M33. Renewal Streak

Approved 26-10-29 by the user ("years renewed in a row").

- **Definition:** the number of consecutive years with status new or renewed that end in the latest data year (`cagayan.data.latest_year`). A non-renewal or closure ends the streak, and a business not active in the latest year has a streak of 0. Counted within the data years (from `cagayan.data.first_year`), so a business older than the data shows the years on record.
- **Computed** in SQL (`App\Services\RenewalStreak`). Shown as a flame and the number when the streak is more than one year.

### M34. Operating Milestone

Approved 26-10-29 by the user ("exclusive badges for 5 years, 10 years, 20 years").

- **Definition:** the highest value in `cagayan.registry.milestones` that is at most the latest data year minus the year established. Null below the first milestone, or when the business is not active (new or renewed) in the latest data year. It counts years since establishment, not years on record or renewals (M33).
- **Computed** in SQL (`App\Services\OperatingMilestone`). Shown as an achievement medallion on the profile and a small numbered mark on directory rows. Approved records only (P15).

## Config values

All thresholds live in `config/cagayan.php`, never in code or SQL literals.

- `privacy.small_group_min`: 5
- `privacy.dominance_max_share`: 0.70
- `privacy.sales_bands`: the band edges
- `peer.margin`: 0.20
- `outlook.lookback_years`: 3
- `early_warning.min_non_renewals`: 3
- `specialization.min_type_count`: 3
- `data.first_year`: 2020
- `data.latest_year`: 2026
- `registry.milestones`: 5, 10, 20
- `towns.shades`: 5

## How each metric is tested

One Pest test per metric, in `tests/Feature/Metrics/`.

1. Build a tiny fixture by hand: two or three barangays, a handful of businesses, two to four years.
2. Work out the expected value by hand and write the working in a comment above the assertion.
3. Run `metrics:refresh`, then assert the stored value equals the hand-computed one.
4. Add one test for the "not available" case and, for anything involving sales, one for the "hidden" case.

Do not test a metric against the seeded demo data. The seeder can change. The definition cannot.

## Economic Performance Demonstration

Approved 26-10-25. This public page is a synthetic UI/data-contract demonstration, separate from town_facts and the deferred verified COA history. Fixture values and changes are finished synthetic figures; no request or frontend computes financial rates from them. The Query validates applicability: consecutive prior observation, shown values, explicit comparability and matching comparison year. Zero prior values have no percentage; missing calendar years become unavailable observations. Exact currency strings retain cents; display percentage changes to one decimal. Future verified metrics must be computed through the existing SQL summary architecture.

Income and Expenses use equal peso axis bounds. They are city-government accounts, not private-business sales, cash-only expenditure, GDP, prosperity or investment scores. Additional measures require explicit units and accounting basis; charts render metric IDs rather than assuming a revenue/expense object shape.

Demo wishes count approved public phrases, once per wish per label in a future live pipeline. Types and specific businesses are separate presentations and must not be summed as unique respondents. All-time reference date is 26-10-25. Larger cloud words indicate more wishes; exact counts remain in ranked bars and the equivalent table. No real wishes or raw names/reasons are read. Population and registered-business metrics remain deferred.


### Combined Public Wish Ranking Correction

26-10-25: The user approved combining curated business-type and fictional specific-business labels into one public ranking. Preserve each existing count and namespace IDs by source group. Sort count descending, label ascending, then ID ascending for ties. Do not sum type/specific counts or present their combined total as unique respondents: one submission may mention both. Tooltip geography is the actual town attached to the snapshot. Current simulated data supports Tuguegarao only; no comparative most-demanded town or measured market demand is inferred. Snapshot date and all-time basis remain metadata available through source details, rather than an automatic visible caption.
