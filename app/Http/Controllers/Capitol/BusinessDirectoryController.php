<?php

namespace App\Http\Controllers\Capitol;

use App\Http\Controllers\Controller;
use App\Http\Requests\BusinessDirectoryRequest;
use App\Queries\BusinessDirectoryQuery;
use Inertia\Inertia;
use Inertia\Response;

final class BusinessDirectoryController extends Controller
{
    public function __invoke(BusinessDirectoryRequest $request, BusinessDirectoryQuery $query): Response
    {
        return Inertia::render('capitol/businesses', ['directory' => $query->get($request->filters(), $request->user()->can('add-business'))]);
    }
}
