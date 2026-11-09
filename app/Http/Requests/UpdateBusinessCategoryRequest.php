<?php

namespace App\Http\Requests;

use App\Models\BusinessCategory;
use App\Rules\CategoryName;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateBusinessCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage-categories') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        /** @var BusinessCategory $category */
        $category = $this->route('category');

        return [
            'name' => ['required', 'string', 'max:'.config('cagayan.inputs.category_name_max'), new CategoryName('business_categories', 'Category', $category->id)],
            'color' => ['required', 'integer', 'between:1,'.config('cagayan.categories.colors')],
        ];
    }
}
