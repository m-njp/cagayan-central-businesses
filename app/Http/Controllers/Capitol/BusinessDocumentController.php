<?php

namespace App\Http\Controllers\Capitol;

use App\Actions\LogRecordView;
use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\BusinessDocument;
use App\Services\BusinessVisibility;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class BusinessDocumentController extends Controller
{
    /** Documents are Admin class: Admins, and Staff of the business's town (D40). */
    public function __invoke(Request $request, Business $business, BusinessDocument $document, LogRecordView $log): BinaryFileResponse
    {
        abort_unless(BusinessVisibility::allows($business), 404);
        Gate::authorize('edit-town', $business->load('barangay.municipality')->barangay->municipality);
        $log->handle($request->user(), $business);

        return response()->file($document->file() ?? abort(404), ['Content-Type' => $document->mime]);
    }
}
