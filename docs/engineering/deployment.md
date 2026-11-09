# Deployment

The online version runs on one server and is deployed by `.github/workflows/deploy.yml`. It is switched off until the server exists (open decision O5).

The video and the booth do not depend on any of this. Both run from a laptop with `composer setup` and `composer dev`.

Only `main`, the production branch, is deployed. `staging` is for development and has no server yet.

## How a deploy works

1. Someone pushes a version tag, such as `v0.3.0`, or runs the Deploy workflow by hand from the Actions tab with a tag name.
2. If the repository variable `DEPLOY_ENABLED` is not `true`, every job skips and nothing happens.
3. The `checks` job runs the full CI workflow (`ci.yml`) on the tagged commit: PostGIS, migrations, build, and `composer ci:check`. If it fails, nothing is deployed.
4. The `deploy` job builds the front end assets on the GitHub runner, then over SSH:
   1. puts the site in maintenance mode (`php artisan down`)
   2. fetches tags and checks out the tag in `DEPLOY_PATH`
   3. installs production PHP packages (`composer install --no-dev`)
   4. replaces `public/build` with the assets built on the runner
   5. runs migrations, rebuilds the config, route, and view caches, restarts the queue workers (`php artisan queue:restart`), and takes the site out of maintenance mode (`php artisan up`)

Only one deploy runs at a time. A second one waits for the first.

## Secrets and variables

Set these under the repository's **Settings > Secrets and variables > Actions**.

| Name | Kind | What it holds |
| :- | :- | :- |
| `DEPLOY_ENABLED` | Variable | `true` to turn deploys on. Anything else, or missing, keeps them off. |
| `DEPLOY_HOST` | Secret | The server's host name or IP address. |
| `DEPLOY_USER` | Secret | The SSH user that owns the app folder. Not `root`. |
| `DEPLOY_SSH_KEY` | Secret | The private key for that user, the whole file including the `BEGIN` and `END` lines. Use a key made only for deploys. |
| `DEPLOY_PATH` | Secret | The absolute path of the app on the server, for example `/var/www/cagayan-business`. |
| `DEPLOY_KNOWN_HOSTS` | Secret, optional | The server's line from `ssh-keyscan <host>`. Pins the host key. Without it the workflow trusts the key on first use and prints a warning. |

The deploy job uses a GitHub environment named `production`. GitHub creates it on the first run. You can add a required reviewer to it under **Settings > Environments** so a person approves each deploy.

`ci.yml` uses no secrets. Its database password `secret` is a throwaway value for the CI container.

## What the server needs

- Ubuntu 24.04 or similar, with a non-root deploy user
- PHP 8.3 with `pdo_pgsql`, `pgsql`, `mbstring`, `xml`, `curl`, `zip`, `bcmath`, `intl`, and PHP-FPM
- Composer 2
- Nginx (or Apache) pointing the site's document root at `DEPLOY_PATH/public`
- PostgreSQL 17 with PostGIS 3.5, on the same server or managed. Run `CREATE EXTENSION postgis;` once as a superuser if the app user cannot.
- Git, with read access to this repository (a read-only deploy key on GitHub)
- A queue worker kept running by Supervisor or systemd: `php artisan queue:work --tries=1`
- For Capitol report uploads (D42): Python 3 with the parser's venv, `python -m venv tools/coa/.venv` then `tools/coa/.venv/bin/pip install -r tools/coa/requirements.txt`, or set `COA_PYTHON`. `composer doctor` reports whether it is ready.
- HTTPS, for example with Certbot

Node is not needed on the server. Assets are built on the GitHub runner.

## First-time server setup

Do this once, by hand, as the deploy user:

1. Clone the repository into `DEPLOY_PATH`.
2. Create `DEPLOY_PATH/.env` from `.env.example` with production values:
   - `APP_ENV=production`, `APP_DEBUG=false` (privacy-rules.md, P14: debug mode is off for any demo others can reach), `APP_URL=https://...`
   - the real database host, name, user, and password
   - a strong password for each `DEMO_*_PASSWORD`, or none, and create administrators with an Artisan command instead. Quick sign in is local only and answers 404 in production (decisions.md, D33)
   - `CAGAYAN_SIMULATED=false` (the default). Set true only for a presentation that needs the simulated business records
   - `CAGAYAN_ADMIN_LOGIN_PATH` set to a long random value (`php -r "echo bin2hex(random_bytes(12));"`). Production refuses to boot with the example value (decisions.md, D20). Share it with administrators directly, never in the repository or a public channel.
   - `SESSION_SECURE_COOKIE=true` and `SESSION_ENCRYPT=true` once the site runs on HTTPS
3. Run `composer install --no-dev`, `php artisan key:generate`, `php artisan migrate --force`, `php artisan db:seed --force`, and `php artisan metrics:refresh`.
4. Make `storage/` and `bootstrap/cache/` writable by the PHP-FPM user.
5. Copy a built `public/build` folder once, or let the first deploy do it.

The real `.env` lives only on the server. It never goes into the repository or GitHub.

## Turn deploys on

1. Finish the first-time server setup above.
2. Add the four required secrets, and `DEPLOY_KNOWN_HOSTS` if you can.
3. Add the variable `DEPLOY_ENABLED` with the value `true`.
4. Release `staging` into `main` (`docs/team/workflow.md`, "Releases"), then tag the release on `main` and push the tag:

   ```
   git switch main
   git pull
   git tag v0.1.0
   git push origin v0.1.0
   ```

5. Watch the Deploy workflow in the Actions tab.

To turn deploys off again, set `DEPLOY_ENABLED` to `false` or delete it.

## Roll back to the previous tag

Run the Deploy workflow by hand (Actions > Deploy > Run workflow) with the previous tag, for example `v0.2.0`. It runs the checks on that tag and deploys it.

Migrations do not roll back on their own. If the bad release added a migration that the previous code cannot work with, roll it back on the server first:

```
cd /var/www/cagayan-business
php artisan migrate:rollback --step=1
```

Then deploy the previous tag. Check what the migration's `down()` does before rolling back: it may drop data.

If a deploy fails partway, the site can be left in maintenance mode. Fix the cause and deploy again, or run `php artisan up` on the server to bring the current code back.
