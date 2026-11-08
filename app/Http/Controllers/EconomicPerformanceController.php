<?php

namespace App\Http\Controllers;

use App\Http\Requests\AnalyticsFiltersRequest;
use App\Queries\EconomicPerformanceQuery;
use Inertia\Inertia;
use Inertia\Response;

final class EconomicPerformanceController extends Controller
{
    public function __invoke(AnalyticsFiltersRequest $request, EconomicPerformanceQuery $query): Response
    {
        return Inertia::render('public/economic-performance', [
            'performance' => $query->get($request->analyticsFilters()),
        ]);
    }
}
