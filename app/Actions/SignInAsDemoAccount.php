<?php

namespace App\Actions;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

final class SignInAsDemoAccount
{
    // The Login event starts the workspace session and writes the signed_in log, as a password sign in does.
    public function handle(Request $request, User $user): void
    {
        Auth::login($user);
        $request->session()->regenerate();
    }
}
