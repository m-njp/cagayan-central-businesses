<?php

namespace App\Providers;

use App\Enums\RecordStatus;
use App\Enums\UserRole;
use App\Models\ActivityLog;
use App\Models\Business;
use App\Models\Municipality;
use App\Models\User;
use App\Services\BusinessVisibility;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use RuntimeException;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureRateLimiting();
        $this->guardAdminLoginPath();

        // D9. Checked on the whole Capitol route group (routes/capitol.php).
        Gate::define('capitol', fn (User $user): bool => $user->canEnterWorkspace());
        Gate::define('community-wishes', fn (User $user): bool => $user->isCapitolAdmin());
        Gate::define('activity-logs', fn (User $user): bool => $user->canEnterWorkspace() && $user->role === UserRole::SuperAdmin);
        // D35. Admins sign off finance figures; FinanceSignOff also refuses a figure the reviewer entered.
        Gate::define('finance-review', fn (User $user): bool => $user->isCapitolAdmin());
        Gate::define('manage-categories', fn (User $user): bool => $user->isCapitolAdmin());
        Gate::define('edit-town', fn (User $user, Municipality $town): bool => $user->canEnterWorkspace()
            && ($user->isCapitolAdmin() || $user->assignedTowns()->whereKey($town->id)->exists()));
        // Registry (D40): Staff prepare records for their towns, Admin and Super Admin review them.
        Gate::define('add-business', fn (User $user): bool => $user->canEnterWorkspace() && $user->role === UserRole::Staff
            && $user->assignedTowns()->exists());
        Gate::define('edit-business', fn (User $user, Business $business): bool => $user->canEnterWorkspace() && $user->role === UserRole::Staff
            && BusinessVisibility::allows($business) && $business->record_status !== RecordStatus::Submitted
            && $user->assignedTowns()->whereKey($business->barangay()->value('municipality_id'))->exists());
        Gate::define('review-business', fn (User $user, Business $business): bool => $user->isCapitolAdmin()
            && BusinessVisibility::allows($business) && $business->record_status === RecordStatus::Submitted);

        Event::listen(Login::class, function (Login $event): void {
            if ($event->user instanceof User && request()->hasSession()) {
                Inertia::clearHistory();
                request()->session()->put('workspace', [
                    'started_at' => now()->getTimestamp(), 'last_activity' => now()->getTimestamp(),
                    'version' => $event->user->auth_version,
                ]);
                ActivityLog::record('signed_in', $event->user->id);
            }
        });
        Event::listen(Logout::class, fn (Logout $event) => $event->user instanceof User ? ActivityLog::record('signed_out', $event->user->id) : null);
        Event::listen(Failed::class, fn () => ActivityLog::record('sign_in_failed', null));
    }

    /**
     * The network address is the key. It is held briefly in the cache and never written to a table.
     */
    protected function configureRateLimiting(): void
    {
        RateLimiter::for('wishes', fn (Request $request) => Limit::perHour((int) config('cagayan.wishes.per_hour_per_ip'))->by((string) $request->ip()));
        RateLimiter::for('finance-sources', fn (Request $request) => Limit::perMinute((int) config('cagayan.finances.source_requests_per_minute'))->by((string) $request->ip()));
        RateLimiter::for('registry-documents', fn (Request $request) => Limit::perMinute((int) config('cagayan.registry.document_requests_per_minute'))->by((string) $request->user()?->getAuthIdentifier()));
        RateLimiter::for('population-writes', fn (Request $request) => Limit::perMinute((int) config('cagayan.population.writes_per_minute'))->by((string) $request->user()?->getAuthIdentifier()));
        RateLimiter::for('finance-writes', fn (Request $request) => Limit::perMinute((int) config('cagayan.finances.writes_per_minute'))->by((string) $request->user()?->getAuthIdentifier()));
        RateLimiter::for('workspace-activity', fn (Request $request) => Limit::perMinute((int) config('cagayan.admin.activity_requests_per_minute'))->by((string) $request->user()?->getAuthIdentifier()));
    }

    /**
     * The example login path is public in the repository, so production refuses to serve it (decisions.md, D20).
     */
    protected function guardAdminLoginPath(): void
    {
        // CI and `composer setup` boot the app from the console before `.env` exists.
        if (app()->runningInConsole()) {
            return;
        }

        $path = config('cagayan.admin.login_path');

        if (app()->isProduction() && (blank($path) || $path === 'staff-sign-in')) {
            throw new RuntimeException('Set CAGAYAN_ADMIN_LOGIN_PATH in .env to a long random value.');
        }
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        // Lazy loading causes N+1 queries. Outside production it throws, so they are caught early.
        Model::preventLazyLoading(! app()->isProduction());

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
