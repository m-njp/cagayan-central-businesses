<?php

namespace App\Http\Requests;

use App\Queries\WorkspaceMapQuery;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class CapitolDashboardRequest extends FormRequest
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
            'level' => ['nullable', Rule::in(['towns', 'barangays']), 'required_with:barangay'],
            'barangay' => ['nullable', 'string', 'regex:/^\d{10}$/'],
        ];
    }

    /** @return list<\Closure> */
    public function after(): array
    {
        return [function ($validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }
            if ($this->input('level') === 'barangays' && ! $this->filled('town')) {
                $validator->errors()->add('town', 'Choose a town before viewing barangays.');
            }
            if ($this->filled('barangay')) {
                $census = app(WorkspaceMapQuery::class)->census($this->string('town')->toString());
                if (! collect($census['features'] ?? [])->contains(fn (array $f) => $f['properties']['psgc_code'] === $this->input('barangay'))) {
                    $validator->errors()->add('barangay', 'Choose a barangay in the selected town.');
                }
            }
        }];
    }
}
