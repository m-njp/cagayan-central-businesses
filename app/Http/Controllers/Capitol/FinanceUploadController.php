<?php

namespace App\Http\Controllers\Capitol;

use App\Actions\UploadFinanceReport;
use App\Http\Controllers\Controller;
use App\Http\Requests\UploadFinanceReportRequest;
use App\Models\Municipality;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

final class FinanceUploadController extends Controller
{
    public function store(UploadFinanceReportRequest $request, Municipality $town, UploadFinanceReport $upload): RedirectResponse
    {
        $result = $upload->handle($request->user(), $town, $request->reportYear(), $request->string('kind')->toString(), $request->report());
        Inertia::flash('toast', ['type' => $result['repeated'] ? 'warning' : 'success', 'message' => $result['repeated']
            ? 'This file was already uploaded for this report.'
            : 'Report uploaded. Its figures appear here as pending once it is read.']);

        return to_route('capitol.finances.show', ['town' => $town->psgc_code, 'year' => $request->reportYear()]);
    }
}
