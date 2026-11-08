<?php

namespace App\Http\Requests;

/** Verify or return pending population figures; a reason is required to return. */
final class ReviewPopulationRequest extends PopulationRequest
{
    public function authorize(): bool
    {
        return parent::authorize() && ($this->user()?->can('finance-review') ?? false);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'decision' => ['required', 'in:verified,rejected'],
            'figures' => ['required', 'array', 'min:1', 'max:'.config('cagayan.population.changes_max')],
            'figures.*' => ['required', 'uuid'],
            'note' => ['nullable', 'required_if:decision,rejected', 'prohibited_if:decision,verified', 'string', 'max:'.config('cagayan.population.note_max'), 'not_regex:/[\p{Cc}\p{Cf}<>]/u'],
        ];
    }
}
