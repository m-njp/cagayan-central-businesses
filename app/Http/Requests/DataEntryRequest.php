<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class DataEntryRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return ['page' => ['nullable', 'integer', 'min:1', 'max:100000']];
    }

    public function page(): int
    {
        return $this->integer('page', 1);
    }
}
