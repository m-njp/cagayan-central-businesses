<?php

namespace App\Http\Requests;

use App\Models\Municipality;
use App\Services\FinanceYears;
use Illuminate\Foundation\Http\FormRequest;

/** Admins open any town; Staff only their assigned towns (edit-town). */
class FinanceReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        $town = $this->route('town');

        return $town instanceof Municipality && ($this->user()?->can('edit-town', $town) ?? false);
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['report_year' => $this->route('year')]);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'report_year' => ['required', 'integer', 'between:'.config('cagayan.finances.first_year').','.app(FinanceYears::class)->reportLastYear()],
        ];
    }

    public function town(): Municipality
    {
        /** @var Municipality */
        return $this->route('town');
    }

    public function reportYear(): int
    {
        return (int) $this->route('year');
    }
}
