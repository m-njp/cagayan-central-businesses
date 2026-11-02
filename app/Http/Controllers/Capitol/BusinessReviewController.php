<?php

namespace App\Http\Controllers\Capitol;

use App\Actions\ReviewBusiness;
use App\Enums\RecordStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\ReviewBusinessRequest;
use App\Models\Business;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

final class BusinessReviewController extends Controller
{
    public function __invoke(ReviewBusinessRequest $request, Business $business, ReviewBusiness $review): RedirectResponse
    {
        $review->handle($request->user(), $business, $request->decision(), $request->note());
        Inertia::flash('toast', ['type' => 'success', 'message' => $request->decision() === RecordStatus::Approved
            ? 'Business verified and published.' : 'Business returned for changes.']);

        return to_route('capitol.businesses.show', $business);
    }
}
