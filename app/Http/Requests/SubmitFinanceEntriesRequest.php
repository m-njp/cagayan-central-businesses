<?php

namespace App\Http\Requests;

use App\Enums\FinanceBasis;
use App\Models\FinanceLine;
use App\Queries\FinanceReportQuery;
use App\Rules\OfficialCoaUrl;
use App\Services\FinanceYears;
use Closure;
use Illuminate\Validation\Rule;

/** The changed cells of one report grid, with one source for all of them. Staff only for assigned towns (edit-town). */
final class SubmitFinanceEntriesRequest extends FinanceReportRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        $years = 'between:'.config('cagayan.finances.first_year').','.min($this->reportYear(), app(FinanceYears::class)->reportLastYear());
        $groups = FinanceLine::query()->enterable()->pluck('group', 'code');
        $digits = strlen(explode('.', (string) config('cagayan.finances.value_max'))[0]);
        $text = ['nullable', 'string', 'max:'.config('cagayan.finances.note_max'), 'not_regex:/[\p{Cc}\p{Cf}<>]/u'];

        return [
            ...parent::rules(),
            'submission' => ['required', 'uuid'],
            'document_kind' => ['nullable', Rule::in(array_keys(FinanceReportQuery::KINDS)),
                Rule::exists('finance_documents', 'kind')->where('municipality_id', $this->town()->id)
                    ->where('fiscal_year', $this->reportYear())->where('availability', 'available')],
            'source_url' => ['nullable', 'required_without:document_kind', 'string', 'max:'.config('cagayan.finances.source_url_max'), 'url:https', new OfficialCoaUrl],
            'page' => ['nullable', 'integer', 'between:1,999'],
            'reason' => $text,
            'note' => ['prohibited'],
            'changes' => ['required', 'array', 'min:1', 'max:'.config('cagayan.finances.entry_changes_max')],
            'changes.*' => ['array:line_code,fiscal_year,basis,value,base'],
            'changes.*.line_code' => ['required', 'string', 'max:80', Rule::in($groups->keys()->all())],
            'changes.*.fiscal_year' => ['required', 'integer', $years],
            'changes.*.basis' => ['required', Rule::enum(FinanceBasis::class),
                function (string $attribute, mixed $value, Closure $fail) use ($groups): void {
                    $line = $this->input(str_replace('.basis', '.line_code', $attribute));
                    $group = is_string($line) ? $groups->get($line) : null;
                    if ($group !== null && ! in_array($value, config("cagayan.finances.groups.{$group}.bases", ['actual']), true)) {
                        $fail('Choose an allowed basis for this financial line.');
                    }
                }],
            'changes.*.value' => ['required', 'string', 'regex:/^\d{1,'.$digits.'}(\.\d{1,2})?$/', 'numeric', 'between:0,'.config('cagayan.finances.value_max')],
            'changes.*.base' => ['nullable', 'string', 'max:30'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'changes.*.value.regex' => 'Enter pesos as digits with up to two decimals, without commas.',
            'changes.*.fiscal_year.between' => 'Choose a fiscal year within the supported range and no later than the report.',
            'changes.*.line_code.in' => 'That line cannot be entered.',
            'changes.max' => 'Save at most :max changes at a time.',
            'changes.required' => 'Change at least one amount before saving.',
            'document_kind.exists' => 'That report is not on file for this town and year.',
            'source_url.required_without' => 'Choose the report you read, or give its COA link.',
            'note.prohibited' => 'A note is not part of figure entry.',
        ];
    }

    /**
     * @return array{submission: string, document_kind: string|null, source_url: string|null, page: int|null, reason: string|null, changes: list<array{line_code: string, fiscal_year: int, basis: string, value: string, base: string|null}>}
     */
    public function entries(): array
    {
        /** @var list<array{line_code: string, fiscal_year: int|string, basis: string, value: string, base?: string|null}> $changes */
        $changes = array_values((array) $this->input('changes'));

        return [
            'submission' => $this->string('submission')->toString(),
            'document_kind' => $this->filled('document_kind') ? $this->string('document_kind')->toString() : null,
            'source_url' => $this->filled('source_url') ? $this->string('source_url')->toString() : null,
            'page' => $this->filled('page') ? $this->integer('page') : null,
            'reason' => $this->filled('reason') ? $this->string('reason')->toString() : null,
            'changes' => array_map(fn (array $change) => [
                'line_code' => $change['line_code'], 'fiscal_year' => (int) $change['fiscal_year'], 'basis' => $change['basis'],
                'value' => $change['value'], 'base' => $change['base'] ?? null,
            ], $changes),
        ];
    }
}
