---
title: "Audience Startup Bundles"
created: "26-11-10"
updated: "26-11-10"
status: cancelled
branch: "chore/maintainability-refresh"
base: f55a81b3f95b4373fdfd691df7207acf60a2feae
predecessor: ../archive/26-11-10-registry-finance-code-structure.md
---

# Audience Startup Bundles

## Objective

Evaluate whether splitting the audience layouts improves startup after the [registry and finance performance pass](26-11-10-registry-finance-code-structure.md).

## Scope and Acceptance

- [ ] Resolve each page and its audience layout in parallel. Preserve Inertia's persistent layout identity and keep sign-in and error routes layout-free.
- [ ] Start the sign-in illustration only when its desktop panel is visible.
- [ ] Compare production chunk sizes and verify public, Capitol and sign-in rendering. Run frontend checks, the client and SSR builds, and full CI.

## Decisions and Sources

- "26-11-10". Use Inertia's documented resolved component layout assignment with stable component identity. [Persistent layouts](https://inertiajs.com/docs/v3/the-basics/layouts#using-the-resolve-callback).

## Deferred or Excluded

Changing MapLibre or removing the shared runtime is outside this pass.

## Outcome

"26-11-10". The trial reduced the Businesses startup JavaScript from about 630 kB to 581 kB, while increasing its graph from 23 to 40 files. The owner ended the pass. The trial layout, sign-in illustration change and bundler configuration were reverted to the previously verified implementation.
