<?php

namespace App\Http\Controllers\Capitol;

use App\Actions\LogRecordView;
use App\Actions\SaveBusiness;
use App\Http\Controllers\Controller;
use App\Http\Requests\SaveBusinessRequest;
use App\Models\Business;
use App\Queries\BusinessFormQuery;
use App\Queries\BusinessProfileQuery;
use App\Services\BusinessVisibility;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

final class BusinessController extends Controller
{
    public function show(Request $request, Business $business, BusinessProfileQuery $query, LogRecordView $log): Response
    {
        abort_unless(BusinessVisibility::allows($business), 404);
        $log->handle($request->user(), $business);

        return Inertia::render('capitol/businesses/show', [
            'business' => $query->get($business, $request->user()),
            'breadcrumbs' => [
                ['label' => 'Businesses', 'href' => route('capitol.businesses.index', absolute: false)],
                ['label' => $business->business_name],
            ],
        ]);
    }

    public function create(Request $request, BusinessFormQuery $query): Response
    {
        Gate::authorize('add-business');

        return Inertia::render('capitol/businesses/form', [
            'form' => $query->get($request->user(), null),
            'breadcrumbs' => [
                ['label' => 'Businesses', 'href' => route('capitol.businesses.index', absolute: false)],
                ['label' => 'Add a Business'],
            ],
        ]);
    }

    public function store(SaveBusinessRequest $request, SaveBusiness $save): RedirectResponse
    {
        return $this->saved($request, $save->handle($request->user(), null, $request->validated()));
    }

    public function edit(Request $request, Business $business, BusinessFormQuery $query, LogRecordView $log): Response
    {
        Gate::authorize('edit-business', $business);
        // The form shows owner details, so opening it is a full record view (P9).
        $log->handle($request->user(), $business);

        return Inertia::render('capitol/businesses/form', [
            'form' => $query->get($request->user(), $business),
            'breadcrumbs' => [
                ['label' => 'Businesses', 'href' => route('capitol.businesses.index', absolute: false)],
                ['label' => $business->business_name, 'href' => route('capitol.businesses.show', $business, absolute: false)],
                ['label' => 'Edit'],
            ],
        ]);
    }

    public function update(SaveBusinessRequest $request, Business $business, SaveBusiness $save): RedirectResponse
    {
        return $this->saved($request, $save->handle($request->user(), $business, $request->validated()));
    }

    private function saved(SaveBusinessRequest $request, Business $business): RedirectResponse
    {
        Inertia::flash('toast', ['type' => 'success', 'message' => $request->boolean('submit')
            ? 'Submitted for review. It appears publicly once someone else verifies it.'
            : 'Draft saved. Submit it for review when it is complete.']);

        return to_route('capitol.businesses.show', $business);
    }
}
