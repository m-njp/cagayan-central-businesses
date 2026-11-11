<?php

namespace App\Http\Controllers;

use App\Services\PublicFinanceArchive;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class PublicFinanceArchiveController extends Controller
{
    public function __invoke(PublicFinanceArchive $archive): BinaryFileResponse
    {
        try {
            $path = $archive->build();
        } catch (RuntimeException $error) {
            report($error);
            abort(503, 'The report archive could not be prepared.');
        }

        return response()->download($path, 'cagayan-coa-reports.zip', ['Content-Type' => 'application/zip'])
            ->deleteFileAfterSend();
    }
}
