<?php

namespace App\Http\Requests\Concerns;

use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;

/**
 * A respondent is an anonymous browser token in an encrypted, signed, http-only
 * cookie, stored only as its SHA-256 hash, plus a keyed hash of the network
 * address that changes every day (decisions.md, D21). No raw address, name, or
 * contact is ever stored (P12).
 */
trait IdentifiesWishSubmitter
{
    public const string SUBMITTER_COOKIE = 'wish_submitter';

    /**
     * HMAC of the address and today's date with the app key. It cannot be reversed
     * without the key, and the same address gives a different hash each day.
     */
    public function networkHash(): string
    {
        return hash_hmac('sha256', 'wish-network|'.now()->toDateString().'|'.$this->ip(), (string) config('app.key'));
    }

    public function submitterHash(): ?string
    {
        $token = $this->cookie(self::SUBMITTER_COOKIE);

        return is_string($token) && $token !== '' ? hash('sha256', $token) : null;
    }

    /**
     * The hash for this browser, issuing a new cookie on its first wish.
     * Visitors who only read the page are never given one.
     */
    public function submitterHashOrStart(): string
    {
        $hash = $this->submitterHash();

        if ($hash !== null) {
            return $hash;
        }

        $token = Str::random(40);

        Cookie::queue(Cookie::make(
            name: self::SUBMITTER_COOKIE,
            value: $token,
            minutes: (int) config('cagayan.wishes.cookie_days') * 24 * 60,
            httpOnly: true,
            sameSite: 'lax',
        ));

        return hash('sha256', $token);
    }
}
