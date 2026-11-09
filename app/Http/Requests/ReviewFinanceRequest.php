<?php

namespace App\Http\Requests;

final class ReviewFinanceRequest extends FinanceReportRequest
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
            'figures' => ['required', 'array', 'min:1', 'max:'.config('cagayan.finances.review_batch_max')],
            'figures.*' => ['required', 'uuid', 'distinct'],
            'note' => ['nullable', 'required_if:decision,rejected', 'prohibited_if:decision,verified', 'string', 'max:'.config('cagayan.finances.note_max'), 'not_regex:/[\p{Cc}\p{Cf}<>]/u'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'note.required_if' => 'Say why the figure is rejected.',
            'note.prohibited_if' => 'A sign-off records the reviewer, not a note.',
        ];
    }

    /** @return list<string> */
    public function figureIds(): array
    {
        return array_values(array_map('strval', (array) $this->input('figures')));
    }
}
