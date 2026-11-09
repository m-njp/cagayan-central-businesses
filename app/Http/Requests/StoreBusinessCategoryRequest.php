<?php

namespace App\Http\Requests;

use App\Rules\CategoryName;
use Illuminate\Foundation\Http\FormRequest;

final class StoreBusinessCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage-categories') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:'.config('cagayan.inputs.category_name_max'), new CategoryName('business_categories', 'Category')],
        ];
    }
}
