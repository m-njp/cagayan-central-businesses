<?php

namespace App\Queries;

use App\Data\DemoLoginOption;
use App\Enums\UserRole;
use App\Models\User;

/**
 * Quick sign in exists only on a local machine (decisions.md, D33). Anywhere else
 * both methods return nothing, so there is nothing to show and nobody to sign in.
 */
final class DemoLoginQuery
{
    /** @return list<DemoLoginOption> */
    public function options(): array
    {
        return array_map(fn (string $role) => DemoLoginOption::from(UserRole::from($role)), array_keys($this->accounts()));
    }

    public function accountFor(UserRole $role): ?User
    {
        return $this->accounts()[$role->value] ?? null;
    }

    /**
     * The seeded demo accounts that can still enter the workspace, keyed by role value.
     *
     * @return array<string, User>
     */
    private function accounts(): array
    {
        if (! app()->isLocal()) {
            return [];
        }

        /** @var array<string, array{email: string|null}> $configured */
        $configured = config('cagayan.demo_accounts');
        $users = User::query()
            ->whereIn('email', array_filter(array_column($configured, 'email')))
            ->get()
            ->keyBy('email');

        $accounts = [];
        foreach ($configured as $roleValue => $account) {
            $user = $users->get($account['email'] ?? '');
            if ($user?->canEnterWorkspace() && $user->role?->value === $roleValue) {
                $accounts[$roleValue] = $user;
            }
        }

        return $accounts;
    }
}
