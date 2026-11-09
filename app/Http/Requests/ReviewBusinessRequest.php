<?php

namespace App\Http\Requests;

use App\Enums\RecordStatus;
use App\Models\Business;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ReviewBusinessRequest extends FormRequest
{
    public function authorize(): bool
    {
        $business = $this->route('business');

        return $business instanceof Business && ($this->user()?->can('review-business', $business) ?? false);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'decision' => ['required', Rule::in([RecordStatus::Approved->value, RecordStatus::Returned->value])],
            'note' => ['nullable', 'required_if:decision,returned', 'string', 'max:'.config('cagayan.registry.review_note_max')],
        ];
    }

    public function decision(): RecordStatus
    {
        return RecordStatus::from($this->string('decision')->toString());
    }

    public function note(): ?string
    {
        return $this->filled('note') ? $this->string('note')->trim()->toString() : null;
    }
}
