<?php

namespace App\Services;

use Carbon\CarbonInterface;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;

/**
 * Bot check without a third party. The page carries an encrypted, tamper-proof
 * timestamp. A form sent back faster than a person can fill it, or long after
 * the page was loaded, is rejected.
 */
final class FormTimer
{
    public function issue(?CarbonInterface $at = null): string
    {
        return Crypt::encryptString((string) ($at ?? now())->getTimestamp());
    }

    public function isHumanPace(string $token): bool
    {
        try {
            $issuedAt = (int) Crypt::decryptString($token);
        } catch (DecryptException) {
            return false;
        }

        $elapsed = now()->getTimestamp() - $issuedAt;

        return $elapsed >= (int) config('cagayan.wishes.min_fill_seconds')
            && $elapsed <= (int) config('cagayan.wishes.max_fill_minutes') * 60;
    }
}
