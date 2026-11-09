<?php

namespace App\Http\Controllers\Capitol;

use App\Actions\UpdateProfile;
use App\Data\AccountProfile;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateProfileRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class ProfileController extends Controller
{
    public function show(Request $request): Response
    {
        return Inertia::render('capitol/profile', ['profile' => AccountProfile::from($request->user())]);
    }

    public function update(UpdateProfileRequest $request, UpdateProfile $update): RedirectResponse
    {
        $update->handle($request->user(), $request->string('display_name')->toString(),
            $request->filled('birthday_month') ? $request->integer('birthday_month') : null,
            $request->filled('birthday_day') ? $request->integer('birthday_day') : null);
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Profile updated.']);

        return to_route('capitol.profile.show');
    }
}
