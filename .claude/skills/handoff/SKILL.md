---
name: handoff
description: Use when the owner asks for a handoff, when ending a long session, or when starting from another agent's handoff. Writes or validates a handoff that any agent (Claude, GPT or other) can act on without the chat history, and sets the rules for working in a checkout several agents share.
argument-hint: "[write | validate] [topic]"
---

# Handoff

A handoff is for a reader with no chat history. It states what is true now, what was verified and how, and what is not done. It never repeats a claim the writer did not check.

## Working in the Shared Checkout

Several agents edit this one folder at the same time.

1. Start: `git branch --show-current`, `git status --short`, then read `docs/plan/README.md` and the active plan. The diff holds other sessions' work; never reset, stash, discard or reformat it.
2. Before editing a file, read it from disk. Before finishing, re-read every file you changed and confirm your change is still there. If another agent overwrote it, reapply it and say so.
3. One test command at a time across all sessions. Parallel workers inside one command are fine.
4. One dev server. Reuse `http://localhost:8000`; never start a second.
5. No commit, push, migration on the development database or bulk import unless the owner asked for it in this session.

## Validate a Handoff You Received

Check its claims against the repository, not against the document.

1. Counts: recount from the files or the database (rows in CSVs, findings by category, registered files and their checksums).
2. State: `php artisan migrate:status`, version constants, manifest checksums.
3. Removals: search the code for what the handoff says is gone.
4. Report three lists: confirmed, not rechecked (and why), and wrong. Correct the document; do not rewrite another agent's structure without need.

## Write a Handoff

Put it in `docs/engineering/<topic>-handoff.md` and link it from the active plan. Use these sections, each short:

1. **Current State.** Branch, what is true now, what is applied to the database, what is uncommitted.
2. **Owner Instructions That Carry Forward.** Decisions and prohibitions, with the decision id.
3. **What Changed.** By area, naming files. Separate inherited work from this session's.
4. **Verification.** A table: check, result, and whether it was run fresh, carried forward or not run. Never write "passed" for a check that was not run after the last change to its inputs.
5. **Not Done.** Every open item, including checks the owner excluded.
6. **Start Here Next.** The next three steps and the exact commands.

Then update the active plan with `/plan-history`, the roadmap line, and `design.md` or the matching rule file for any owner correction. Run `git diff --check` on the documents.
