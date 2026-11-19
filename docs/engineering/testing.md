# Testing

How to write tests that are quick to run and that people trust. Read this before writing or changing a test.

## Choose Checks From the Change

Owner correction, "26-11-14": **targeted tests are the default. Do not rerun every test after each change.** This section is the canonical policy for `AGENTS.md`, `CLAUDE.md` and the testing rules.

1. Trace the changed behavior and its callers. Pick the smallest test, file or related group that covers that behavior and its failure cases.
2. Run that selection once. After a failure, fix it and rerun the failing test or affected group. Broaden only when the failure exposes another dependency or the implementation changes a shared contract.
3. Keep useful results from earlier in the same session when their code, dependencies, configuration and fixtures remain unchanged. Record which results are carried forward. A documentation change alone does not invalidate application tests.
4. Do not rerun the full suite after an isolated assertion, fixture, formatting or documentation fix. Report the last full-run result and the targeted follow-up separately; never describe that combination as a fresh green `composer ci:check` run.
5. Run full verification once at an integration checkpoint for broad changes, and let the existing pre-push hook and CI enforce the final full gate. Do not bypass hooks. Rerun that full gate when application changes invalidate it or when required for the final integration, not for each intermediate edit.

Before a long run, state the scope and why a smaller selection is insufficient. Run one test command at a time. Parallel workers inside that one command are allowed; separate Pest commands share worker databases and can block each other.

### Selection Guide

| Change | Default Verification | Broaden When |
| :- | :- | :- |
| Documentation or instructions only | Review links, scope and `git diff --check`; no application test run | A documented executable command or application behavior changed |
| One PHP behavior | Relevant named Pest test, then its file or direct consumers if needed; Pint for edited PHP | Shared service, authorization, contract or query behavior changed |
| Government finance import fixture or assertion | Named cases in `FinanceImportTest`; affected evidence/review cases | Import semantics, ledger CSVs, sign-off rules or schema changed |
| Source library and downloads | `FinanceSourceTest`; relevant source-list formatter tests | Public archive construction, file validation or shared citation contract changed |
| Finance review diagnostics | `FinanceReviewEvidenceTest` and the directly affected review cases | Review/sign-off authorization or publication rules changed |
| Pure frontend helper | Its existing `composer test:*` command, such as `test:analytics` or `test:sources` | Shared serialization, navigation or page behavior changed |
| React, CSS or Vite | Relevant frontend tests, plus mandatory `npm run check`, `npm run types:check`, `npm run build` | Shared layout, routing, privacy or backend prop contracts changed |
| Python COA intake or parser module | Its pytest file or named case through the Composer command below | Common readers, number matching, cross-report checks or parser output changed |
| Migration, shared test infrastructure, dependencies or broad feature integration | Affected groups first, then full CI at the integration checkpoint | Follow-up application edits invalidate that checkpoint |

Use one path argument with `php artisan test`. Use `--filter` for one behavior or a related selection. Examples, run individually:

```powershell
php artisan test --compact tests/Feature/Finances/FinanceImportTest.php --filter="loads parsed figures as pending"
php artisan test --parallel --filter=FinanceReviewEvidenceTest
php artisan test --parallel --filter=FinanceSourceTest
composer test:analytics
composer test:sources
composer data:finances:test -- tests/test_intake.py -n 0
```

`-n 0` avoids starting every pytest worker for a small selection. A full parser run retains its parallel default. `composer test:fast` runs **all Pest tests**; its name does not make it a targeted command. `composer ci:check` is the aggregate integration gate and does not replace selecting tests while working.

### Expensive Coverage and Timing Reports

Keep full committed-data imports and full-seeder tests. Select their named cases only when the changed behavior needs them; do not delete or weaken them to improve timings. A whole `FinanceImportTest` file includes the committed-data import. On "26-11-14", that one case took 396.652 seconds in a parallel profiling run; two full-seeder cases added 519.564 summed seconds. See the [measured report](executive-summary-verification.md).

Report the command, selected scope, result, wall time and whether it is a new run or carried-forward evidence. For a failed full run followed by a targeted pass, state both. Never infer serial performance from summed parallel test durations or claim an agent speed saving without a comparable timing record.

Smoke-test profiling hooks on one small case and inspect the emitted data before starting an expensive benchmark. Laravel query events omit direct PDO batches and transaction-control statements; label that limit and time the affected phase separately. Read actual database constraints before adding a defensive fixture or branch. The "26-11-14" importer follow-up retained all three full-data tests and improved their matched two-worker run from 456.851s to 379.458s; see [the measured optimization](executive-summary-verification.md#import-lookup-optimization).

Pest 4 on PostgreSQL with PostGIS. Run one file with `php artisan test --compact tests/Feature/PostgisTest.php`, one test with `--filter="hides sales"`, everything with `php artisan test --compact`.

`composer test:frontend` runs every Node frontend suite in one command. The targeted `test:sources`, `test:analytics`, `test:maps`, `test:categories`, `test:trend`, `test:geo`, and `test:businesses` commands are also available. `composer ci:check` includes this suite.

## What a good test here looks like

- **Tests behavior, not code.** It calls a route or a class the way real code does, and checks the result: status, redirect, Inertia props, database rows. It survives a refactor that keeps the behavior.
- **Fast.** No demo seeder, no sleeping, no network. Build the few rows the test needs with factories.
- **Reliable.** Same result every run, in any order, on any laptop.
- **Says what broke.** The name reads as a sentence. One behavior per test.

```php
it('hides sales for a group below the minimum size', function () {
    $min = config('cagayan.privacy.small_group_min');

    $figure = app(PrivacyGuard::class)->figure(total: '900000.00', count: $min - 1, maxSingle: '300000.00');

    expect($figure->state)->toBe('hidden')
        ->and($figure->reason)->toBe('small_group');
});
```

## Where tests go

```
tests/
  Feature/
    Capitol/     Capitol pages and actions, one file per feature (BarangayProfileTest.php)
    Public/      public pages and the wish form
    Metrics/     one file per metric (NetGrowthTest.php)
    Privacy/     cross-cutting privacy rules (sentinel scans, guard behavior)
    Seeder/      the seeder requirement checks
    Auth/        login, logout, and the removed starter kit routes
  Unit/          pure PHP with no database: the privacy guard, sales bands, enums
  Arch/          architecture rules (arch() tests)
```

Create a folder with its first real test. Name files after the behavior's subject: `WishSubmissionTest.php`, not `WishControllerTest.php`.

Style: Pest functions (`it()`, `test()`, `expect()`). Create new files with `php artisan make:test --pest NameTest`.

## Speed

Aim for focused working checks under a minute. Full integration currently takes several minutes because it includes committed-data imports and full-seeder coverage; see the [measured report](executive-summary-verification.md). Keep that coverage and select it at the appropriate checkpoint.

- **Unit tests for pure logic.** The privacy guard, band selection, and enum helpers need no database and run in milliseconds. Put them in `tests/Unit/` and do not boot the database there.
- **Transactions, not migrations.** `RefreshDatabase` migrates once, then wraps each test in a transaction that rolls back.
- **Small fixtures.** Two or three barangays and a handful of businesses. Never the demo seeder, except in `tests/Feature/Seeder/`.
- **Windows fixture paths.** Give each generated import fixture a fresh directory. When testing a directory in place of a file, move the file aside before creating the directory. Immediate delete/recreate can fail with Permission denied while Windows still holds the deleted path open (observed "26-11-14").
- **Cheap passwords.** `BCRYPT_ROUNDS=4` in `phpunit.xml` (already set).
- **Fakes for slow things.** `Notification::fake()`, `Mail::fake()`, `Queue::fake()`, `Storage::fake()`.
- **Run narrow while working**, full before pushing. The pre-push hook runs the full suite.
- **Parallel by default (26-11-14, D49).** `composer test` and `composer test:fast` run Pest with `--parallel`; each worker gets its own `cagayan_business_test_test_N` database. `composer data:finances:test` runs the parser tests on every core through pytest-xdist (`-- -n 0` for one process). `composer test:frontend` runs every Node suite in one command. Never start two test commands at once: they share the same test databases and block each other.
- **Find slow tests** with `php artisan test --profile`.

## Reliability

| Cause of flaky tests | Do this |
| :- | :- |
| The clock | Freeze it: `$this->travelTo(now()->setDate(2026, 10, 1))` |
| Random data that matters | Set it explicitly in the factory call. Faker only for values the test does not check. |
| Network calls | `Http::preventStrayRequests()` and `Http::fake()` |
| Test order | Each test builds its own data. Never rely on another test's rows. |
| Shared config | Change config inside the test with `config([...])`. It resets after each test. |
| Thresholds | Build fixtures from `config('cagayan....')`, never a hardcoded `5`, so a changed threshold cannot leave a test passing for the wrong reason. |

## Assertions

Assert on structure, not on page text.

```php
$this->actingAs($admin)
    ->get(route('capitol.barangays.show', $barangay))
    ->assertOk()
    ->assertInertia(fn (Assert $page) => $page
        ->component('capitol/barangays/show')
        ->where('profile.sales.state', 'hidden')
        ->where('profile.sales.reason', 'small_group')
        ->missing('profile.sales.value'));
```

- Status and redirect: `assertOk()`, `assertRedirect(route('login'))`, `assertForbidden()`, `assertTooManyRequests()`.
- Props: `assertInertia()` with `where()`, `has()`, `missing()`.
- Database: `assertDatabaseHas()`, `assertDatabaseCount()`.
- Text only when the text itself is the rule, for example the "Simulated data" label.
- Hidden is never zero: assert the state and reason, and that no value is present.

## Patterns this project needs

### Route guards in one test

One dataset test covers every protected route, including routes added later.

```php
dataset('capitol routes', fn () => collect(Route::getRoutes())
    ->filter(fn ($route) => str_starts_with($route->getName() ?? '', 'capitol.'))
    ->map(fn ($route) => [$route])
    ->all());

it('sends guests to login', function ($route) {
    $this->call($route->methods()[0], url($route->uri()))->assertRedirect(route('login'));
})->with('capitol routes');
```

Routes with parameters need a record to bind. Build one in a `beforeEach`.

### CSRF in Laravel 13

Laravel 13's CSRF middleware (`PreventRequestForgery`) switches itself off while tests run, so a request without a token does not return 419 in a test. Check instead that every state-changing route is in the `web` middleware group, which applies it.

### Privacy tests assert absence

Seed a sentinel value (a distinctive sales amount like `987654321.12`, an owner name like `Sentinel Owner`), call every relevant route, and assert the sentinel appears nowhere in the response.

```php
expect($response->getContent())->not->toContain('987654321.12');
```

### Metric tests use hand-built fixtures

A tiny fixture, the hand calculation in a comment above the assertion, `$this->artisan('metrics:refresh')->assertSuccessful()`, then assert the stored value. Add one test for "not available", and one for "hidden" when sales are involved. Never test a metric against the demo seeder.

### Rate limit tests check behavior

Send the allowed number of requests, then one more, and expect 429. Do not rebuild Laravel's internal throttle key in the test.

```php
foreach (range(1, 10) as $i) {
    $this->post(route('public.wishes.store'), $payload());
}
$this->post(route('public.wishes.store'), $payload())->assertTooManyRequests();
```

### Mass assignment

For every store and update route, post a field the user must not control (`role`, `is_simulated`, `user_id`) and assert it did not change.

### Architecture rules

```php
arch('controllers do not query the database directly')
    ->expect('App\Http\Controllers')
    ->not->toUse('Illuminate\Support\Facades\DB');

arch('no debugging leftovers')
    ->expect(['dd', 'dump', 'ray', 'var_dump'])
    ->not->toBeUsed();
```

## Proving a security test works

Before trusting a new security test, break the protection on purpose (remove the middleware or the check), run the test, and watch it fail. Then restore the protection. A test that passes either way proves nothing.

## What not to test

- Framework behavior (that `required` rejects empty input, that Eloquent saves).
- The same thing twice at two layers.

## Never

- Delete or weaken a test to make a change pass. If a test looks wrong, stop and ask.
- Use SQLite.
- Leave `->only()` or `->skip()` in committed tests.

## Audit backlog

From the October 3, 2026 review. To be done later, in this order:

1. Route guard dataset test: state-changing routes sit in the `web` group. Guests already get a 404 on every Capitol route, never a redirect to the login (D20), and `CapitolAccessTest` checks each one.
2. Done October 5, 2026: the shared `auth.user` prop is the `SessionUser` Data object (name only), locked by `CapitolAccessTest`.
3. Convert the starter kit's class-style tests to Pest (`./vendor/bin/pest --drift`), move `RefreshDatabase` into `tests/Pest.php` once, delete `tests/Unit/ExampleTest.php` and `tests/Feature/ExampleTest.php`.
4. Rewrite the login rate-limit test to send six bad logins instead of rebuilding the throttle key.


## Workspace foundation verification

October 24, 2026: composer ci:check passes with 131 tests (667 assertions). Coverage includes all three roles, direct-route denial, no wishes on the dashboard or Staff responses, assigned-town authorization, profile constraints and protected-field injection, private birthday props, audit immutability, idle/absolute expiry, role/deactivation revocation, no remember-me, encrypted history and logout clearing, stale-tab checks, no-referrer validation redirects, and the real 49-barangay census drill-down. Browser and SSR production builds pass. MapLibre is lazy-loaded with an explicit Vite worker and only mounts on the client.

Manual review covered desktop and 375px layouts, keyboard town selection, explicit barangay drill-down, map click selection and URL/breadcrumb synchronization, light/dark appearance, profile error feedback, aggregate Community Wishes, and Super Admin audit navigation. Basemap fallback and fatal worker/WebGL fallback are distinct; the WebGL context-loss event is explicitly handled. No browser-test dependencies were added.

Final source and security recheck: verified-only town facts, same-year complete population totals, no combined municipal accounts mislabeled as provincial finances, user-input-only activity renewal for all three roles, background non-renewal, and no-store JSON expiry preserving the Inertia history-clear flag. Independent privacy review found the JSON redirect/history issue; it was fixed and re-reviewed with no open findings. Manual profile rejection preserves valid input and shows a linked error summary plus inline birthday feedback. Plan lifecycle and evidence are maintained under `docs/plan/`.
