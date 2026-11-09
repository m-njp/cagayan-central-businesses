<?php

namespace App\Data;

use App\Models\User;

/**
 * The only fields of the logged-in account that reach the browser (P10).
 */
final readonly class SessionUser
{
    public function __construct(
        public string $name,
    ) {}

    public static function from(User $user): self
    {
        return new self(name: $user->name);
    }
}
