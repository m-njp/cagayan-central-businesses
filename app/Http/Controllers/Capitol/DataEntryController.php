<?php

namespace App\Http\Controllers\Capitol;

use App\Http\Controllers\Controller;
use App\Http\Requests\DataEntryRequest;
use App\Queries\DataEntryQuery;
use Inertia\Inertia;
use Inertia\Response;

final class DataEntryController extends Controller
{
    public function __invoke(DataEntryRequest $request, DataEntryQuery $query): Response
    {
        return Inertia::render('capitol/data-entry', ['entry' => $query->get($request->user(), $request->page())]);
    }
}
