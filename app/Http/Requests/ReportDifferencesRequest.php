<?php

namespace App\Http\Requests;

use App\Services\FinanceYears;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ReportDifferencesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('capitol') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'family' => ['nullable', Rule::in(['report', 'between_reports', 'reading'])],
            'town' => ['nullable', 'required_with:year', 'string', 'regex:/^\d{10}$/', Rule::exists('municipalities', 'psgc_code')],
            'year' => ['nullable', 'required_with:town', 'integer', 'between:'.config('cagayan.finances.first_year').','.app(FinanceYears::class)->reportLastYear()],
        ];
    }
}
