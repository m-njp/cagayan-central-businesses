# Team Workflow

How three people and their AI agents work in one repository without breaking `staging` or `main`. Every person and every agent follows this.

## Roles

Every member has one role. The role owner decides on their area and reviews changes to it. Anyone can still help anywhere.

| Role | Name | GitHub |
| :- | :- | :- |
| Frontend developer | ________ | ________ |
| Backend developer | ________ | ________ |
| Data and content | ________ | ________ |

One of the three is also the **repository owner**: GitHub settings, branch protection, and releases into `main`.

### Frontend developer

- **Owns:** `resources/js/` (pages, layouts, components, hooks), `resources/css/` (tokens), the map, charts, and how both sides look and read.
- **Reads first:** `docs/engineering/frontend.md`, `docs/engineering/design.md`.
- **Uses:** `/feature-builder` for the page part of a feature, `/design-check` before every UI commit.

### Backend developer

- **Owns:** `app/` (controllers, Queries, Actions, Services, Data objects, models), `routes/`, `database/migrations/`, `database/sql/metrics/`, `config/`, the Docker setup (`compose.yaml`, `docker/`), the privacy guard, login, exports, CI and deploy.
- **Reads first:** `docs/engineering/architecture.md`, `database.md`, `metrics.md`, `privacy-rules.md`.
- **Uses:** `/db-change`, `/metric-builder`, `/feature-builder` for the server part of a feature, `/privacy-check`.

### Data and content

- **Owns:** `data/` (boundaries), `database/seeders/`, `resources/content/` (the start-a-business checklist), and the submission documents.
- **Reads first:** `docs/engineering/database.md`, `data/boundaries/README.md`.
- **Uses:** `/checklist-content`, `/demo-check`.

### How frontend and backend split a feature

The Data object is the contract between the two developers.

1. **Agree the shape first.** In the issue, list the Data object's fields and the matching TypeScript type. Both developers approve it.
2. **Backend** builds the route, controller, Query or Action, Data object, and server tests on `feat/<id>-<name>`.
3. **Frontend** builds the page against the agreed type on `feat/<id>-<name>-page`, created from `staging`, using sample props until the backend merges. Then `git merge origin/staging` and switch to the real props.
4. A small feature can stay one branch with one developer.

Two reviewers are needed for `docs/engineering/metrics.md`, `docs/engineering/privacy-rules.md`, and `app/Services/Privacy/`, because a mistake there reaches every screen.

## Branching

Two long-lived branches. All work happens in branches inside `staging`.

| Branch | Is | Gets changes from |
| :- | :- | :- |
| `main` | **Production.** What the Capitol, the judges, and the deploy see. Always releasable. | `staging` only, by a release pull request |
| `staging` | **Development.** Where all work comes together and gets tested. The default branch on GitHub. | Work branches, by pull request |
| `feat/...`, `bug/...`, and the rest | **One task, inside `staging`.** Created from `staging`, merged back into `staging`, never into `main`. Lives one to two days. | Your commits |

```
main     ──●───────────────────────────●──────   production, tagged releases
            \                         / release (merge commit)
staging  ────●──────●───────────●────●──────────   development, default branch
              \    /  \        /
   feat/home-...●──●   bug/insert●                 work branches, squash merged into staging
```

Nobody commits or pushes directly to `main` or `staging`. The hooks refuse it, and GitHub branch protection refuses it.

### Branch names

`type/short-description`, lower case with hyphens. Put the feature or metric id first when there is one.

| Type | For | Example |
| :- | :- | :- |
| `feat/` | A feature, metric, or improvement | `feat/home-animations`, `feat/c3-barangay-profile`, `feat/m1-net-growth` |
| `bug/` | Something broken, on `staging` or in production | `bug/insert`, `bug/wish-count-refresh` |
| `data/` | Seeder, boundaries, checklist content | `data/f3-demo-seeder` |
| `docs/` | Documents only | `docs/design-tokens` |
| `chore/` | Setup, dependencies, tooling, CI, refactors, tests | `chore/install-maplibre` |

Branch names and commit types are separate things. A `bug/` branch has `fix(...)` commits. See "Commits" below.

### Everyday work

Start a task with a branch inside `staging`:

```
git switch staging
git pull --ff-only
git switch -c feat/home-animations
```

Bring in new work from `staging` while you work:

```
git fetch origin
git merge origin/staging
```

Finish:

```
/commit       (in Claude Code) or git commit
/open-pr      (in Claude Code) or git push -u origin feat/home-animations, then open a pull request into staging
```

The pull request goes into `staging` and is **squash merged**: the whole branch becomes one commit on `staging`, titled with the pull request title.

Rules:

1. **One branch per task.** One feature id, one metric id, one bug. If it grows past two days or about 400 changed lines, split it.
2. **Merge, do not rebase.** Squash merge flattens the history anyway, so there is no need to rewrite your branch or force push.
3. **No force push to shared branches.** Only to your own branch, and only with `--force-with-lease`.
4. **Depending on unmerged work:** wait for it to merge if you can. If you cannot, create your branch from it, say so in the pull request, and retarget to `staging` once the first one merges.
5. **Delete the branch after merging.** GitHub does this automatically.

### Releases

`main` changes only through a release from `staging`. When `staging` is stable and `/demo-check` passes on it:

1. Open a pull request from `staging` into `main`, titled `chore(infra): release v0.3.0`.
2. Merge it with **Create a merge commit**, not squash. A merge commit keeps `main` and `staging` sharing history, so the next release has no conflicts.
3. Tag the release on `main` and push the tag:

```
git switch main
git pull --ff-only
git tag v0.3.0
git push origin v0.3.0
```

The tag triggers the production deploy once deploys are on (`docs/engineering/deployment.md`). Versions: `v0.x.0` for each demo-ready release, `v1.0.0` for the video build.

An urgent production bug follows the same path, only faster: a `bug/` branch inside `staging`, merged, then a release right away.

### Freezes

**Feature freeze: Monday, October 26.** After it, only `bug/` branches merge into `staging`, and `staging` is released to `main` before recording on October 27.

## Commits

- Conventional Commits: `type(scope): subject`. The commit-msg hook checks it.
- Types: `feat`, `fix`, `docs`, `test`, `refactor`, `chore`, `ci`, `data`.
- Scopes: `capitol`, `public`, `metrics`, `privacy`, `data`, `map`, `guide`, `infra`.
- Subject in the imperative, lower case, 72 characters or fewer, no period: `feat(capitol): add barangay profile page`.
- One logical change per commit.
- No attribution trailers or generated-by lines. The hook strips them.

## Checks that run on their own

`composer setup` points Git at `.githooks/`. The hooks run for people and for AI agents alike.

| When | What runs | Time |
| :- | :- | :- |
| `git commit` | Blocks commits on `main` and `staging`, secrets, build output, and debugging leftovers. Then, for the file types you changed: PHP format (Pint), PHPStan, front end format and lint, TypeScript. Docs-only commits skip the checks. | About 5 to 25 seconds |
| Commit message | Strips AI attribution, checks the Conventional Commit format | Instant |
| `git push` | Blocks pushes to `main` and `staging`. Builds the front end and runs `composer ci:check`, the same checks as CI, including all tests. Needs the database running. | About 45 seconds today, growing with the tests |
| Pull request | GitHub runs CI on PostGIS, and checks the title format | A few minutes |

If a hook fails, fix the cause: `composer lint` for PHP formatting, `npm run check:fix` for front end formatting and lint. Never skip a hook with `--no-verify`. AI agents are blocked from doing so.

## Pull requests

- Small: under about 400 changed lines, not counting lock files and generated files.
- Work pull requests go into `staging`. Only release pull requests go into `main`.
- The title is a Conventional Commit line. Squash merge makes it the commit on `staging`.
- The description follows `.github/pull_request_template.md`. `/commit` prints a filled draft after every commit, and `/open-pr` uses it, so nobody writes it from scratch.
- "How to check it" is not optional.
- One approval from the area owner. Two for the files named above.
- CI must be green.
- Review within one working day. A pull request waiting longer gets raised in the daily check-in.

## Definition of done

1. It meets the acceptance checks in its issue.
2. It has tests, including privacy cases if it touches business data.
3. `composer ci:check` passes.
4. It handles loading, empty, not available, hidden, and error states.
5. It shows the "Simulated data" label where business records appear.
6. Its wording follows `docs/engineering/design.md` and the phrases in `privacy-rules.md`.
7. Someone other than the author clicked through it.
8. Its status is updated in `docs/team/roadmap.md`.
9. Its accepted plan and verification are recorded through `/plan-history` in `docs/plan/` with `yy-mm-dd` dates. Resume matching active plans; archive completed plans and link them when planning improvements.

## Working with Claude Code

- **One task per session.** Start a new session for a new task, so old context does not leak in.
- **Start from a skill:** `/feature-builder C3`, `/metric-builder M10`, `/db-change add wishes table`. The skill reads the right docs first.
- **Read the plan before approving it.** Changing a plan is cheap. Changing merged code is not.
- **When Claude gets something wrong twice,** add the correction to `CLAUDE.md` or the matching file in `.claude/rules/`, in the same pull request.
- **Decisions go in `docs/engineering/decisions.md`,** not in chat.
- **One folder per laptop (user rule, 26-10-28).** All work happens in the one clone, `cagayan-business-kickoff`, switching branches there. Never create a git worktree, a second clone, or a copy of the repository for a task: several folders split one database, `.env` and branch set, and made changes hard to find and combine. Claude Code must not use worktree isolation for this project.

## Daily rhythm

- Ten-minute check-in: what I finished, what I am doing, what blocks me.
- Friday: run `/demo-check`, look at the roadmap, cut scope if needed.
- A blocker lasting more than half a day goes to the group chat right away.

## The shared database

The database runs in Docker from `compose.yaml`, the same way on every laptop, and CI uses the same image. Everyone needs it working, so:

- **Daily work never touches it.** Start it with `composer db:up`, stop it with `docker compose stop`.
- **Settings that differ per laptop go in your `.env`:** `DB_PORT` and `FORWARD_DB_PORT` for a port clash, `DB_PASSWORD`. `compose.yaml` reads them from there.
- **A real change** (a new image version, a new init script) is its own `chore/` pull request, reviewed by the backend developer, with the CI image kept in sync. The pre-commit hook reminds you. The description says what teammates run after merging, usually `composer db:up`.
- **Something broken?** Run `composer doctor` first, then tell the backend developer.
- **Never** `docker compose down -v`, `docker volume rm`, or `docker system prune`. They delete your local database.
- **Before any rebuild** (`migrate:fresh`, `composer db:reset`), run `php artisan config:show database.connections.pgsql.database` and confirm which database it is and that nothing else uses it, such as your running `composer dev`. A rebuild deletes the local accounts too; restore them with `php artisan db:seed --class=DemoAccountSeeder` (user correction, 26-10-28).
- **Before `npm ci` or `npm install`,** stop `composer dev` completely, including its Vite process. On Windows a running Vite server locks Tailwind's native file, and the install stops halfway with `EPERM`, leaving `node_modules` broken.

## Not allowed

- Committing or pushing directly to `main` or `staging`, or force pushing a shared branch.
- Opening a work pull request into `main`. Work goes into `staging`, and `main` changes only by release.
- Skipping hooks with `--no-verify`.
- Creating git worktrees, extra clones, or copies of the repository folder. Work in the one clone and switch branches.
- Changing Docker (`compose.yaml`, `docker/`) inside a feature branch. See "The shared database" above.
- Editing a migration that has been merged.
- Committing `.env` files, database dumps, or real business records.
- Adding a package without a line in `decisions.md` and the owner's approval.
- Changing a metric definition or a privacy rule without the two-reviewer pull request.
- Adding a checklist requirement without an official source and a verification date.
