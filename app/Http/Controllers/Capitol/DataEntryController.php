<?php

namespace App\Http\Controllers\Capitol;

use App\Http\Controllers\Controller;
use App\Http\Requests\DataEntryRequest;
use App\Queries\DataEntryQuery;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final class DataEntryController extends Controller
{
    public function __invoke(DataEntryRequest $request, DataEntryQuery $query): Response|RedirectResponse
    {
        $entry = $query->get($request->user(), $request->page());
        if ($entry->pagination !== null && $entry->pagination->page > $entry->pagination->lastPage) {
            return to_route('capitol.data-entry', [...$request->query(), 'page' => $entry->pagination->lastPage]);
        }

        return Inertia::render('capitol/data-entry/index', ['entry' => $entry]);
    }
}
