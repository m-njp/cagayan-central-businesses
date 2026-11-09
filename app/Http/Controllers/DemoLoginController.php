<?php

namespace App\Http\Controllers;

use App\Actions\SignInAsDemoAccount;
use App\Enums\UserRole;
use App\Queries\DemoLoginQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class DemoLoginController extends Controller
{
    public function __invoke(Request $request, UserRole $role, DemoLoginQuery $demo, SignInAsDemoAccount $signIn): RedirectResponse
    {
        $signIn->handle($request, $demo->accountFor($role) ?? abort(404));

        return to_route('capitol.dashboard');
    }
}
