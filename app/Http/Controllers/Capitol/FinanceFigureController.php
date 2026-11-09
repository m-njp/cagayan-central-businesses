<?php

namespace App\Http\Controllers\Capitol;

use App\Actions\SubmitFinanceEntries;
use App\Http\Controllers\Controller;
use App\Http\Requests\SubmitFinanceEntriesRequest;
use App\Models\Municipality;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

final class FinanceFigureController extends Controller
{
    public function store(SubmitFinanceEntriesRequest $request, Municipality $town, SubmitFinanceEntries $submit): RedirectResponse
    {
        $saved = $submit->handle($request->user(), $town, $request->reportYear(), $request->entries());
        Inertia::flash('toast', $saved === null
            ? ['type' => 'warning', 'message' => 'These changes were already saved.']
            : ['type' => 'success', 'message' => sprintf(
                '%d %s sent for review. They appear on Economy and Analytics once someone else verifies them.',
                $saved, $saved === 1 ? 'figure' : 'figures',
            )]);

        return to_route('capitol.finances.show', ['town' => $town->psgc_code, 'year' => $request->reportYear()]);
    }
}
