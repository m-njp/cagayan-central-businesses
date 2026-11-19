<?php

namespace App\Http\Controllers;

use App\Services\PublicExecutiveSummaries;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PublicReportController extends Controller
{
    public function __invoke(string $report, PublicExecutiveSummaries $summaries): BinaryFileResponse
    {
        return response()->file($summaries->file($report) ?? abort(404));
    }
}
