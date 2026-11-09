<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\IdentifiesWishSubmitter;
use App\Models\BusinessType;
use App\Models\Municipality;
use App\Services\FormTimer;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreWishRequest extends FormRequest
{
    use IdentifiesWishSubmitter;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'town' => ['required', 'string', 'max:10', 'exists:municipalities,psgc_code'],
            'type' => ['nullable', 'required_without:specific_business', 'string', 'max:64', 'exists:business_types,code'],
            'specific_business' => [
                'nullable',
                'string',
                'max:'.config('cagayan.wishes.specific_business_max'),
                'not_regex:/(https?:\/\/|www\.|\.(com|net|org|ph|xyz|ru)\b)/i',
            ],
            'reason' => [
                'nullable',
                'string',
                'max:'.config('cagayan.wishes.reason_max'),
                // Links are the usual payload of form spam, and a reason never needs one.
                'not_regex:/(https?:\/\/|www\.|\.(com|net|org|ph|xyz|ru)\b)/i',
            ],
            // Hidden from people, so only bots fill it in.
            'website' => ['prohibited'],
            'form_token' => [
                'required',
                'string',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (! is_string($value) || ! app(FormTimer::class)->isHumanPace($value)) {
                        $fail(__('The form expired or was sent too quickly. Reload the page and try again.'));
                    }
                },
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'town.required' => __('Choose a town.'),
            'town.exists' => __('Choose a town from the list.'),
            'type.required' => __('Choose a business type.'),
            'type.exists' => __('Choose a business type from the list.'),
            'type.required_without' => __('Choose a business type or name a specific business.'),
            'specific_business.max' => __('Keep the specific business name under :max characters.'),
            'specific_business.not_regex' => __('Enter a business name, not a web address.'),
            'reason.max' => __('Keep the reason under :max characters.'),
            'reason.not_regex' => __('Remove links and web addresses from the reason.'),
            'website.prohibited' => __('Leave the hidden field empty.'),
            'form_token.required' => __('The form expired or was sent too quickly. Reload the page and try again.'),
        ];
    }

    /**
     * Plain text only: tags and control characters are removed and spaces collapsed
     * before the length and link rules run.
     */
    protected function prepareForValidation(): void
    {
        $clean = [];

        foreach (['reason', 'specific_business'] as $field) {
            if (! is_string($this->input($field))) {
                continue;
            }

            $value = strip_tags($this->input($field));
            $value = (string) preg_replace('/(?!\n)\p{C}/u', '', $value);
            $value = (string) preg_replace('/[^\S\n]+/u', ' ', $value);
            $value = (string) preg_replace('/\n{3,}/', "\n\n", $value);
            $clean[$field] = trim($value);
        }

        $this->merge($clean);
    }

    public function town(): Municipality
    {
        return Municipality::query()->where('psgc_code', $this->string('town')->toString())->firstOrFail();
    }

    public function type(): ?BusinessType
    {
        return $this->filled('type')
            ? BusinessType::query()->where('code', $this->string('type')->toString())->firstOrFail()
            : null;
    }

    public function specificBusiness(): ?string
    {
        $specificBusiness = $this->string('specific_business')->toString();

        return $specificBusiness === '' ? null : $specificBusiness;
    }

    public function reason(): ?string
    {
        $reason = $this->string('reason')->toString();

        return $reason === '' ? null : $reason;
    }
}
