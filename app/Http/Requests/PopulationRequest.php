<?php

namespace App\Http\Requests;

use App\Models\Municipality;
use Illuminate\Foundation\Http\FormRequest;

/** Admins open any town's population; Staff only their assigned towns (edit-town). */
class PopulationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $town = $this->route('town');

        return $town instanceof Municipality && ($this->user()?->can('edit-town', $town) ?? false);
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['census_year' => $this->route('year')]);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return ['census_year' => [$this->isMethod('GET') ? 'nullable' : 'required', 'integer', 'between:'.config('cagayan.population.first_year').','.now()->year]];
    }

    public function town(): Municipality
    {
        /** @var Municipality */
        return $this->route('town');
    }

    public function year(): ?int
    {
        return $this->route('year') === null ? null : (int) $this->route('year');
    }
}
