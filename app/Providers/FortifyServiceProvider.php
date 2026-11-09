<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Login and logout are registered in routes/web.php at the secret path (decisions.md, D20).
        Fortify::ignoreRoutes();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Only Capitol administrators may log in. Anyone else gets the same failure message
        // as a wrong password, so the form never reveals which accounts exist.
        Fortify::authenticateUsing(function (Request $request): ?User {
            $request->merge(['remember' => false]);
            $user = User::query()->where('email', Str::lower($request->string(Fortify::username())->toString()))->first();

            return $user && $user->canEnterWorkspace() && Hash::check($request->string('password')->toString(), $user->password)
                ? $user
                : null;
        });

        RateLimiter::for('login', function (Request $request) {
            $email = Str::transliterate(Str::lower($request->string(Fortify::username())->toString()));

            return [
                Limit::perMinute((int) config('cagayan.admin.login_per_minute'))->by($email.'|'.$request->ip()),
                Limit::perHour((int) config('cagayan.admin.login_per_hour_per_ip'))->by('ip|'.$request->ip()),
                // Lockout: an account under attack from many addresses is still capped.
                Limit::perHour((int) config('cagayan.admin.login_per_hour_per_account'))->by('account|'.$email),
            ];
        });
    }
}
