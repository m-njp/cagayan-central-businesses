---
name: plan-history
description: Record and maintain this repository's agreed implementation plans, scope changes, verification evidence, and completion history under docs/plan/active and docs/plan/archive. Use when starting, revising, completing, or superseding planned project work.
---

# Plan History

Read `docs/plan/README.md` and the relevant existing plan before implementation. A plan records the user's authorized scope; it does not grant permission to implement deferred features, publish, merge, or contact people.

## Record or Resume

- Reuse the matching active plan rather than creating a duplicate for each session. Search archived plans before changing an established decision.
- For a new agreed plan, use `template.md` and save it as `docs/plan/active/yy-mm-dd-short-name.md`. Use the user's local date, Asia/Manila for this project. Inside the document, quote dates as `yy-mm-dd`, for example `"26-10-24"`. Use dashes in both metadata and filenames.
- Record the objective, acceptance checks, explicit exclusions/deferred work, important decisions with their reasons/sources, affected areas, and branch/base when known. Ask only for ambiguity that materially changes scope; continue independent work.
- Link the entry from `docs/plan/README.md`. The index should make active and deferred work distinguishable without opening every plan.

## Maintain During Work

- Preserve the original acceptance checks and dated decisions. Add a dated change entry explaining what changed, why, and what it supersedes; do not silently rewrite history into a different plan.
- Keep checkboxes factual. Distinguish implemented behavior, source inspection, automated checks, and manual verification. A passing test is not proof that imported facts are correct.
- Record meaningful implementation changes and limitations, referencing repository files, exact primary sources, commits, or PRs where available. Never put credentials, protected records, raw resident text, or private session data into plans.
- Keep the roadmap and canonical product/security/schema/design/metric docs consistent; link to them instead of copying their full contents. Explicit later user corrections take precedence over earlier plan examples.

## Close or Replace

- Complete only after all checks in the plan's agreed scope pass and required reviews are resolved. Deferred exclusions are not open acceptance checks.
- Add actual verification results, remaining limitations, and handoff references; move the same file into `docs/plan/archive/` and update the index and inbound links.
- If work is cancelled or replaced, archive with `cancelled` or `superseded`, explain unfinished work, and link its successor. Never mark it completed merely because the session ends.
- For an improvement to completed work, create a new active plan linking its predecessor. Do not reopen and overwrite the archived plan.

Before handoff, check Markdown links, date/filename consistency, the index, and `git diff --check`. This skill adds documentation, not an extra approval step for work the user has already authorized.
