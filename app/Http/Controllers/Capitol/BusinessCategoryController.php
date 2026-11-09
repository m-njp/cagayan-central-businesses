<?php

namespace App\Http\Controllers\Capitol;

use App\Actions\CreateBusinessCategory;
use App\Actions\CreateBusinessType;
use App\Actions\UpdateBusinessCategory;
use App\Actions\UpdateBusinessType;
use App\Http\Controllers\Controller;
use App\Http\Requests\SaveBusinessTypeRequest;
use App\Http\Requests\StoreBusinessCategoryRequest;
use App\Http\Requests\UpdateBusinessCategoryRequest;
use App\Models\BusinessCategory;
use App\Models\BusinessType;
use App\Queries\BusinessCategoriesQuery;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final class BusinessCategoryController extends Controller
{
    public function index(BusinessCategoriesQuery $query): Response
    {
        return Inertia::render('capitol/business-categories', ['categories' => $query->get()]);
    }

    public function storeCategory(StoreBusinessCategoryRequest $request, CreateBusinessCategory $create): RedirectResponse
    {
        $category = $create->handle($request->user(), $request->string('name')->toString());

        return $this->saved("{$category->name} added.");
    }

    public function updateCategory(UpdateBusinessCategoryRequest $request, BusinessCategory $category, UpdateBusinessCategory $update): RedirectResponse
    {
        $update->handle($request->user(), $category, $request->string('name')->toString(), $request->integer('color'));

        return $this->saved("{$request->string('name')} saved.");
    }

    public function storeType(SaveBusinessTypeRequest $request, CreateBusinessType $create): RedirectResponse
    {
        $type = $create->handle($request->user(), $request->string('name')->toString(), $request->string('industry')->toString(), $request->string('category')->toString());

        return $this->saved("{$type->name} added.");
    }

    public function updateType(SaveBusinessTypeRequest $request, BusinessType $type, UpdateBusinessType $update): RedirectResponse
    {
        $update->handle($request->user(), $type, $request->string('name')->toString(), $request->string('industry')->toString(), $request->string('category')->toString());

        return $this->saved("{$request->string('name')} saved.");
    }

    private function saved(string $message): RedirectResponse
    {
        Inertia::flash('toast', ['type' => 'success', 'message' => $message.' It shows in the Business Directory and the Wish Form.']);

        return back();
    }
}
