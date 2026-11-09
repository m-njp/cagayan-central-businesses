---
name: open-pr
description: Push the current work branch and open a pull request into staging with the template filled in from the real commits, so a teammate can review it in ten minutes.
argument-hint: "[feature or metric id, e.g. C3 or M10]"
disable-model-invocation: true
allowed-tools: Bash(git status*) Bash(git diff*) Bash(git log*) Bash(git branch*) Bash(git fetch*) Bash(git merge origin/staging) Bash(git push -u origin *) Bash(gh pr create *) Bash(gh pr view *)
---

# Open a pull request

**Role: Review coordinator.** Own the work-branch preflight and a PR reviewers can reproduce. Preserve branch protection and draft a description from the actual commits. Hand off the PR URL, checks, and remaining review decisions.

Feature or metric id from the person, if given: $ARGUMENTS

Rules: `docs/team/workflow.md`, "Branching" and "Pull requests". Template: `.github/pull_request_template.md`.

Work pull requests always go into `staging`. A release (`staging` into `main`) is opened by a person, as described in `workflow.md`, "Releases". This skill does not open releases.

## Steps

1. **Check the branch.** `git branch --show-current` must be a work branch, not `main` or `staging`. Uncommitted changes: stop and suggest `/commit`.
2. **Bring in `staging`.** `git fetch origin`, then `git merge origin/staging` if the branch is behind. Resolve conflicts with the person. GitHub requires the branch to be current before merging.
3. **Check the size.** `git diff --stat origin/staging...HEAD`. Over about 400 changed lines, not counting lock and generated files: say so and suggest a split before continuing.
4. **Push.** `git push -u origin <branch>`. The pre-push hook builds the front end and runs `composer ci:check`, the same as CI. It needs the database running (`composer db:up`). If it fails, fix the cause and push again. Never `--no-verify`, never force push.
5. **Write the description.** If `/commit` printed one in this session and nothing changed since, reuse it. Otherwise fill `.github/pull_request_template.md` from `git log origin/staging..HEAD` and the diff, exactly as `/commit` step 7 describes: What, Why, How, How to check it, Impact, When, Who, Screenshots, Checklist. Write "none" where nothing changed. Tick only what is true.
6. **Title:** one Conventional Commit line, `type(scope): subject`, 72 characters or fewer. Squash merge makes it the commit on `staging`. CI checks the format.
7. **No generated-by footer** and no AI attribution anywhere.
8. **Create it.** Write the body to a temporary file so line breaks survive PowerShell, then `gh pr create --base staging --title "<title>" --body-file <file>`. Print the link.
9. **Remind the person** to attach screenshots for visual changes, and to set the feature to Done in `docs/team/roadmap.md` in this pull request if it finished.

## Done when

The branch is pushed with the hooks passing, a pull request exists into `staging` with a valid title and a fully filled description, and its link is shown.
