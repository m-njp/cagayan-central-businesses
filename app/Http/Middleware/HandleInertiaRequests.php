<?php

namespace App\Http\Middleware;

use App\Data\SessionUser;
use App\Data\Workspace;
use App\Queries\DemoLoginQuery;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        Inertia::encryptHistory($request->routeIs('capitol.*'));

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user() ? SessionUser::from($request->user()) : null,
            ],
            'simulated' => (bool) config('cagayan.simulated'),
            ...($request->routeIs('capitol.*') && $request->user()?->canEnterWorkspace()
                ? ['workspace' => Workspace::from($request->user(), $request)] : []),
            ...($request->routeIs('admin.login') ? ['notice' => $request->session()->get('notice'), 'demoLogins' => app(DemoLoginQuery::class)->options()] : []),
        ];
    }
}
