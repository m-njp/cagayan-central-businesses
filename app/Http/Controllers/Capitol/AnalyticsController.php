<?php

namespace App\Http\Controllers\Capitol;

use App\Http\Controllers\Controller;
use App\Http\Requests\AnalyticsRequest;
use App\Queries\AnalyticsCatalogQuery;
use Inertia\Inertia;
use Inertia\Response;

final class AnalyticsController extends Controller
{
    public function __invoke(AnalyticsRequest $request, AnalyticsCatalogQuery $query): Response
    {
        $filters = $request->analyticsFilters();

        return Inertia::render('capitol/analytics/index', [
            'catalog' => $query->get($filters['town']),
            'filters' => $filters,
        ]);
    }
}
