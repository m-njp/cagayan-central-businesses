<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/** A Business Type or Focus Tag name: plain text, unique ignoring case, and with a usable slug for its code. */
final class CategoryName implements ValidationRule
{
    /** A renamed record keeps its code, so only a new one is checked against codes. */
    public function __construct(
        private string $table,
        private string $label,
        private ?int $ignoreId = null,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $name = (string) $value;
        if (preg_match('/[\p{Cc}\p{Cf}<>]/u', $name)) {
            $fail('Use plain text without markup or control characters.');

            return;
        }
        $code = Str::slug($name);
        if ($code === '') {
            $fail('Use letters or numbers in the name.');

            return;
        }
        $taken = DB::table($this->table)
            ->when($this->ignoreId, fn ($query) => $query->where('id', '!=', $this->ignoreId))
            ->where(fn ($query) => $query->whereRaw('lower(name) = lower(?)', [$name])
                ->when($this->ignoreId === null, fn ($inner) => $inner->orWhere('code', $code)))
            ->exists();
        if ($taken) {
            $fail("A {$this->label} with this name already exists.");
        }
    }
}
