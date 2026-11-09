---
paths:
  - "app/Http/**"
  - "app/Data/**"
  - "app/Queries/**"
  - "app/Services/Privacy/**"
  - "resources/js/pages/**"
---

# Privacy rules in short

Full rules and reasons: `docs/engineering/privacy-rules.md`. Read it before changing anything here. With Inertia, every prop reaches the browser, so protection happens on the server.

- **P1** No single business's sales leaves the server: not in props, JSON, CSV, PDF, logs, or errors. No Data object has a field for it.
- **P2** Fewer than `cagayan.privacy.small_group_min` active businesses: sales hidden, reason `small_group`. Administrators included.
- **P3** Largest single business over `cagayan.privacy.dominance_max_share` of the total: hidden, reason `dominance`. Uses `declared_sales_max_single`.
- **P4** Exactly one hidden cell in a breakdown shown beside its total: also hide the smallest remaining cell, reason `complementary`.
- **P5** The guard runs after filters, on the final group.
- **P6** Anything derived from a hidden figure is hidden: per business, share, change, rank, concentration, outlook.
- **P7** The public sees a band only, never a number. Hidden stays hidden, with no band.
- **P8** Owner name, owner contact, street address, ownership type, size category, employee count, registration and permit numbers, TIN (masked, never sent to the edit form), RDO, review notes and documents: Capitol administrators and Staff of the business's town only. The business's own phone and email are public; a home-based business never sends its pin.
- **P9** Opening a full business record, its edit form or a document writes one `record_view_logs` row before responding. The list view writes none.
- **P10** Pages get Data objects from `app/Data/`, never models, collections, or `->toArray()` of a model, including shared props in `HandleInertiaRequests`. Separate public and admin objects.
- **P11** CSV and PDF are built from the same guarded Data objects.
- **P12** Wishes store no name, contact, or raw network address. A browser is the SHA-256 hash of an anonymous signed cookie token, plus a daily keyed network hash used only to cap new respondents (D21). One new wish per 3 days, 3 edits per 7 days, from `cagayan.wishes`. Honeypot, signed form timer, plain-text reasons with no links. Reasons are admin only, plus the browser that wrote them. Rate limited per IP per hour.
- **P13** The "Simulated data" label is in the shared layout and every export whenever simulated records are on. They are off by default (D37), and with the flag off no business figure is produced.
- **P14** No sales, owner names, or contact numbers in logs or exception text.
- **P15** Only approved registry records reach public routes or `stats_` tables (D40).

`auth.user` is the `SessionUser` Data object (name only). Keep it that way.

Before committing, run `/privacy-check` or the `privacy-reviewer` agent.
