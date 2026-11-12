<?php

namespace App\Http\Controllers\Capitol;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReportDifferencesRequest;
use App\Models\Municipality;
use App\Queries\ReportDifferencesQuery;
use Inertia\Inertia;
use Inertia\Response;

final class ReportDifferenceController extends Controller
{
    public function __invoke(ReportDifferencesRequest $request, ReportDifferencesQuery $query): Response
    {
        return Inertia::render('capitol/report-differences/index', [
            'differences' => fn () => $query->get(),
            'detail' => Inertia::optional(fn () => $request->filled('town')
                ? $query->detail(Municipality::query()->where('psgc_code', $request->string('town'))->firstOrFail(), $request->integer('year'))
                : null),
        ]);
    }
}
