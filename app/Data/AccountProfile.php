<?php

namespace App\Data;

use App\Models\User;

final readonly class AccountProfile
{
    public function __construct(public string $displayName, public string $email, public string $role,
        public ?int $birthdayMonth, public ?int $birthdayDay, public int $displayNameMax) {}

    public static function from(User $user): self
    {
        return new self($user->name, $user->email, $user->role?->label() ?? '',
            $user->birthday_month, $user->birthday_day, (int) config('cagayan.inputs.display_name_max'));
    }
}
