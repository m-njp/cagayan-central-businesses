<?php

namespace App\Http\Controllers;

use App\Models\FinanceFigure;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class FinanceSourceController extends Controller
{
    public function __invoke(FinanceFigure $figure): BinaryFileResponse
    {
        $path = $figure->publicFile() ?? abort(404);

        // A PDF opens in the browser at the page in the link's fragment; a workbook downloads.
        return str_ends_with($path, '.pdf') ? response()->file($path) : response()->download($path);
    }
}
