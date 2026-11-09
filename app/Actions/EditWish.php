<?php

namespace App\Actions;

use App\Models\BusinessType;
use App\Models\Municipality;
use App\Models\Wish;
use App\Services\WishAllowance;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class EditWish
{
    public function __construct(private WishAllowance $allowance) {}

    public function handle(Wish $wish, Municipality $town, ?BusinessType $type, ?string $specificBusiness, ?string $reason): void
    {
        Cache::lock('wish:'.$wish->submitter_hash, 10)->block(5, function () use ($wish, $town, $type, $specificBusiness, $reason): void {
            $nextEditAt = $this->allowance->nextEditAt($wish->submitter_hash);

            if ($nextEditAt !== null) {
                throw ValidationException::withMessages([
                    'wish' => __('You have used your :count edits for this week. You can edit again on :date.', [
                        'count' => config('cagayan.wishes.edits_per_window'),
                        'date' => $this->allowance->forDisplay($nextEditAt),
                    ]),
                ]);
            }

            DB::transaction(function () use ($wish, $town, $type, $specificBusiness, $reason): void {
                $wish->update([
                    'municipality_id' => $town->id,
                    'business_type_id' => $type?->id,
                    'specific_business' => $specificBusiness,
                    'reason' => $reason,
                ]);

                $wish->edits()->create(['edited_at' => now()]);
            });
        });
    }
}
