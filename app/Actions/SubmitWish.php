<?php

namespace App\Actions;

use App\Models\BusinessType;
use App\Models\Municipality;
use App\Models\Wish;
use App\Services\WishAllowance;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

final class SubmitWish
{
    public function __construct(private WishAllowance $allowance) {}

    public function handle(Municipality $town, ?BusinessType $type, ?string $specificBusiness, ?string $reason, string $submitterHash, string $networkHash): Wish
    {
        // The lock stops two quick requests from both passing the checks.
        return Cache::lock('wish:'.$submitterHash, 10)->block(5, function () use ($town, $type, $specificBusiness, $reason, $submitterHash, $networkHash): Wish {
            $nextWishAt = $this->allowance->nextWishAt($submitterHash);

            if ($nextWishAt !== null) {
                throw ValidationException::withMessages([
                    'wish' => __('You can send another suggestion on :date.', ['date' => $this->allowance->forDisplay($nextWishAt)]),
                ]);
            }

            $isNewRespondent = ! Wish::query()->where('submitter_hash', $submitterHash)->exists();

            if ($isNewRespondent && ! $this->allowance->networkAcceptsNewRespondent($networkHash)) {
                throw ValidationException::withMessages([
                    'wish' => __('This network has sent many suggestions today. Try again tomorrow.'),
                ]);
            }

            return Wish::query()->create([
                'municipality_id' => $town->id,
                'business_type_id' => $type?->id,
                'specific_business' => $specificBusiness,
                'reason' => $reason,
                'submitter_hash' => $submitterHash,
                'network_hash' => $networkHash,
            ]);
        });
    }
}
