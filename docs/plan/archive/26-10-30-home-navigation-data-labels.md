---
title: "Home Navigation and Data Labels"
created: "26-10-30"
updated: "26-10-30"
status: completed
branch: "feat/business-registry"
base: staging
predecessor: ../archive/26-10-25-compact-home-session-handoff.md
---

# Home Navigation and Data Labels

## Objective

Help residents find Home, Economy, Businesses and Suggestions through a clear text-only navbar. User requested a filled selection instead of an underline, removal of the navbar simulated label, and red data labels at the upper right of their cards.

## Scope and Acceptance

- [x] Desktop navigation has text-only links, a filled active state and keyboard focus.
- [x] Navbar has no Simulated data label.
- [x] Home and Economy Sample Data labels sit at the upper right of their wish cards in red token-based badges.
- [x] Public simulated business labels remain with the directory, profile and Business Trends content through the shared config-driven component.
- [x] Format/lint, TypeScript, production build and final browser verification.

## Deferred or Excluded

No changes to routes, data, privacy guards, map behavior or mobile navigation.

## Decisions and Sources

- "26-10-30": Read CLAUDE.md and UI Builder, Tailwind, Plan History and Design Check skills. The user's explicit navbar and badge corrections supersede the previous icon/underline/amber design examples. All existing navigation destinations and mobile controls remain.
- Loaded-data interactions are unchanged; no data fetching or selection handlers changed.

## Change History

| Date | Change | Reason / Reference |
| :- | :- | :- |
| "26-10-30" | Text-only desktop links with filled active state; contextual red data labels | User request; design.md Public Navigation and Data Labels |

## Verification and Handoff

- npm run check, npm run types:check and npm run build passed using the installed npm.cmd launcher. Build retains the existing large-chunk advisory.
- Browser: Home checked at 1440px and 375px, light and dark; no horizontal overflow. The Home card badge aligns at its header top on phone width. Desktop links contain no SVG icons or underline; current destination is marked with aria-current. Keyboard Tab shows a visible focus ring. Economy navigation and both card labels verified.
- Design Check: token colors, copy, spacing, focus, fixed label wording and requested scope reviewed; no must-fix findings in these changes. Data selection/request behavior was unchanged.
- composer ci:check passed frontend checks and all 29 frontend tests, then stopped in Pint because tools/coa/.pytest_cache is unreadable. PHPStan and Pest were not reached. Initial global Composer configuration access was avoided through a process-local temporary COMPOSER_HOME. No permissions changed.
- Public directory/profile label placement was inspected in source; those pages were not separately browser-tested. Existing unrelated working changes preserved. No commit or push requested.