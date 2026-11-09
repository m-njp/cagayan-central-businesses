<?php

namespace App\Http\Requests;

use App\Models\BusinessType;
use App\Rules\CategoryName;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/** Creates a Business Type, or updates the one in the route. */
final class SaveBusinessTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage-categories') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        /** @var BusinessType|null $type */
        $type = $this->route('type');

        return [
            'name' => ['required', 'string', 'max:'.config('cagayan.inputs.business_type_name_max'), new CategoryName('business_types', 'Business Type', $type?->id)],
            'industry' => ['required', 'string', Rule::exists('business_types', 'psic_section')],
            'category' => ['required', 'string', Rule::exists('business_categories', 'code')],
        ];
    }

    /** @return list<callable(Validator): void> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($this->route('type') === null && DB::table('business_types')->count() >= (int) config('cagayan.categories.max_types')) {
                $validator->errors()->add('name', 'The Business Type limit has been reached.');
            }
        }];
    }
}
