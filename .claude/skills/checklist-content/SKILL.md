---
name: checklist-content
description: Add or update one item in the start-a-business guide, from an official source the person supplies, with its verification date.
argument-hint: "[what the item is, e.g. barangay business clearance for a storefront]"
disable-model-invocation: true
---

# Checklist content

**Role: Content editor.** Own plain-language wording, placement and reuse within the supplied source. Escalate unclear jurisdiction, eligibility, verification date or conflicting official instructions. Hand off the source, checked date and exact requirement supported by it.

Item to add or change: $ARGUMENTS

The guide tells a real person what to bring to a government office. A wrong requirement costs them a wasted trip, and an invented one damages the project in front of the judges. Nothing goes in without an official source checked by a team member.

## Read first

1. `.claude/rules/content.md`.
2. `docs/engineering/database.md`, "Content outside the database": the fields every item has.
3. `docs/product/mvp-scope.md`, feature P4 and "Verification findings", section 4, for sources the team already has.
4. The files in `resources/content/checklists/`, to match their format and avoid duplicate ids.

## Steps

1. **Branch.** If on `main` or `staging`, create a work branch inside `staging`: `data/<short-name>`.
2. **Ask the person for,** and do not continue without:
   - the official source link (a government page or document)
   - the date they checked it
   - their name, for `verified_by`
   A document with no link: ask how it should be cited. Never substitute a link you found yourself.
3. **Read the source** if it can be fetched. Write only what it says: the requirement, the office (`where`), any condition. Never add a fee, processing time, office, or document from memory or from another town.
4. **Fill every field:** `id` (stable slug, never reused), `title` (sentence case, no separators), `where`, `applies_when` (in terms of the four questions: ownership, activity, premises, employees), `order`, `source_url`, `verified_on` (`YYYY-MM-DD`, the date the person gave), `verified_by`.
5. **Check the order** makes sense next to the other items.
6. **List stale items:** every item whose `verified_on` is more than 60 days before today, so the person can recheck them.
7. **Run** `php artisan test --compact`. The content test rejects items without `source_url` and `verified_on`.

## Done when

The item has all eight fields, its text matches the source, the source and date came from the person, stale items are listed, and the tests pass. Then suggest `/commit`.
