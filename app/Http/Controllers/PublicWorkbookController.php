<?php

namespace App\Http\Controllers;

use App\Services\PublicWorkbooks;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class PublicWorkbookController extends Controller
{
    /** A registered COA workbook, sent as a download under its readable name so it opens in a spreadsheet program. */
    public function __invoke(string $workbook, PublicWorkbooks $workbooks): BinaryFileResponse
    {
        $file = $workbooks->file($workbook) ?? abort(404);

        return response()->download($file['path'], $file['name'], [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
