<?php

namespace App\Http\Controllers\Capitol;

use App\Actions\ReviewPopulation;
use App\Actions\SubmitPopulation;
use App\Http\Controllers\Controller;
use App\Http\Requests\PopulationRequest;
use App\Http\Requests\ReviewPopulationRequest;
use App\Http\Requests\SubmitPopulationRequest;
use App\Models\Municipality;
use App\Queries\PopulationQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class PopulationController extends Controller
{
    /** Opens the demo town for Admins, or the first assigned town for Staff. */
    public function index(Request $request): RedirectResponse
    {
        $user = $request->user();
        $town = $user->isCapitolAdmin()
            ? Municipality::query()->where('psgc_code', config('cagayan.performance.demo_town'))->first()
            : $user->assignedTowns()->orderBy('name')->first();

        return $town === null ? abort(404) : to_route('capitol.population.show', ['town' => $town->psgc_code]);
    }

    public function show(PopulationRequest $request, Municipality $town, PopulationQuery $query): Response
    {
        return Inertia::render('capitol/population/index', ['population' => $query->get($request->user(), $town, $request->year())]);
    }

    public function store(SubmitPopulationRequest $request, Municipality $town, SubmitPopulation $submit): RedirectResponse
    {
        $saved = $submit->handle($request->user(), $town, (int) $request->year(), $request->entries());
        Inertia::flash('toast', $saved === null
            ? ['type' => 'warning', 'message' => 'These changes were already saved.']
            : ['type' => 'success', 'message' => sprintf(
                '%d %s sent for review. They appear on the Home and Dashboard maps once someone else verifies them.',
                $saved, $saved === 1 ? 'count' : 'counts',
            )]);

        return to_route('capitol.population.show', ['town' => $town->psgc_code, 'year' => $request->year()]);
    }

    public function review(ReviewPopulationRequest $request, Municipality $town, ReviewPopulation $review): RedirectResponse
    {
        $decision = $request->string('decision')->toString();
        $result = $review->handle($request->user(), $town, (int) $request->year(), $decision,
            array_values(array_map('strval', (array) $request->input('figures'))), $request->filled('note') ? $request->string('note')->toString() : null);
        $message = sprintf('%d %s %s.', $result['done'], $result['done'] === 1 ? 'count' : 'counts', $decision === 'verified' ? 'verified' : 'returned');
        if ($result['skipped'] > 0) {
            $message .= sprintf(' %d you entered %s another reviewer.', $result['skipped'], $result['skipped'] === 1 ? 'needs' : 'need');
        }
        Inertia::flash('toast', ['type' => $result['skipped'] > 0 ? 'warning' : 'success', 'message' => $message]);

        return to_route('capitol.population.show', ['town' => $town->psgc_code, 'year' => $request->year()]);
    }
}
