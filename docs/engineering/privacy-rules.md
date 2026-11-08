# Privacy Rules

These rules are part of the product, not a layer added at the end. Each one is written so it can be tested. A pull request that touches business data must keep every test here green.

The reasons behind the rules are in the product document, under "Privacy rules built into the product" and "Verification findings".

## The one idea behind all of them

With Inertia, everything a controller passes to a page is delivered to the browser and can be read there, whether or not React displays it. So protection happens on the server, before data leaves. Hiding a value in the front end protects nothing.

## Field classes

- **Public:** business name, type, description, year established, barangay, business phone and email (the business's own contact), the BIR Registered and Permit Status badges, and the map location of storefronts only. Home-based businesses show their barangay, never their pin (D40, 26-10-29).
- **Admin:** owner name, owner contact number, street address, ownership type, size category, number of employees, registration and permit numbers, TIN (masked to its last three digits), RDO code, review notes, and every uploaded document.
- **Never displayed:** the declared gross sales of a single business.

## The rules

### P1. No single business's sales ever leaves the server

Not to the public and not to administrators. Not in page props, JSON, CSV, PDF, logs, or error messages.

- Enforce: the sales column is hidden on the model, and no data object has a field for it. Request code reads sales only from summary tables.
- Test: scan every route's response for a known seeded sales amount and assert it is absent.

### P2. Small-group rule

A sales figure for a group with fewer than 5 active businesses is hidden, for everyone, administrators included. The threshold is `privacy.small_group_min`.

- Test: a group of 4 returns hidden with reason `small_group`. A group of 5 returns a value.

### P3. Dominance rule

A sales figure is hidden when the largest single business accounts for more than 70 percent of the group's total. The threshold is `privacy.dominance_max_share`.

- Enforce: read `declared_sales_max_single` from the summary table and compare with the total.
- Test: a group of 6 where one business holds 80 percent returns hidden with reason `dominance`.

### P4. Complementary rule

When a breakdown is shown beside its total, a single hidden cell can be worked out by subtraction. So if exactly one cell in a breakdown is hidden, also hide the smallest remaining cell, with reason `complementary`.

- Applies to: sales by business type within an area, sales by barangay within a town, and sales by industry or by Business Category within a town.
- Test: a breakdown with one small cell and three large ones shows two hidden cells, not one.
- Crossing groups (user decision, 26-10-28, restated for D39): a Business Category partitions businesses, but it crosses industries, so a category total minus an industry total can isolate a group the guard never checked. Category sales are therefore shown as bands to every audience, Capitol included, with no exact figure, change or sales share. Industry sales keep exact guarded figures in the Capitol.
- Test: the Capitol receives a band and no value for a Business Category whose industry figure is shown.

### P5. The rules run after filters

Filters by type, size, status, and year shrink groups. The guard runs on the final filtered group, every time.

- Test: an area that shows a figure unfiltered returns hidden when a filter leaves 3 businesses.

### P6. Anything derived from a hidden figure is hidden too

Sales per business, share of total, change from last year, rank by sales, sales concentration, and the sales outlook all inherit the hidden state. A change from last year is hidden if either year is hidden.

- Test: for a hidden area, each derived value is hidden and none is zero.

### P7. The public sees bands only

The public side shows the band that contains a total, never the total. A hidden total stays hidden and shows no band.

- Enforce: public data objects carry a band label and nothing numeric about sales.
- Test: no public response contains a sales number.

### P8. Admin fields need a login

Admin-class fields (see "Field classes") appear only in responses to Capitol administrators and to Staff assigned to the business's town (D40, 26-10-29). Staff of another town see the record without them. The TIN is always sent masked, and the edit form never receives it: a blank TIN keeps the stored one.

- Test: every public route's response is scanned for a known seeded owner name and contact number, and both are absent. Staff of another town get no admin fields, permit numbers or documents.

### P9. Every full record view is logged

Opening a business's full record, its edit form, or one of its documents writes a row to `record_view_logs` with the user and the time, before the response is sent. The directory list shows public fields only. Owner details appear only on the record page and its edit form.

- Test: opening a record adds exactly one log row for that user and business. The edit form and a document add one each. Viewing the list adds none.

### P10. Pages receive data objects, never models

Controllers build explicit data objects and pass those. No Eloquent model, collection of models, or `toArray()` of a model is passed to Inertia, including through shared props.

- There are separate objects for the two audiences, for example a public business object and an admin business object. The public one has no admin fields to leak.
- A sales figure is always passed as a guarded object with a state (`shown` or `hidden`), a value when shown, and a reason when hidden.
- Enforce: an architecture test that fails when a controller passes a model to `Inertia::render`.

### P11. Exports go through the same guard

CSV and PDF are built from the same guarded data objects as the screens.

- The MVP has no bulk export of owner names or contact numbers.
- Test: a CSV of an area with a hidden figure contains the hidden marker, not a number.

### P12. Wishes collect no resident identity

The form collects no resident name, contact, or account. It may store the named business the resident wishes their area had. That value is hidden from everyone except the browser that sent it; public pages show only aggregate counts. The raw network address is never stored. A browser is recognized by a random token in an encrypted, signed, http-only cookie, and the database keeps only the token's SHA-256 hash (decisions.md, D21). The cookie is set on the first wish, never on a visit. Each wish also keeps a keyed hash of the network address and the date, which changes every day, cannot be reversed without the app key, and is used only to cap new respondents per network per day.

- **Cooldown:** one new wish per browser every `wishes.cooldown_days` (3) days.
- **Edits:** up to `wishes.edits_per_window` (3) edits per browser in any rolling `wishes.edit_window_days` (7) days. Only the browser that made a wish may edit it. Anyone else gets a 404.
- **Rate limit:** `wishes.per_hour_per_ip` submissions and edits per network address per hour. The address lives only in the rate-limit cache. This still holds when someone clears cookies.
- **Bots:** a hidden honeypot field and a signed form timestamp (too fast, too old, or forged is rejected).
- **Input:** the specific business name and reason are plain text. Tags and control characters are removed, links are rejected, and their lengths are capped by `wishes.specific_business_max` and `wishes.reason_max` (255 characters).
- **New respondents:** at most `wishes.new_respondents_per_network_per_day` per network hash per day.
- The free-text reason is shown to administrators only, because a resident may type something personal. The public sees counts. The browser that wrote a reason gets it back so it can edit it. No other browser does.
- Test: the wishes table has no column for a resident name, contact, or raw network address, and the stored network hash does not contain the address and changes the next day. A second wish within the cooldown is rejected. An edit past the limit is rejected. Another browser cannot edit. Another browser's company name and reason never appear in a response.

### P13. The simulated label is always present

Every screen and every export that shows business records carries a visible "Simulated data" label while the dataset is simulated. Simulated records are off by default (`CAGAYAN_SIMULATED`, decisions.md, D37): with the flag off no business figure is produced at all, so no label is needed, and the label is never shown without records behind it.

- Enforce: the shared config-driven SimulatedLabel is rendered by the Capitol layout and the public cards containing business records. Public navigation has no label (user correction 26-10-30). Export templates retain the label.
- Test: every page that shows business data renders the label. Every export contains it. With the flag off, no response carries business figures (`BusinessTrendsPrivacyTest`).

### P14. Logs and errors carry no protected data

No sales amounts, owner names, or contact numbers in log messages or exception text. Debug mode is off for any demo that others can reach.

### P15. Only approved records are public

Drafts, submitted and returned registry records never reach a public route, and only approved records count in `stats_` tables (D40).

- Enforce: public queries filter `record_status = 'approved'`; the metric SQL does the same.
- Test: a draft, a submitted and a returned record are absent from the public list and return 404 on their public profile.

## The guard

One service applies P2 through P7. Everything that shows a sales figure calls it.

- Input: a total, the count of active businesses behind it, and the largest single contribution.
- Output: a guarded figure with state `shown` and a value, or state `hidden` and a reason of `small_group`, `dominance`, or `complementary`.
- The public variant turns a shown value into a band.

Nothing else in the codebase decides whether a sales figure may be shown.

## Words on screen

- Hidden figure: "Hidden to protect individual businesses"
- Non-renewal: "Did not renew. This usually means closure, but the business may have moved or kept operating without a permit."
- Coverage: "Registered businesses only"
- Declared sales: "Declared business activity, not the income of residents or of the barangay government"
- Wishes definition: voluntary expressed interest is not measured demand. Keep this limitation in metric/data documentation. The 26-10-25 user correction removes the repeated on-screen disclaimer from Economy; curated counts and their actual town remain labeled without implying market demand.
- Peer comparison: "Below peer average"

## Review checklist for any pull request that touches business data

1. Does any new response include a field from the admin class or the never displayed class? If so, is the route behind a login, and is a view logged?
2. Is every sales figure produced by the guard?
3. Does a new filter change group sizes? If so, is there a test for the hidden case?
4. Does a new page or export carry the simulated label?
5. Was a model passed to a page anywhere?
