# Sample Wishes for the Economy Page

`demo.php` holds the sample community wishes for Tuguegarao City on the public Economy page. They are a demonstration, not real submissions: the Economy page labels them "Sample Data", and nothing is drawn from live wishes.

- `types` use business type codes from `database/seeders/BusinessTypeSeeder.php`. `WishSeeder` stores the same counts as wish rows, so Economy and the Suggestions tally agree.
- `specific` lists named businesses residents might ask for. They exist only in this file; the wish table stores business types.
- `asOf` and `period` describe the snapshot. Counts are pilot sized and fixed, so every laptop shows the same numbers.
- `EconomicPerformanceQuery` ranks the items by count, then label. No resident text, reason, hash or private field is read or shown.

Government finances do not come from here. They are the signed-off COA figures in `data/towns/finances` read through `stats_finance_year` (see `docs/engineering/frontend.md`, "Government Finances Ledger Table"). Business trends are off unless `CAGAYAN_SIMULATED` is true (`docs/engineering/decisions.md`, D37).

To replace the sample with live wishes, aggregate the `wishes` table by approved label, count each wish once, and keep company names, reasons and respondent identifiers private (privacy-rules.md, P12).
