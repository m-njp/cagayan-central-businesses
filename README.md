# Cagayan Business

A map of Cagayan's registered businesses, barangay by barangay, for the Provincial Government of Cagayan.
Capitol staff log in to see business counts, trends, and area sales totals. The public explores the map, gets a start-a-business checklist, and tells the Capitol what businesses their town lacks.
The MVP covers Tuguegarao City's 49 barangays. Built for the PGC Digital Innovation Challenge 2026.

> **Data.** Boundaries, population and COA government finances are real. There are no simulated business records unless `CAGAYAN_SIMULATED` is true for a presentation, and then every screen that shows them says so. The Tuguegarao City wishes on Economy are sample data and are labeled.

## Quick start

New to the project? Follow [STARTUP.md](STARTUP.md) from the top. It covers every tool, the PHP extensions, Docker, and the first run.

Already set up:

```
composer setup
composer dev
```

Open http://localhost:8000/staff-sign-in and press a demo role button (Super Admin, Admin, or Staff). The buttons exist only while `APP_ENV=local`; accounts come from the `DEMO_*` lines in your `.env` (decisions.md, D33).

## Every day

```
git switch staging
git pull --ff-only
git switch -c feat/home-animations
composer db:up
composer dev
```

`main` is production and `staging` is development. Every task is a branch inside `staging` (`feat/...`, `bug/...`) that merges back into `staging`. Commit with `/commit` and open the pull request with `/open-pr` in Claude Code. The Git hooks check every commit and push. Full rules: [docs/team/workflow.md](docs/team/workflow.md).

## Commands

| Command | What it does |
| :- | :- |
| `composer setup` | First-time setup. Safe to rerun. |
| `composer dev` | Runs the app at http://localhost:8000 with live reload. |
| `composer ci:check` | Everything CI checks: front end format and lint, TypeScript, PHP format, static analysis, tests. |
| `php artisan test --compact <file>` | Runs one test file. |
| `composer lint` | Fixes PHP formatting. |
| `npm run check:fix` | Fixes front end formatting and lint. |
| `composer db:up` | Starts the database. |
| `composer db:reset` | Deletes local data and rebuilds the demo dataset. |
| `php artisan metrics:refresh` | Rebuilds the summary tables. |
| `composer doctor` | Checks your machine and the database, with a fix for anything wrong. |
| `docker compose stop` | Stops the database. Your data stays. |

## Where things are

```
app/                    PHP: controllers, queries, actions, services, data objects, models
config/cagayan.php      Every product threshold
database/               Migrations, factories, seeders, metric SQL
data/boundaries/        Barangay and town shapes, with their sources
compose.yaml, docker/   The database setup, shared by the whole team and CI
resources/js/           React: pages, layouts, components, hooks
resources/css/app.css   Tailwind and the design tokens
routes/                 URLs, split into public and capitol
tests/                  Pest tests, run on PostGIS
docs/                   Product, engineering, and team documents
.claude/                Claude Code rules, skills, and the privacy reviewer
.githooks/              Checks that run on commit and push
.github/                CI, deploy, and pull request templates
```

## Looking at the database

| Setting | Value |
| :- | :- |
| Host | `127.0.0.1` |
| Port | `5432` (or `DB_PORT` in `.env`) |
| Database | `cagayan_business` (tests use `cagayan_business_test`) |
| User | `cagayan` |
| Password | `secret` |

Use DBeaver or pgAdmin for tables, and QGIS (PostgreSQL connection, then drag the `barangays` table onto the map) to see the shapes.

## Documents

Start here: [STARTUP.md](STARTUP.md), [CLAUDE.md](CLAUDE.md), [Team workflow](docs/team/workflow.md).

Product:

- [Product description and MVP scope](docs/product/mvp-scope.md)
- [Challenge guidelines](docs/product/challenge-guidelines.md)

Engineering:

- [Architecture](docs/engineering/architecture.md): back end structure, performance, rate limiting
- [Front end](docs/engineering/frontend.md): pages, components, data flow
- [Design](docs/engineering/design.md): UI and writing rules, tokens
- [Database](docs/engineering/database.md): schema rules and every table
- [Metrics](docs/engineering/metrics.md): every number on screen
- [Privacy rules](docs/engineering/privacy-rules.md)
- [Testing](docs/engineering/testing.md)
- [Decisions](docs/engineering/decisions.md)
- [Deployment](docs/engineering/deployment.md)
- [Progressive Web App](docs/engineering/pwa.md)

Team:

- [Workflow](docs/team/workflow.md): branches, commits, pull requests, hooks
- [Roadmap](docs/team/roadmap.md)

Data:

- [Boundary files](data/boundaries/README.md)
