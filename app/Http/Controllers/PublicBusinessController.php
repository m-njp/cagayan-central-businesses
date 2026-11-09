<?php

namespace App\Http\Controllers;

use App\Http\Requests\PublicBusinessesRequest;
use App\Models\Business;
use App\Queries\PublicBusinessesQuery;
use Inertia\Inertia;
use Inertia\Response;

final class PublicBusinessController extends Controller
{
    /** Nothing loads until a town is chosen; each town's businesses load once and are cached in the browser. */
    public function index(PublicBusinessesRequest $request, PublicBusinessesQuery $query): Response
    {
        $town = $request->town();

        return Inertia::render('public/businesses/index', [
            'directory' => fn () => $query->directory(),
            'businesses' => fn () => $town === null ? null : $query->town($town),
        ]);
    }

    public function show(Business $business, PublicBusinessesQuery $query): Response
    {
        return Inertia::render('public/businesses/show', ['business' => $query->profile($business)]);
    }
}
