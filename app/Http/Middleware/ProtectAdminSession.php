<?php

namespace App\Http\Middleware;

use App\Models\ActivityLog;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

final class ProtectAdminSession
{
    /** @param Closure(Request): Response $next */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var User|null $user */
        $user = $request->user();
        if ($user) {
            $now = now()->getTimestamp();
            $context = $request->session()->get('workspace');
            $expired = is_array($context) && (
                $now - (int) $context['last_activity'] >= (int) config('cagayan.admin.idle_seconds') ||
                $now - (int) $context['started_at'] >= (int) config('cagayan.admin.absolute_seconds') ||
                (int) $context['version'] !== $user->auth_version
            );
            if (! $user->is_active || $expired || Auth::viaRemember()) {
                ActivityLog::record('session_expired', $user->id);
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                Inertia::clearHistory();

                // A standalone JSON client cannot apply Inertia history flags from a followed redirect.
                if ($request->expectsJson() && ! $request->header('X-Inertia')) {
                    return response()->json(['message' => 'Your session ended. Sign in again to continue.'], 401,
                        ['Cache-Control' => 'private, no-store', 'X-Frame-Options' => 'DENY']);
                }

                return to_route('admin.login')->with('notice', 'Your session ended. Sign in again to continue.');
            }
            if (! is_array($context)) {
                $request->session()->put('workspace', ['started_at' => $now, 'last_activity' => $now, 'version' => $user->auth_version]);
            }
            // Background reloads must not keep an unattended workstation signed in.
            if ($request->routeIs('capitol.*') && $request->header('X-Workspace-Background') !== 'true') {
                $request->session()->put('workspace.last_activity', $now);
            }
        }

        $response = $next($request);
        if ($request->routeIs('logout')) {
            Inertia::clearHistory();
        }

        return $response;
    }
}
