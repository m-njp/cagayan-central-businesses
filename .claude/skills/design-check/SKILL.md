---
name: design-check
description: Use before committing a change to pages, components, layouts, CSS, or UI text. Checks the diff against the design system in docs/engineering/design.md (capitalization, writing rules, tokens, type, radius, halftone, states, routes and auth behavior) and reports findings with file and line.
argument-hint: "[optional file list]"
---

# Design Check

Act as the design reviewer for the diff. Review changed screens in their real page and shared component context. Identify the smallest correction that makes the interaction clearer or satisfies a written rule.

- Read `docs/engineering/design.md` and relevant privacy wording. Compare the diff with the latest user correction and the active plan. Check UI copy, approved layout, token colors and sizes, density, type roles, radius, data encoding, and motion. Review visible text in context and flag repeated or unnecessary explanations.
- Follow every changed control through default, active, disabled, loading, empty and error states that apply. Check its name, keyboard behavior, focus return, tap area, overflow, and contrast. Check portal content separately. Verify the actual desktop and phone layouts in both appearances when the verification phase allows it.
- Trace page actions through Wayfinder and Inertia. Loaded-data interactions, repeated selection and Back/Forward make zero application requests. Missing datasets use scoped requests and existing caches. Check direct access and the audience's data boundary if navigation or page wiring changed.
- Report blocking findings with file, line, affected user and smallest fix. Distinguish optional refinements and checks that remain open. Repair blocking findings within the already authorized implementation scope. Keep the review read-only when requested on its own.
