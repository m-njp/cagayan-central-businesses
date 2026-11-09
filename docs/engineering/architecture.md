# Architecture

How the back end is built, where each piece of code goes, and how to keep it fast. Read this before adding a route, controller, model, query, or service.

The goal is code that a teammate can read in one pass. Simple beats clever. Short beats complete. Add a layer only when the code in front of you needs it.

## The request flow

```
route -> middleware -> Form Request -> controller -> Query / Action / Service -> Data object -> Inertia page
```

| Piece | Lives in | Job | Never |
| :- | :- | :- | :- |
| Route | `routes/` | URL, name, middleware group | Logic |
| Form Request | `app/Http/Requests/` | Validate input, authorize, turn input into typed values | Query the database for display |
| Controller | `app/Http/Controllers/` | Call one Query or Action, render one page or redirect | Business rules, metric math, raw SQL |
| Query | `app/Queries/` | Read data for one screen and return Data objects | Write anything |
| Action | `app/Actions/` | One write use case (submit a wish, log a record view) | Render or redirect |
| Service | `app/Services/` | A domain rule reused by many Queries or Actions (the privacy guard, the metrics refresh) | Know about HTTP |
| Model | `app/Models/` | Table mapping: relationships, casts, scopes, hidden fields | HTTP, formatting for screens |
| Data object | `app/Data/` | The exact fields a page or export receives | Hold an Eloquent model |
| Enum | `app/Enums/` | One backed enum per enum column, with small helper methods | Database calls |

Start with the fewest pieces that work. A page with no input and one simple read needs a route, a controller, a Query, and a Data object. That is it.

## Routes

- Three route files: `routes/web.php` (home page, and it requires the others), `routes/public.php`, `routes/capitol.php`.
- Every route has a name. Public routes are `public.*`. Capitol routes are `capitol.*`.
- Capitol routes sit in one group so a new route cannot be left open by mistake:

```php
Route::middleware(['auth', 'can:capitol'])
    ->prefix('capitol')
    ->name('capitol.')
    ->group(function () {
        Route::get('barangays/{barangay:psgc_code}', [BarangayController::class, 'show'])->name('barangays.show');
    });
```

- Bind models by a readable key where one exists (`{barangay:psgc_code}`), so URLs can be shared and bookmarked.
- Use resource method names: `index`, `show`, `create`, `store`, `edit`, `update`, `destroy`. A controller with one job uses `__invoke`.
- Filters live in the query string (`?year=2026&type=pharmacy`), so a refresh, the back button, and a shared link all show the same screen. This does not require a server request per selection: loaded-data sorts, filters, views and cached map selections use local Inertia history. Queries return bounded public datasets with finished metrics; fetch only missing data. See frontend.md, "Local Selection Contract".
- After any `POST`, `PUT`, `PATCH`, or `DELETE`, redirect. Never render a page from a write. Show the result with a toast: `Inertia::flash('toast', ['type' => 'success', 'message' => __('Wish recorded.')])`.

### Clean URLs

A URL should read like an address someone could type or say out loud.

| Rule | Good | Bad |
| :- | :- | :- |
| Lower case, words joined by hyphens | `/capitol/business-types` | `/capitol/businessTypes`, `/capitol/business_types` |
| Plural nouns for collections, no verbs | `/capitol/barangays` | `/capitol/get-barangays`, `/capitol/barangay-list` |
| A readable key instead of a database id | `/capitol/barangays/0201529001` | `/capitol/barangays/17` |
| Audience first, then the thing | `/capitol/...`, `/map`, `/guide` | `/admin-panel/...`, `/pages/public/map` |
| Filters in the query string, not the path | `/capitol/barangays?year=2026` | `/capitol/barangays/year/2026` |
| No file endings or trailing slash | `/guide` | `/guide.php`, `/guide/` |

Public pages use short top-level paths (`/map`, `/guide`, `/wishes`) even though their route names start with `public.`. Only Capitol pages carry a prefix (`/capitol`), so the login wall is visible in the URL.

### Redirects

Always redirect to a route name, never to a typed path, so renaming a URL never breaks a redirect.

| Situation | Use |
| :- | :- |
| After a successful write | `return to_route('capitol.wishes.index');` |
| Back to the form after a write on the same page | `return back();` |
| To a page outside Inertia (a file download, another site) | `return Inertia::location($url);` |
| A URL was renamed and old links exist | `Route::permanentRedirect('old-path', 'new-path');` in the route file |
| Where login sends people | `'home'` in `config/fortify.php` |
| Where guests are sent when they open a Capitol page | nowhere: a plain 404, so the login path stays hidden (decisions.md, D20) |

Do not use `redirect('/some/path')` or `redirect()->to(...)` with a typed path. Validation failures redirect back on their own through the Form Request.

## Controllers stay thin

A controller method is usually under 10 lines. If it grows, the logic belongs in a Query, Action, or Service.

```php
final class BarangayController extends Controller
{
    public function show(Barangay $barangay, FilterRequest $request, BarangayProfileQuery $query): Response
    {
        return Inertia::render('capitol/barangays/show', [
            'profile' => $query->get($barangay, $request->filters()),
        ]);
    }
}
```

Signs a controller is too fat: a loop, an `if` about business rules, a `DB::` call, a sum or a percentage, more than one `Inertia::render`.

## Form Requests

- Every route that takes input has one. No `$request->validate()` in controllers.
- `authorize()` returns `true` when the route group already checks access. Add a real check only for record-level rules.
- Turn input into typed values once, so the rest of the code never reads raw strings:

```php
public function filters(): Filters
{
    return new Filters(
        year: $this->integer('year', config('cagayan.data.latest_year')),
        typeId: $this->integer('type') ?: null,
    );
}
```

## Queries read

One class per screen or panel. It reads summary tables, passes sales through the privacy guard, and returns Data objects.

```php
final class BarangayProfileQuery
{
    public function __construct(private PrivacyGuard $guard) {}

    public function get(Barangay $barangay, Filters $filters): BarangayProfile
    {
        $row = DB::table('stats_area_year')
            ->where('barangay_id', $barangay->id)
            ->where('year', $filters->year)
            ->when($filters->typeId, fn ($q, $id) => $q->where('business_type_id', $id))
            ->selectRaw('sum(active_count) as active, sum(declared_sales_total) as sales, max(declared_sales_max_single) as max_single')
            ->first();

        return new BarangayProfile(
            name: $barangay->name,
            activeCount: (int) $row->active,
            sales: $this->guard->figure($row->sales, (int) $row->active, $row->max_single),
        );
    }
}
```

- Numbers on screen come from `stats_` tables. Request code never sums `businesses` or `business_years`.
- The sales column `business_years.declared_gross_sales` is read only by the SQL in `database/sql/metrics/`.

## Actions write

One class, one public `handle()` method, one use case. Wrap more than one write in a transaction.

For example, `SubmitWish::handle()` receives the validated town, optional business type, optional company name, reason, and the submitter and network hashes. It acquires a lock per submitter, checks the cooldown and network allowance, and writes the wish. `EditWish` applies the edit allowance and records the edit in a transaction. See those Actions for the complete implementation; raw network addresses and the removed `dedupe_hash` design must not be copied into new code.

## Services hold shared domain rules

Make a service when two or more Queries or Actions need the same rule. The privacy guard is the main one: it is the only code that decides whether a sales figure may be shown.

Services are plain classes resolved by the container. Inject them through the constructor. No static helpers, no facades of our own.

## Data objects

Pages and exports receive Data objects, never models. With Inertia, every prop reaches the browser, so the Data object is the list of fields we chose to send.

```php
final readonly class BarangayProfile
{
    public function __construct(
        public string $name,
        public int $activeCount,
        public GuardedFigure $sales,
    ) {}
}
```

- Plain PHP classes. No package.
- Separate objects for each audience: `PublicBusiness` has no admin fields at all, `CapitolBusiness` adds them.
- Money is a string. Counts are ints. Rates are floats rounded in the Query, or `null` when not available.
- The TypeScript type in `resources/js/types/` matches the class field for field.

## Models

- Explicit `#[Fillable]` and `#[Hidden]` attributes. The sales column and anything never displayed go in `#[Hidden]`.
- `casts()` for enums, dates, and decimals (`'decimal:2'` gives a string, never a float).
- Relationships with return types. Query scopes for filters used in more than one place.
- No HTTP code, no screen formatting, no business workflows.
- Models that are written only once (`record_view_logs`) set `const UPDATED_AT = null;`.

## Object-oriented without ceremony

- `final` classes by default. `readonly` for Data objects and value objects.
- Constructor injection with promoted properties.
- Small enums with behavior beat scattered `if` checks: `BusinessStatus::isActive()`.
- Value objects for things with rules: `GuardedFigure` (shown with a value, or hidden with a reason).
- An interface only when there are two real implementations. No repositories around Eloquent. No base classes "for later". No events for code that runs in the same request.

## Reuse before writing

1. Search first: `app/Queries`, `app/Services`, `app/Data`, model scopes, enums.
2. Extend what exists if the change keeps it simple.
3. Copying a few lines once is fine. Extract on the third copy, when the shared shape is clear.

## Login

Fortify handles the login `POST` and logout. Its built-in pages are off (`'views' => false` in `config/fortify.php`), and `routes/web.php` renders `auth/login`. Every other starter kit feature was removed: registration, password reset, email verification, two-factor login, passkeys, and the settings pages (decisions.md, O8).

Fortify still registers two password confirmation endpoints (`user/confirm-password`, `user/confirmed-password-status`) for logged-in users. Nothing uses them.

## Performance

The app is small today. These habits keep it fast when more towns arrive.

### No N+1 queries

Lazy loading is turned off outside production (`Model::preventLazyLoading()` in `AppServiceProvider`). A relationship that was not loaded throws an error in development and in tests instead of running one query per row.

```php
// Bad: one query for businesses, then one per business for its type
$businesses = Business::all();
foreach ($businesses as $b) { $b->businessType->name; }

// Good: two queries in total
$businesses = Business::with('businessType:id,name')->get();
```

- `with()` for relationships you read. `withCount()` and `withSum()` for numbers about a relationship.
- `select()` only the columns the Data object needs.
- Watch Data object builders: a `->map(fn ...)` that touches a relationship is an N+1 waiting to happen.

### Lazy and chunked loading for big reads

Never `->get()` on a table that grows.

- Lists on screen: `paginate()` (or `cursorPaginate()` for long scrolling lists).
- Exports: `lazyById(500)` with a streamed response, so memory stays flat.

```php
return response()->streamDownload(function () use ($query) {
    $out = fopen('php://output', 'w');
    $query->lazyById(500)->each(fn ($row) => fputcsv($out, $row->toCsv()));
}, 'barangays.csv');
```

### Let the database do the work

- Aggregates in SQL (`sum`, `count`, `group by`), not in PHP loops.
- Metrics are precomputed into `stats_` tables by `php artisan metrics:refresh`. Screens read those.
- Spatial work in PostGIS (`ST_Contains`, `ST_AsGeoJSON`), not in PHP or React.
- Every foreign key and every column in a frequent `where` or `order by` has an index. See `database.md`.

### Send less to the browser

- Props are the payload. Send only what the page renders.
- Heavy panels use deferred props (`Inertia::defer(fn () => ...)`) so the page shows first and the panel fills in.
- Boundary GeoJSON is large and rarely changes: simplify the shapes, then cache the response (`Cache::rememberForever`, cleared by the seeder and `metrics:refresh`).

## Rate limiting

Anything a stranger can call repeatedly gets a limit.

```php
// AppServiceProvider::boot()
RateLimiter::for('wishes', fn (Request $request) => Limit::perHour(10)->by($request->ip()));
RateLimiter::for('exports', fn (Request $request) => Limit::perMinute(10)->by($request->user()?->id ?: $request->ip()));

// routes/public.php
Route::post('wishes', [WishController::class, 'store'])->middleware('throttle:wishes')->name('wishes.store');
```

| Endpoint | Limit | Where |
| :- | :- | :- |
| Login | `admin.login_per_minute` per email and IP, `admin.login_per_hour_per_ip` per IP, `admin.login_per_hour_per_account` per account (lockout) | `FortifyServiceProvider` |
| Wish form and edits (P3) | `wishes.per_hour_per_ip` per IP, per hour. The 3 day cooldown and weekly edit limit are separate rules in `WishAllowance` (privacy-rules.md, P12) | `AppServiceProvider` |
| CSV and PDF exports (C14) | per user, per minute | add with the feature |

Limits live in `AppServiceProvider` (or `FortifyServiceProvider` for login), named, and applied with `throttle:<name>`. The limiter key may use the IP. That is held briefly in the cache, never written to a table.

## Errors and logs

- Let Laravel render errors. Catch an exception only when you can do something useful with it.
- No sales amounts, owner names, or contact numbers in logs, exception messages, or validation messages (privacy rule P14).
- `APP_DEBUG=false` on anything other people can reach.

## Config

- Every threshold lives in `config/cagayan.php` and is read with `config('cagayan....')`. Never a literal in PHP, SQL, or React.
- `env()` is read only inside `config/` files.


## Workspace foundation

The capitol gate admits active Super Admin, Admin, and Staff accounts. Separate community-wishes and activity-logs gates enforce the visibility matrix; edit-town checks assigned towns for Staff. Routes remain inside the shared authenticated Capitol group. The dashboard's WorkspaceMap payload contains economic/geographic data only. Workspace capabilities and session deadlines are private Capitol props, not public shared props; auth.user remains name-only.

ProtectAdminSession runs on web requests and binds each session to auth_version, start time, and last user-initiated Capitol activity. Background reloads send X-Workspace-Background: true. A stale tab rechecks through a non-renewing reload before redirecting to sign-in. Private Inertia history is encrypted; sign-in/logout/expiry clear history keys. Cookie security defaults to HTTPS-only in production; deployments must retain secure cookie settings.

The prototype retains Fortify password sign-in with remember-me disabled and public registration off. The private /capitol/profile page is an approved workspace feature, not restored starter /settings/profile. Identity-provider migration is documented in database.md and deliberately deferred. Trusted operators assign an existing account with php artisan workspace:role <email> super_admin|admin|staff. This command never creates an account or silently promotes an existing administrator.
