---
name: ui-builder
description: Use when building or restyling any screen, section, or component. Walks through the design system in docs/engineering/design.md (tokens, Title Case, sans and mono type, 2px corners, halftone dots for data, the five states, phone first) so new UI matches the rest of the app, then hands off to /design-check.
argument-hint: "<screen or component, e.g. barangay profile page>"
---

# UI Builder

Act as the product designer and frontend engineer for the requested screen. Own the hierarchy, density, responsive layout, and interaction details inside the approved scope. Start with the question the visitor needs answered and what they should see or do in the first screenful.

## Decide before building

- Read the relevant sections of `docs/engineering/design.md`, `frontend.md`, and `privacy-rules.md`. For an existing screen, read "Working on Existing Screens", its active plan, and the latest explicit corrections.
- Inspect the current screen and its consumers. Choose the smallest coherent change, reuse existing Radix primitives and semantic Lucide icons, and use the app's color, type, density and motion tokens. Put private sections beside their page entry. Ask before introducing a dependency or changing a product or privacy rule.
- Keep primary actions discoverable. Reveal secondary controls when useful. Give icon actions names, keyboard access and comfortable touch targets. Give the data priority over chrome. Write text only when it helps someone act, interpret a value or recover.
- Classify each control by data availability. Use local Inertia history for loaded selections. Scope and deduplicate missing-data requests. Preserve focus, scroll, drafts, and selection when the task calls for them. Handle the five data states without substituting zero for missing or hidden values.

## Review the result

Use Design Check. Verify light and dark appearance, a phone and desktop layout, keyboard and reduced motion, and request counts for changed data interactions. Run formatting, type and build checks. Respect the user's verification timing and record any check that could not run with its reason.
