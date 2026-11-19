---
name: commit
description: Review the changes, run the checks, make one clean Conventional Commit, then print the filled pull request description for the branch. Never pushes.
argument-hint: "[optional hint about what changed]"
disable-model-invocation: true
allowed-tools: Bash(git status*) Bash(git diff*) Bash(git log*) Bash(git show*) Bash(git branch*) Bash(git switch -c *) Bash(git rev-parse*) Bash(git merge-base*) Bash(git add *) Bash(git commit *) Bash(php artisan test*) Bash(composer lint) Bash(npm run check:fix)
---

# Commit

**Role: Change integrator.** Own the diff review, staging of the authorized files and the commit summary. Keep unrelated working changes intact and surface failed checks before committing. Hand off the commit hash and PR draft with actual verification evidence.

Make one commit a teammate can understand from the log, then hand the person a ready pull request description. Hint from the person, if any: $ARGUMENTS

Rules: `docs/team/workflow.md`, "Branching" and "Commits".

## 1. Check the branch

Run `git branch --show-current`.

- `main` or `staging`: stop. Work happens on branches inside `staging`. Propose a name (`feat/home-animations`, `bug/insert`, `data/...`, `docs/...`, `chore/...`) and, when the person agrees, run `git switch staging`, `git pull --ff-only`, `git switch -c <name>`. Uncommitted changes come along to the new branch. The hooks refuse commits on `main` and `staging` anyway.
- A name that does not follow `type/short-description`: say so, and carry on.

## 2. Read what changed

Run `git status`, `git diff`, and `git diff --staged`. Summarize the change in one sentence.

If it is more than one logical change (for example a migration plus an unrelated page fix), propose how to split it into separate commits and wait.

## 3. Review the diff for things that must not slip in

Go through each item. Report what you find before committing.

| Check | How | If found |
| :- | :- | :- |
| `.gitignore` changed | `git diff HEAD -- .gitignore` | Show the change. Ask why. A new ignore rule can hide files the team needs. A removed one can expose `.env`, build output, or dumps. Commit only with a clear reason. |
| Dependencies added, removed, or upgraded | Diff `composer.json`, `composer.lock`, `package.json`, `package-lock.json` | List each package and version. A new package needs the person's approval and a line in `docs/engineering/decisions.md` (D6 or a new entry). The manifest and its lock file go in the same commit. Development tools belong in `require-dev` or `devDependencies`. |
| Files in `public/` | `git status -- public` | `public/build/`, `public/hot`, and `public/storage` are never committed. A new icon or image is fine if it is small (under about 200 KB) and used. |
| Secrets and private data | File names and added lines | Never stage `.env` or `.env.*` (except `.env.example`), keys, tokens, passwords, database dumps, or real business records. A new env key goes in `.env.example` with a safe default. |
| Generated files | `git status` | `resources/js/routes/`, `resources/js/actions/`, `resources/js/wayfinder/`, `vendor/`, `node_modules/` are never committed. |
| Debugging leftovers | Added lines | `dd(`, `dump(`, `ray(`, `var_dump(`, `console.log(`. Remove them. |
| Docker files changed | `git diff HEAD -- compose.yaml docker/` | The whole team and CI use them. Commit only if the task is about Docker, as its own commit. Check the image tag matches `.github/workflows/ci.yml`. Put "run `composer db:up`" in the pull request's "After merging" line. |
| Edited migration | `git log origin/staging -- database/migrations/<file>` | A merged migration is never edited. Move the change to a new migration. |
| Docs that should change too | The diff | Schema: `docs/engineering/database.md`. New package or decision: `decisions.md`. Finished feature: `docs/team/roadmap.md`. |
| UI text and visuals | Changed `.tsx` | No em dashes, middots, or separators in titles. No gradients or raw colors. If unsure, run `/design-check`. |
| Business data | Controllers, Data objects, Queries, pages, exports | Run `/privacy-check` first. |

## 4. Run the checks

- Only `*.md` files changed: skip this step.
- Otherwise run the tests that cover the change, then the whole suite: `php artisan test --compact`. Stop on failure and show it. Do not commit around a red test.

The pre-commit hook runs Pint, PHPStan, the front end lint, and TypeScript when you commit, so you do not need to run them separately. If it fails, fix the cause (`composer lint`, `npm run check:fix`, or the reported error), stage again, and commit again. **Never use `--no-verify`.**

## 5. Stage named files only

`git add <path> <path>`. Never `git add .` or `git add -A`, so nothing unreviewed slips in.

## 6. Write the message

- Subject: `type(scope): subject`. Imperative, lower case after the colon, 72 characters or fewer, no period. Example: `feat(capitol): add barangay profile page`.
- Types: `feat`, `fix`, `docs`, `test`, `refactor`, `chore`, `ci`, `data`.
- Scopes: `capitol`, `public`, `metrics`, `privacy`, `data`, `map`, `guide`, `infra`.
- A body only when the reason is not obvious from the subject. Then say why, in a few lines. Mention the feature or metric id (C3, M10).
- No trailers of any kind: no co-author lines, no generated-by line, no session link.

Commit with `git commit -m "<subject>"` (add `-m "<body>"` for a body). The hooks check the format. Show `git log --oneline -1`.

## 7. Print the pull request description

After every commit, print the pull request description for the **whole branch**, so the person can paste it into GitHub or hand it to `/open-pr` without another session.

1. Gather: `git log --oneline origin/staging..HEAD` and `git diff --stat origin/staging...HEAD`. The pull request goes into `staging`.
2. Read `.github/pull_request_template.md` and fill every section from the real commits and diff:
   - **Title:** one Conventional Commit line for the whole branch.
   - **What:** one or two plain sentences, plus the feature or metric id and type.
   - **Why:** the need, with a link to the doc section or issue.
   - **How:** 2 to 5 bullets naming the main files and the decision behind each.
   - **How to check it:** exact URL, account, clicks, and what the person should see.
   - **Impact:** database, dependencies, config and env, `.gitignore` and public files, privacy, breaking changes. Write "none" where nothing changed. Use what you found in step 3.
   - **When:** what it depends on, and what teammates must run after merging (`composer install`, `npm install`, `composer db:reset`, or nothing).
   - **Who:** author from `git config user.name`, reviewers from `.github/CODEOWNERS` and the two-reviewer rule.
   - **Checklist:** tick only what is true. Answer the privacy questions or write "no business data touched".
3. Print it inside one fenced `markdown` block, title first.

## 8. Never push

Pushing and opening the pull request are `/open-pr`.

## Done when

One commit exists on a work branch inside `staging`, the review in step 3 found nothing unresolved, the tests passed (or only docs changed), the hooks passed, `git log -1` shows a correct subject with no trailers, and the filled pull request description is printed.
