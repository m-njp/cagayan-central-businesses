<?php

namespace App\Http\Controllers\Capitol;

use App\Http\Controllers\Controller;
use App\Http\Requests\WorkspaceListRequest;
use App\Queries\CommunityWishesQuery;
use Inertia\Inertia;
use Inertia\Response;

final class CommunityWishController extends Controller
{
    public function __invoke(WorkspaceListRequest $request, CommunityWishesQuery $query): Response
    {
        return Inertia::render('capitol/community-wishes', ['table' => $query->get($request->input('town'), $request->input('category'))]);
    }
}
