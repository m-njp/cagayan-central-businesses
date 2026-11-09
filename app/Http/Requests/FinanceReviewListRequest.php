<?php

namespace App\Http\Requests;

use App\Services\FinanceYears;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class FinanceReviewListRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('capitol') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'town' => ['nullable', 'string', 'regex:/^\d{10}$/', Rule::exists('municipalities', 'psgc_code')],
            'year' => ['nullable', 'integer', 'between:'.config('cagayan.finances.first_year').','.app(FinanceYears::class)->reportLastYear()],
            'status' => ['nullable', Rule::in(['pending'])],
            'page' => ['nullable', 'integer', 'min:1', 'max:100000'],
        ];
    }
}
