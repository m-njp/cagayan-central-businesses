<?php

namespace App\Data;

use App\Enums\UserRole;

/**
 * One quick sign in button on the local login page. No account details reach the browser.
 */
final readonly class DemoLoginOption
{
    public function __construct(
        public string $role,
        public string $label,
    ) {}

    public static function from(UserRole $role): self
    {
        return new self(role: $role->value, label: $role->label());
    }
}
