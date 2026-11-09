<?php

namespace App\Http\Requests;

use App\Enums\BusinessStatus;
use App\Enums\RecordStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class BusinessDirectoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('capitol') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:60'],
            'record' => ['nullable', Rule::enum(RecordStatus::class)],
            'barangay' => ['nullable', 'string', 'size:10', Rule::exists('barangays', 'psgc_code')],
            'category' => ['nullable', 'string', 'max:60', Rule::exists('business_categories', 'code')],
            'type' => ['nullable', 'string', 'max:60', Rule::exists('business_types', 'code')],
            'industry' => ['nullable', 'string', 'size:1', Rule::exists('business_types', 'psic_section')],
            'status' => ['nullable', Rule::enum(BusinessStatus::class)],
            'sort' => ['nullable', Rule::in(['name', 'type', 'established'])],
            'dir' => ['nullable', Rule::in(['asc', 'desc'])],
            'page' => ['nullable', 'integer', 'min:1', 'max:100000'],
        ];
    }

    /** @return array{q: ?string, record: ?string, barangay: ?string, category: ?string, type: ?string, industry: ?string, status: ?string, sort: string, dir: string} */
    public function filters(): array
    {
        return [
            'q' => $this->filled('q') ? $this->string('q')->trim()->toString() : null,
            'record' => $this->input('record') ?: null,
            'barangay' => $this->input('barangay') ?: null,
            'category' => $this->input('category') ?: null,
            'type' => $this->input('type') ?: null,
            'industry' => $this->input('industry') ?: null,
            'status' => $this->input('status') ?: null,
            'sort' => (string) $this->input('sort', 'name'),
            'dir' => (string) $this->input('dir', 'asc'),
        ];
    }
}
