<?php

namespace App\Console\Commands;

use Closure;
use Composer\Semver\Semver;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Checks that this laptop can run Cagayan Business, and prints the exact fix
 * for every failure. It must never crash: a stopped database is one of the
 * things it reports, so every check runs inside its own try block.
 */
#[Signature('app:doctor')]
#[Description('Check this machine and the database, and print the fix for anything that is wrong')]
class DoctorCommand extends Command
{
    private const MIN_NODE_MAJOR = 22;

    private const TEST_DATABASE_FALLBACK = 'cagayan_business_test';

    private int $failures = 0;

    private bool $databaseReachable = false;

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->newLine();
        $this->components->info('Cagayan Business doctor');

        $this->check('PHP version', fn () => $this->checkPhpVersion());
        $this->check('PHP extensions pdo_pgsql and pgsql', fn () => $this->checkPhpExtensions());
        $this->check('Node version', fn () => $this->checkNode());
        $this->check('Docker is running and the db service is healthy', fn () => $this->checkDocker());
        $this->check('.env exists and has an app key', fn () => $this->checkEnv());
        $this->check('Git hooks path is .githooks', fn () => $this->checkHooksPath());
        $this->check('COA parser tools', fn () => $this->checkCoaTools());
        $this->check('App can connect to the database', fn () => $this->checkDatabaseConnection());
        $this->check('PostGIS responds', fn () => $this->checkPostgis(), needsDatabase: true);
        $this->check('Test database exists', fn () => $this->checkTestDatabase(), needsDatabase: true);
        $this->check('No pending migrations', fn () => $this->checkMigrations(), needsDatabase: true);
        $this->check('Summary tables are up to date', fn () => $this->checkSummaryTables(), needsDatabase: true);

        $this->newLine();

        if ($this->failures > 0) {
            $this->components->error("{$this->failures} check(s) failed. Apply the fixes above, then run `composer doctor` again.");

            return self::FAILURE;
        }

        $this->components->success('Everything checks out.');

        return self::SUCCESS;
    }

    /**
     * Run one check and print its result.
     *
     * The closure returns null or a note on success, and throws DoctorFailure
     * with the fix on failure. Any other exception is reported as a failure
     * too, so the doctor keeps going.
     *
     * @param  Closure(): ?string  $callback
     */
    private function check(string $label, Closure $callback, bool $needsDatabase = false): void
    {
        if ($needsDatabase && ! $this->databaseReachable) {
            $this->components->twoColumnDetail($label, '<fg=yellow;options=bold>SKIP</>');
            $this->line('    <fg=gray>Skipped because the database is not reachable. Fix that first.</>');

            return;
        }

        try {
            $note = $callback();
            $this->components->twoColumnDetail($label.($note ? " <fg=gray>({$note})</>" : ''), '<fg=green;options=bold>PASS</>');
        } catch (DoctorFailure $failure) {
            $this->reportFailure($label, $failure->getMessage(), $failure->fix);
        } catch (Throwable $e) {
            $this->reportFailure($label, $e->getMessage(), 'Run `composer doctor` again. If it repeats, ask the back end owner and share this message.');
        }
    }

    private function reportFailure(string $label, string $problem, string $fix): void
    {
        $this->failures++;

        $this->components->twoColumnDetail($label, '<fg=red;options=bold>FAIL</>');
        $this->line("    <fg=red>{$problem}</>");
        $this->line("    <fg=yellow>Fix:</> {$fix}");
    }

    private function checkPhpVersion(): string
    {
        /** @var array{require?: array<string, string>} $composer */
        $composer = json_decode((string) file_get_contents(base_path('composer.json')), true);
        $constraint = $composer['require']['php'] ?? '^8.3';

        $satisfied = class_exists(Semver::class)
            ? Semver::satisfies(PHP_VERSION, $constraint)
            : version_compare(PHP_VERSION, ltrim($constraint, '^~>='), '>=');

        if (! $satisfied) {
            throw new DoctorFailure(
                'PHP '.PHP_VERSION." does not meet {$constraint} from composer.json.",
                'Install a patched PHP version matching composer.json (WAMP, Laravel Herd, or php.new), and make sure `php -v` in a new terminal shows it.',
            );
        }

        return PHP_VERSION;
    }

    private function checkPhpExtensions(): null
    {
        $missing = array_values(array_filter(['pdo_pgsql', 'pgsql'], fn (string $ext) => ! extension_loaded($ext)));

        if ($missing !== []) {
            $ini = php_ini_loaded_file() ?: 'the php.ini shown by `php --ini`';

            throw new DoctorFailure(
                'Not loaded in the command line PHP: '.implode(', ', $missing).'.',
                "Open {$ini}, remove the `;` in front of `extension=pdo_pgsql` and `extension=pgsql`, save, and open a new terminal. The command line can use a different php.ini from the web server.",
            );
        }

        return null;
    }

    private function checkNode(): string
    {
        $result = Process::timeout(15)->run(['node', '--version']);

        if (! $result->successful()) {
            throw new DoctorFailure(
                'Node was not found.',
                'Install Node '.self::MIN_NODE_MAJOR.' from https://nodejs.org, then open a new terminal and run `node --version`.',
            );
        }

        $version = trim($result->output());
        $major = (int) ltrim(strtok($version, '.') ?: '', 'v');

        if ($major < self::MIN_NODE_MAJOR) {
            throw new DoctorFailure(
                "Node {$version} is older than ".self::MIN_NODE_MAJOR.'.',
                'Install Node '.self::MIN_NODE_MAJOR.' from https://nodejs.org, then open a new terminal.',
            );
        }

        return $version;
    }

    private function checkDocker(): string
    {
        $info = Process::timeout(20)->run(['docker', 'info', '--format', '{{.ServerVersion}}']);

        if (! $info->successful()) {
            throw new DoctorFailure(
                'Docker is not reachable.',
                'Start Docker Desktop and wait until it says "Engine running", then run `composer db:up`.',
            );
        }

        $ps = Process::path(base_path())->timeout(20)->run(['docker', 'compose', 'ps', '--all', '--format', 'json', 'db']);
        $line = trim(strtok($ps->output(), "\n") ?: '');

        /** @var array{State?: string, Health?: string}|null $service */
        $service = $line === '' ? null : json_decode($line, true);

        if (! $ps->successful() || $service === null || ($service['State'] ?? '') !== 'running') {
            throw new DoctorFailure(
                'The db service is not running.',
                'Run `composer db:up`.',
            );
        }

        if (($service['Health'] ?? '') !== 'healthy') {
            throw new DoctorFailure(
                'The db service is running but not healthy yet ('.($service['Health'] ?? 'unknown').').',
                'Wait a few seconds and run `composer doctor` again. If it stays unhealthy, run `docker compose logs db`.',
            );
        }

        return 'Docker '.trim($info->output());
    }

    private function checkEnv(): null
    {
        if (! file_exists(base_path('.env'))) {
            throw new DoctorFailure(
                'There is no .env file.',
                'Run `composer setup`, which copies .env.example to .env and generates the app key.',
            );
        }

        if (blank(config('app.key'))) {
            throw new DoctorFailure(
                'APP_KEY in .env is empty.',
                'Run `php artisan key:generate`.',
            );
        }

        return null;
    }

    private function checkHooksPath(): null
    {
        $result = Process::path(base_path())->timeout(10)->run(['git', 'config', '--get', 'core.hooksPath']);

        if (trim($result->output()) !== '.githooks') {
            throw new DoctorFailure(
                'Git is not using the hooks in .githooks, so the commit message hook does not run.',
                'Run `git config core.hooksPath .githooks`.',
            );
        }

        return null;
    }

    /** Optional for a clean clone; Capitol report uploads need it on the queue worker (D34, D42). */
    private function checkCoaTools(): string
    {
        return is_file((string) config('cagayan.finances.uploads.python'))
            ? 'ready'
            : 'not set up, so report uploads cannot be read; `composer data:finances:test` creates it';
    }

    private function checkDatabaseConnection(): string
    {
        $connection = config('database.default');
        $database = config("database.connections.{$connection}.database");
        $host = config("database.connections.{$connection}.host");
        $port = config("database.connections.{$connection}.port");

        try {
            DB::connection()->getPdo();
        } catch (Throwable $e) {
            throw new DoctorFailure(
                "Cannot connect to {$database} on {$host}:{$port}. ".$this->firstLine($e->getMessage()),
                'Run `composer db:up`. If another program uses the port, set FORWARD_DB_PORT and DB_PORT in .env to the same free port, then run `composer db:up` again.',
            );
        }

        $this->databaseReachable = true;

        return "{$database} on {$host}:{$port}";
    }

    private function checkPostgis(): string
    {
        try {
            $version = DB::scalar('SELECT PostGIS_Version()');
        } catch (Throwable) {
            throw new DoctorFailure(
                'PostGIS is not enabled in the development database.',
                'Run `php artisan migrate`. The first migration enables PostGIS.',
            );
        }

        return 'PostGIS '.strtok((string) $version, ' ');
    }

    private function checkTestDatabase(): string
    {
        $name = $this->testDatabaseName();

        $exists = DB::scalar('SELECT 1 FROM pg_database WHERE datname = ?', [$name]);

        if (! $exists) {
            throw new DoctorFailure(
                "The test database {$name} does not exist.",
                'It is created the first time the database volume starts. Run `docker compose down -v`, then `composer setup`. This deletes local data, which `composer db:reset` restores.',
            );
        }

        return $name;
    }

    private function checkMigrations(): null
    {
        /** @var Migrator $migrator */
        $migrator = app('migrator');

        if (! $migrator->repositoryExists()) {
            throw new DoctorFailure(
                'The database has never been migrated.',
                'Run `php artisan migrate`.',
            );
        }

        $files = $migrator->getMigrationFiles([database_path('migrations')]);
        $pending = array_diff(array_keys($files), $migrator->getRepository()->getRan());

        if ($pending !== []) {
            throw new DoctorFailure(
                count($pending).' pending migration(s), for example '.reset($pending).'.',
                'Run `php artisan migrate`, or `composer db:reset` to rebuild the demo data from scratch.',
            );
        }

        return null;
    }

    /**
     * The summary tables are rebuilt by `metrics:refresh`, which stamps the
     * time it finished. They are stale when any business record is newer.
     */
    private function checkSummaryTables(): ?string
    {
        if (! Schema::hasTable('businesses') || ! Schema::hasTable('business_years')) {
            return 'no business tables yet';
        }

        $newestRecord = collect([
            DB::table('businesses')->max('updated_at'),
            DB::table('business_years')->max('updated_at'),
        ])->filter()->max();

        if ($newestRecord === null) {
            return 'no business records yet';
        }

        $refreshedAt = RefreshMetricsCommand::lastRefreshedAt();

        if ($refreshedAt === null || $refreshedAt->lt($newestRecord)) {
            throw new DoctorFailure(
                'Business records changed after the last metrics refresh, so screens show old numbers.',
                'Run `php artisan metrics:refresh`.',
            );
        }

        return null;
    }

    /**
     * Read the test database name from phpunit.xml, so the doctor checks the
     * same database the test run uses.
     */
    private function testDatabaseName(): string
    {
        $xml = @simplexml_load_file(base_path('phpunit.xml'));

        if ($xml !== false) {
            foreach ($xml->xpath('//php/env[@name="DB_DATABASE"]') ?: [] as $env) {
                return (string) $env['value'];
            }
        }

        return self::TEST_DATABASE_FALLBACK;
    }

    private function firstLine(string $message): string
    {
        return trim(strtok($message, "\n") ?: $message);
    }
}
