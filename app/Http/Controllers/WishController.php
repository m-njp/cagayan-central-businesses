<?php

namespace App\Http\Controllers;

use App\Actions\EditWish;
use App\Actions\SubmitWish;
use App\Http\Requests\StoreWishRequest;
use App\Http\Requests\UpdateWishRequest;
use App\Http\Requests\WishIndexRequest;
use App\Models\Wish;
use App\Queries\WishIndexQuery;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final class WishController extends Controller
{
    public function index(WishIndexRequest $request, WishIndexQuery $query): Response
    {
        return Inertia::render('public/wishes/index', [
            'page' => fn () => $query->get($request->submitterHash(), $request->town(), $request->type()),
            'tally' => Inertia::optional(fn () => $query->selectionTally($request->town(), $request->type())),
        ]);
    }

    public function store(StoreWishRequest $request, SubmitWish $submitWish): RedirectResponse
    {
        $submitWish->handle($request->town(), $request->type(), $request->specificBusiness(), $request->reason(), $request->submitterHashOrStart(), $request->networkHash());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Suggestion sent.')]);

        return to_route('public.wishes.index', ['town' => $request->string('town')->toString(), 'type' => $request->input('type') ?: null]);
    }

    public function update(UpdateWishRequest $request, Wish $wish, EditWish $editWish): RedirectResponse
    {
        $editWish->handle($wish, $request->town(), $request->type(), $request->specificBusiness(), $request->reason());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Suggestion updated.')]);

        return to_route('public.wishes.index', ['town' => $request->string('town')->toString(), 'type' => $request->input('type') ?: null]);
    }
}
