<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

final class UpdateProfileRequest extends FormRequest
{
    protected $redirectRoute = 'capitol.profile.show';

    public function authorize(): bool
    {
        return $this->user()?->can('capitol') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'display_name' => ['required', 'string', 'max:'.config('cagayan.inputs.display_name_max'), 'not_regex:/[\p{Cc}\p{Cf}<>]/u'],
            'birthday_month' => ['nullable', 'required_with:birthday_day', 'integer', 'between:1,12'],
            'birthday_day' => ['nullable', 'required_with:birthday_month', 'integer', 'between:1,31'],
            'role' => ['prohibited'], 'email' => ['prohibited'], 'is_active' => ['prohibited'],
            'auth_version' => ['prohibited'], 'public_id' => ['prohibited'], 'user_id' => ['prohibited'],
        ];
    }

    /** @return list<\Closure(Validator): void> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }
            if ($this->filled('birthday_month') && ! checkdate($this->integer('birthday_month'), $this->integer('birthday_day'), 2000)) {
                $validator->errors()->add('birthday_day', 'Choose a valid day for this month.');
            }
        }];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return ['display_name.not_regex' => 'Use plain text without markup or control characters.'];
    }
}
