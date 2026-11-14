---
title: "Finance Trends People Can Understand"
created: "26-10-29"
updated: "26-10-29"
status: superseded
branch: "feat/analytics-clarity"
base: staging
predecessor: ../archive/26-10-28-finance-ledger-redesign.md
---

# Finance Trends People Can Understand

## Objective

Answer: How have this town's government finances changed over time? The user approved a shared chart-led redesign for public Economy and Capitol Analytics on "26-10-29".

## Scope and Acceptance

- [x] Default to Trends and Max; visible 3Y / 5Y / Max controls use local history.
- [x] Revenue and expenses share a chart with latest figures and existing annual changes; surplus or deficit has an aligned zero-baseline bar chart.
- [x] Existing breakdowns focus one selectable metric. Retain tables, exact amounts, General Fund labels, source links through Yearly Summary and short Help.
- [x] No interpolation, invented observations, new metrics or source collection.
- [ ] Automated checks and design review pass.
- [ ] Both pages checked at desktop/phone sizes, both themes, keyboard/touch and local navigation.

## Decisions and Sources

"26-10-29": Supersedes the table-first default of the predecessor. Reuses Recharts, the analytics catalog, SQL annual changes and local Inertia history. Old period URLs remain supported. Missing intermediate years and incomparable definitions break lines. Totals are parsed and published for 2019 through 2025; FS breakdowns begin in 2023, annex details in 2024. See data/towns/finances/README.md and docs/engineering/metrics.md M19 to M27.

## Deferred or Excluded

New workbooks, packages, economic scores, inflation adjustments, forecasts, maps, wishes and business-record workflows.

## Verification and Handoff

Read-only database query confirmed seven published Tuguegarao revenue/expense/surplus years. Revenue matches committed parsed output, including 2019 PHP 1,048,262,291.06 and 2025 PHP 1,679,019,473.60. Further check results pending.

## Superseded "26-10-29"

Superseded by [Executive Summary View and Net Income](../archive/26-10-29-executive-summary-view.md), which makes the Executive Summary the default view and renames Surplus or Deficit to Net Income. Trends remains a View option. The two open checks (automated checks and design review; phone, desktop, theme and keyboard checks) move to that plan.
