<?php

namespace App\Http\Controllers\Capitol;

use App\Actions\RetryFinanceUpload;
use App\Actions\UploadFinanceReport;
use App\Http\Controllers\Controller;
use App\Http\Requests\FinanceUploadTaskRequest;
use App\Http\Requests\UploadFinanceReportRequest;
use App\Models\FinanceUpload;
use App\Models\Municipality;
use App\Queries\FinanceUploadQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

final class FinanceUploadController extends Controller
{
    public function store(UploadFinanceReportRequest $request, Municipality $town, UploadFinanceReport $upload): RedirectResponse
    {
        $result = $upload->handle($request->user(), $town, $request->reportYear(), $request->string('kind')->toString(), $request->report());
        Inertia::flash('financeUpload', $result['upload']->public_id);
        Inertia::flash('toast', ['type' => $result['repeated'] ? 'warning' : 'success', 'message' => $result['repeated']
            ? 'This file was already uploaded for this report.'
            : 'Report uploaded. Its figures appear here as pending once it is read.']);

        return to_route('capitol.finances.show', ['town' => $town->psgc_code, 'year' => $request->reportYear()]);
    }

    public function show(FinanceUploadTaskRequest $request, Municipality $town, int $year, FinanceUpload $upload, FinanceUploadQuery $query): JsonResponse
    {
        return response()->json($query->get($request->upload()))->header('Cache-Control', 'no-store');
    }

    public function retry(FinanceUploadTaskRequest $request, Municipality $town, int $year, FinanceUpload $upload, RetryFinanceUpload $retry): RedirectResponse
    {
        $retry->handle($request->upload());
        Inertia::flash('financeUpload', $request->upload()->public_id);
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Report queued for parsing.']);

        return to_route('capitol.finances.show', ['town' => $request->town()->psgc_code, 'year' => $request->reportYear()]);
    }
}
