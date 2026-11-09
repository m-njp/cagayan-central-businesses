<?php

use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\ProtectAdminSession;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: ['appearance']);

        // No redirect target for guests. The exception below turns it into a 404 (D20).
        $middleware->redirectGuestsTo(fn () => null);

        $middleware->web(append: [
            ProtectAdminSession::class,
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Guests on a login-only page get a plain 404, not a redirect that reveals the login path (D20).
        $exceptions->map(AuthenticationException::class, fn () => new NotFoundHttpException);

        $exceptions->respond(function (Response $response, Throwable $exception, Request $request) {
            if (in_array($response->getStatusCode(), [403, 404, 419, 429, 500, 503], true)
                && (! $request->expectsJson() || $request->header('X-Inertia'))) {
                $error = Inertia::render('errors/status', ['status' => $response->getStatusCode()])
                    ->toResponse($request)->setStatusCode($response->getStatusCode());
                $error->headers->add(['Cache-Control' => 'private, no-store', 'X-Frame-Options' => 'DENY',
                    'X-Content-Type-Options' => 'nosniff', 'Referrer-Policy' => 'no-referrer']);

                return $error;
            }

            return $response;
        });

        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
