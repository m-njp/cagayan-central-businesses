<?php

namespace App\Http\Controllers\Capitol;

use App\Actions\ReviewFinanceFigures;
use App\Http\Controllers\Controller;
use App\Http\Requests\FinanceReportRequest;
use App\Http\Requests\FinanceReviewListRequest;
use App\Http\Requests\ReviewFinanceRequest;
use App\Models\Municipality;
use App\Queries\FinanceReportQuery;
use App\Queries\FinanceReviewListQuery;
use App\Queries\FinanceUploadQuery;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final class FinanceReviewController extends Controller
{
    public function index(FinanceReviewListRequest $request, FinanceReviewListQuery $query): Response
    {
        return Inertia::render('capitol/finances/index', ['list' => $query->get(
            $request->user(), $request->input('town'),
            $request->filled('year') ? $request->integer('year') : null, $request->input('status'),
        )]);
    }

    public function show(FinanceReportRequest $request, Municipality $town, FinanceReportQuery $query, FinanceUploadQuery $uploads): Response
    {
        return Inertia::render('capitol/finances/show', [
            'report' => fn () => $query->get($request->user(), $town, $request->reportYear()),
            'uploadStatuses' => Inertia::optional(fn () => $uploads->listing($town, $request->reportYear())),
            'breadcrumbs' => [
                ['label' => 'Government Finances', 'href' => route('capitol.finances.index', absolute: false)],
                ['label' => $town->name],
                ['label' => 'Report '.$request->reportYear()],
            ],
        ]);
    }

    public function store(ReviewFinanceRequest $request, Municipality $town, ReviewFinanceFigures $review): RedirectResponse
    {
        $decision = $request->string('decision')->toString();
        $result = $review->handle($request->user(), $town, $request->reportYear(), $decision,
            $request->figureIds(), $request->filled('note') ? $request->string('note')->toString() : null);

        $message = sprintf('%d %s %s.', $result['done'], $result['done'] === 1 ? 'figure' : 'figures', $decision === 'verified' ? 'verified' : 'returned');
        if ($result['skipped'] > 0) {
            $message .= sprintf(' %d you entered %s for another reviewer.', $result['skipped'], $result['skipped'] === 1 ? 'needs' : 'need');
        }
        Inertia::flash('toast', ['type' => $result['skipped'] > 0 ? 'warning' : 'success', 'message' => $message]);

        return to_route('capitol.finances.show', ['town' => $town->psgc_code, 'year' => $request->reportYear()]);
    }
}
