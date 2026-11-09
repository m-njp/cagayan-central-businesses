<?php

namespace App\Http\Controllers\Capitol;

use App\Data\Workspace;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SessionActivityController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user !== null, 403);

        return response()->json(Workspace::from($user, $request));
    }
}
