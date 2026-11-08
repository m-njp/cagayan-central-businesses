<?php

namespace App\Http\Requests;

use App\Rules\OfficialPsaUrl;

/** The changed places of one census year, with one PSA source for all of them. */
final class SubmitPopulationRequest extends PopulationRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        $digits = strlen((string) config('cagayan.population.value_max'));

        return [
            ...parent::rules(),
            'submission' => ['required', 'uuid'],
            'source_url' => ['required', 'string', 'max:'.config('cagayan.population.source_url_max'), 'url:https', new OfficialPsaUrl],
            'reason' => ['nullable', 'string', 'max:'.config('cagayan.population.note_max'), 'not_regex:/[\p{Cc}\p{Cf}<>]/u'],
            'changes' => ['required', 'array', 'min:1', 'max:'.config('cagayan.population.changes_max')],
            'changes.*' => ['array:place,value,base'],
            'changes.*.place' => ['required', 'string', 'max:10', 'regex:/^(town|\d{10})$/'],
            'changes.*.value' => ['required', 'string', 'regex:/^\d{1,'.$digits.'}$/', 'integer', 'between:0,'.config('cagayan.population.value_max')],
            'changes.*.base' => ['nullable', 'string', 'regex:/^\d{1,'.$digits.'}$/'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'changes.*.value.regex' => 'Enter whole numbers without commas.',
            'changes.max' => 'Save at most :max changes at a time.',
            'changes.required' => 'Change at least one count before saving.',
        ];
    }

    /**
     * @return array{submission: string, source_url: string, reason: string|null, changes: list<array{place: string, value: int, base: int|null}>}
     */
    public function entries(): array
    {
        /** @var list<array{place: string, value: string, base?: string|null}> $changes */
        $changes = array_values((array) $this->input('changes'));

        return [
            'submission' => $this->string('submission')->toString(),
            'source_url' => $this->string('source_url')->toString(),
            'reason' => $this->filled('reason') ? $this->string('reason')->toString() : null,
            'changes' => array_map(fn (array $change) => [
                'place' => $change['place'], 'value' => (int) $change['value'],
                'base' => ($change['base'] ?? null) === null || $change['base'] === '' ? null : (int) $change['base'],
            ], $changes),
        ];
    }
}
