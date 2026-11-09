<?php

namespace App\Http\Controllers;

use App\Queries\HomeOverviewQuery;
use Inertia\Inertia;
use Inertia\Response;

final class HomeController extends Controller
{
    public function __invoke(HomeOverviewQuery $query): Response
    {
        return Inertia::render('public/home', [
            'overview' => $query->get(),
        ]);
    }
}
