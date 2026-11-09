<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\IdentifiesWishSubmitter;
use App\Models\BusinessType;
use App\Models\Municipality;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class WishIndexRequest extends FormRequest
{
    use IdentifiesWishSubmitter;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * Unknown codes are ignored rather than rejected, so an old shared link still opens.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'town' => ['nullable', 'string', 'max:10'],
            'type' => ['nullable', 'string', 'max:64'],
        ];
    }

    public function town(): ?Municipality
    {
        return $this->filled('town')
            ? Municipality::query()->where('psgc_code', $this->string('town')->toString())->first()
            : null;
    }

    public function type(): ?BusinessType
    {
        return $this->filled('type')
            ? BusinessType::query()->where('code', $this->string('type')->toString())->first()
            : null;
    }
}
