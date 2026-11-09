<?php

namespace App\Http\Requests;

use App\Models\ActivityLog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class WorkspaceListRequest extends FormRequest
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
            'category' => ['nullable', 'string', 'max:40', Rule::exists('business_types', 'code')],
            'event' => ['nullable', Rule::in(array_keys(ActivityLog::EVENTS))],
            'page' => ['nullable', 'integer', 'min:1', 'max:100000'],
        ];
    }
}
