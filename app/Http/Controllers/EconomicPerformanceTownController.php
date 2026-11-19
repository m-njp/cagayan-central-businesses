<?php

namespace App\Http\Controllers;

use App\Models\Municipality;
use App\Queries\AnalyticsCatalogQuery;
use Illuminate\Http\JsonResponse;

final class EconomicPerformanceTownController extends Controller
{
    /** One town's public finance series for the Economy and Analytics pages, cached by the browser after the first load. */
    public function __invoke(Municipality $town, AnalyticsCatalogQuery $query): JsonResponse
    {
        return response()->json($query->town($town->psgc_code));
    }
}
