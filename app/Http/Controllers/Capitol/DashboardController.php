<?php

namespace App\Http\Controllers\Capitol;

use App\Http\Controllers\Controller;
use App\Http\Requests\CapitolDashboardRequest;
use App\Queries\DataEntryQuery;
use App\Queries\WorkspaceMapQuery;
use Inertia\Inertia;
use Inertia\Response;

final class DashboardController extends Controller
{
    public function __invoke(CapitolDashboardRequest $request, WorkspaceMapQuery $query, DataEntryQuery $entry): Response
    {
        return Inertia::render('capitol/dashboard', [
            'map' => $query->get($request->input('town'), $request->input('level') === 'barangays'),
            // Null for roles that cannot review, so the table is absent rather than empty.
            'waiting' => $entry->waiting($request->user(), 1)['rows'] ?? null,
        ]);
    }
}
