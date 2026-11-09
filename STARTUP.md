# Startup

Everything a new teammate sets up, in order. Do the steps top to bottom. Each one ends with a check, so you know it worked before moving on. Plan for about an hour, most of it downloads.

Written for Windows and PowerShell. You do not need to know Docker or PostgreSQL.

## 1. Accounts

- [ ] A GitHub account, added to `drSabine/cagayan-central-businesses` by the repository owner
- [ ] Claude Code installed and signed in (https://claude.com/claude-code), if you will use the AI agent

## 2. Install the tools

Install each one, then open a **new** PowerShell window and run its check.

| Tool | Get it | Check |
| :- | :- | :- |
| Git | https://git-scm.com/download/win | `git --version` |
| PHP 8.3.36 or a newer 8.3 patch | WAMP, Laravel Herd, or https://php.new | `php -v` |
| Composer | https://getcomposer.org/download | `composer --version` |
| Node 22 | https://nodejs.org (LTS installer) | `node --version` |
| Docker Desktop | https://www.docker.com/products/docker-desktop | `docker --version` |
| GitHub CLI | `winget install GitHub.cli`, then `gh auth login` | `gh --version` |

Everyone uses the same PHP minor version (8.3) as CI.

PHP 8.3.36 includes the October 2026 critical security fixes. Update the PHP used by the web server as well as the command line, set `session.upload_progress.enabled=0` in both configurations, and restart PHP after updating. Keep installing subsequent security patches. Official advisory: https://www.php.net/#security-advisory-critical-remote-code-execution-vulnerabilities-update-now.

## 3. Turn on two PHP extensions

The app talks to PostgreSQL through `pdo_pgsql` and `pgsql`.

1. Run `php --ini`. Note the "Loaded Configuration File", for example `C:\wamp64\bin\php\php8.3.36\php.ini`.
2. Open that file in a text editor. Find these two lines and delete the `;` at the start of each:

   ```
   ;extension=pdo_pgsql
   ;extension=pgsql
   ```

3. Save, open a new PowerShell window, and run `php -m`.

**Check:** both `pdo_pgsql` and `pgsql` appear in the list.

With WAMP, the command line reads `php.ini` and the web server reads `phpForApache.ini`, so this does not affect your other WAMP projects.

## 4. Start Docker Desktop

Open Docker Desktop from the Start menu and wait until it says "Engine running". It must be running whenever you work on this project. It runs only the database.

The whole team shares this Docker setup, so you only start and stop it (`composer db:up`, `docker compose stop`). Any setting that differs on your laptop, such as the port, goes in your own `.env`, not in `compose.yaml`. See `docs/team/workflow.md`, "The shared database".

**Check:** `docker info` prints details instead of an error.

## 5. Tell Git who you are

```
git config --global user.name "Your Name"
git config --global user.email "you@example.com"
```

Use the email of your GitHub account.

## 6. Clone and set up

```
git clone https://github.com/drSabine/cagayan-central-businesses.git
cd cagayan-central-businesses
composer setup
```

`composer setup` installs PHP and Node packages, creates `.env`, starts the database, creates the tables, loads the demo data, builds the front end, turns on the Git hooks, and ends with a health check. The first run downloads the database image and takes a few minutes. It is safe to run again.

**Check:** the last part prints every doctor check as PASS.

## 7. Run the app

```
composer dev
```

Open http://localhost:8000/staff-sign-in (the path is `CAGAYAN_ADMIN_LOGIN_PATH` in your `.env`). The page shows a quick sign in button for each demo role: Super Admin, Admin, and Staff. The buttons exist only while `APP_ENV=local`.

The accounts come from `DEMO_SUPER_ADMIN_*`, `DEMO_ADMIN_*`, and `DEMO_STAFF_*` in your `.env`. To change a role's login, edit those lines and run `php artisan db:seed --class=DemoAccountSeeder`. It adds missing accounts and leaves existing ones alone. Press `Ctrl+C` in the terminal to stop.

**Check:** you see the dashboard after logging in.

## 8. Check the Git hooks

```
git config core.hooksPath
```

**Check:** it prints `.githooks`. If not, run `git config core.hooksPath .githooks`.

From now on, every commit runs the fast checks and every push runs the full checks. See `docs/team/workflow.md`, "Checks that run on their own".

## 9. Editor

Any editor works. With VS Code, install:

- PHP Intelephense
- Tailwind CSS IntelliSense
- Oxc (the front end linter and formatter used by `npm run check`)
- EditorConfig for VS Code

Turn on "Format on save" only for languages the project formats: PHP with Pint (`composer lint`), TypeScript with `npm run check:fix`.

## 10. Claude Code

1. Open a terminal in the project folder and run `claude`.
2. Approve the `laravel-boost` MCP server when asked. It gives Claude the exact docs for the installed Laravel versions.
3. Claude reads `CLAUDE.md` on its own. Start each task from a skill, for example `/feature-builder C3`. The list is in `CLAUDE.md`, "Skills".

## 11. Your first task

1. Read `CLAUDE.md`, then `docs/team/workflow.md`.
2. Find your role in `docs/team/workflow.md`, "Roles" (frontend developer, backend developer, or data and content), and read the docs it lists.
3. Pick your next item from `docs/team/roadmap.md`.
4. Make a branch inside `staging` (`git switch staging`, `git pull --ff-only`, `git switch -c feat/short-description`), build, `/commit`, `/open-pr`. The pull request goes into `staging`.

## The team's first tasks

In this order. Each is one session and one pull request.

1. Check the 49 barangay codes and names in `data/boundaries/` against PSA's current listing. Record the result under O1 in `decisions.md`.
2. `/db-change core schema: municipalities, barangays, business types, businesses, business years, wishes, record view logs, user role` (F1)
3. Load the boundary files with a seeder, plus urban or rural class and 2024 population from PSA (F2)
4. Build the privacy guard and its tests from `privacy-rules.md` (F5)
5. Build the demo seeder to the requirements in `database.md` (F3)
6. `/metric-builder M1`, then M2, M3, M4, which also creates `stats_area_year` (F4)
7. Build the shared map component and the two layouts with the simulated label (F6, F7)
8. `/feature-builder C1`

## When something breaks

Run `composer doctor` first. It checks your machine and the database and prints the fix for anything wrong.

**Port 5432 is already in use.** Another PostgreSQL is running. Pick a free port, for example 5433, set both lines in `.env`, then run `composer db:up`:

```
DB_PORT=5433
FORWARD_DB_PORT=5433
```

**`could not find driver`.** The extensions are on for the web server but not the command line. Redo step 3 with the file `php --ini` shows.

**Something about Docker looks wrong.** Run `composer doctor` and send its output to the backend developer. Fix laptop-only problems in your `.env`, not in `compose.yaml`.

**Docker is not running.** You see `failed to connect to the docker API` or `Cannot connect to the database`. Start Docker Desktop, wait for "Engine running", then `composer db:up`.

**PHP is too old.** `php -v` shows 8.2 or lower. Install 8.3 and make sure its folder comes first in your Windows `PATH`.

**A commit or push is refused.** Read the message. It says which check failed and the command that fixes it. The usual fixes are `composer lint` and `npm run check:fix`. A push also needs the database running for the tests (`composer db:up`).

**The map is empty.** Run `composer db:reset`. It reloads everything and refreshes the metrics.

**PHPStan runs out of memory.** Use `composer types:check`, which already passes `--memory-limit=1G`.

**Still stuck?** Copy the output of `composer doctor` into the group chat.

## For the repository owner

Do these once, after the kickoff pull request is merged into `main`. Branch protection on a private repository needs GitHub Pro, Team, or the GitHub Student Developer Pack.

1. Create `staging` from `main` on GitHub. This uses the API, so nothing is pushed to a protected branch:

   ```
   git fetch origin
   gh api --method POST repos/drSabine/cagayan-central-businesses/git/refs -f ref=refs/heads/staging -f sha=$(git rev-parse origin/main)
   ```

2. Make `staging` the default branch, so clones and new pull requests start there:

   ```
   gh repo edit drSabine/cagayan-central-businesses --default-branch staging
   ```

3. Allow squash merge (work into `staging`) and merge commits (releases into `main`), and delete work branches after merging. GitHub never auto-deletes the default branch or a protected one, so `staging` and `main` stay.

   ```
   gh repo edit drSabine/cagayan-central-businesses --enable-squash-merge --enable-merge-commit --enable-rebase-merge=false --delete-branch-on-merge
   ```

4. Protect both branches (pull request with one approval, both CI jobs green, no direct or force pushes):

   ```
   gh api --method PUT repos/drSabine/cagayan-central-businesses/branches/main/protection --input .github/branch-protection.json
   gh api --method PUT repos/drSabine/cagayan-central-businesses/branches/staging/protection --input .github/branch-protection.json
   ```

5. Check them:

   ```
   gh api repos/drSabine/cagayan-central-businesses/branches/staging/protection
   gh api repos/drSabine/cagayan-central-businesses/branches/main/protection
   ```

6. Put each area owner's GitHub username in `.github/CODEOWNERS` and their names in `docs/team/workflow.md`.
