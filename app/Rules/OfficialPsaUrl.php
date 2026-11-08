<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/** A census figure cites the Philippine Statistics Authority over HTTPS, never a lookalike host or credentials. */
final class OfficialPsaUrl implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $parts = is_string($value) ? parse_url($value) : false;
        $host = strtolower($parts['host'] ?? '');
        if ($parts === false || ($parts['scheme'] ?? '') !== 'https'
            || ($host !== 'psa.gov.ph' && ! str_ends_with($host, '.psa.gov.ph'))
            || isset($parts['user']) || isset($parts['pass'])
            || (isset($parts['port']) && $parts['port'] !== 443)) {
            $fail('Use an official HTTPS link on psa.gov.ph.');
        }
    }
}
