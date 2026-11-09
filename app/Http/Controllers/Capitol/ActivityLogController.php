<?php

namespace App\Http\Controllers\Capitol;

use App\Http\Controllers\Controller;
use App\Http\Requests\WorkspaceListRequest;
use App\Queries\ActivityLogsQuery;
use Inertia\Inertia;
use Inertia\Response;

final class ActivityLogController extends Controller
{
    public function __invoke(WorkspaceListRequest $request, ActivityLogsQuery $query): Response
    {
        return Inertia::render('capitol/activity-logs', ['table' => $query->get($request->input('event'))]);
    }
}
