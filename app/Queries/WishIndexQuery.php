<?php

namespace App\Queries;

use App\Data\MyWish;
use App\Data\SelectOption;
use App\Data\WishPage;
use App\Data\WishTally;
use App\Models\BusinessType;
use App\Models\Municipality;
use App\Models\Wish;
use App\Services\FormTimer;
use App\Services\WishAllowance;

final class WishIndexQuery
{
    public function __construct(
        private WishAllowance $allowance,
        private FormTimer $formTimer,
    ) {}

    public function get(?string $submitterHash, ?Municipality $town, ?BusinessType $type): WishPage
    {
        $towns = Municipality::query()->orderBy('name')->get(['psgc_code', 'name', 'is_connected']);
        $myWish = $submitterHash ? $this->myWish($submitterHash) : null;
        $specificBusiness = $town && $myWish?->townCode === $town->psgc_code
            && $myWish->specificBusiness !== null
            && $myWish->typeCode === $type?->code
                ? $myWish->specificBusiness
                : null;

        return new WishPage(
            towns: array_values($towns->map(fn (Municipality $town) => new SelectOption($town->psgc_code, $town->name))->all()),
            types: array_values(BusinessType::query()->orderBy('sort_order')->get(['code', 'name'])
                ->map(fn (BusinessType $type) => new SelectOption($type->code, $type->name))->all()),
            defaultTown: $towns->firstWhere('is_connected', true)?->psgc_code,
            tally: $town && ($type || $specificBusiness)
                ? $this->tally($town, $type, $specificBusiness)
                : null,
            myWish: $myWish,
            nextWishAt: $submitterHash ? $this->allowance->nextWishAt($submitterHash)?->toIso8601String() : null,
            cooldownDays: (int) config('cagayan.wishes.cooldown_days'),
            editsPerWindow: (int) config('cagayan.wishes.edits_per_window'),
            editWindowDays: (int) config('cagayan.wishes.edit_window_days'),
            reasonMax: (int) config('cagayan.wishes.reason_max'),
            specificBusinessMax: (int) config('cagayan.wishes.specific_business_max'),
            formToken: $this->formTimer->issue(),
        );
    }

    public function selectionTally(?Municipality $town, ?BusinessType $type): ?WishTally
    {
        return $town && $type ? $this->tally($town, $type, null) : null;
    }

    private function tally(Municipality $town, ?BusinessType $type, ?string $specificBusiness): WishTally
    {
        $query = Wish::query()->where('municipality_id', $town->id);

        if ($type) {
            $query->where('business_type_id', $type->id);
        }

        if ($specificBusiness) {
            $query->whereRaw('LOWER(specific_business) = ?', [mb_strtolower($specificBusiness)]);
        }

        return new WishTally(
            townName: $town->name,
            typeName: $type?->name,
            specificBusiness: $specificBusiness,
            wishCount: $query->count(),
            // Registered businesses arrive with roadmap F3 and F4.
            registeredCount: null,
        );
    }

    private function myWish(string $submitterHash): ?MyWish
    {
        $wish = Wish::query()
            ->with(['municipality:id,psgc_code,name', 'businessType:id,code,name'])
            ->where('submitter_hash', $submitterHash)
            ->latest()
            ->first();

        if ($wish === null) {
            return null;
        }

        return new MyWish(
            id: $wish->id,
            townCode: $wish->municipality->psgc_code,
            townName: $wish->municipality->name,
            typeCode: $wish->businessType?->code,
            typeName: $wish->businessType?->name,
            specificBusiness: $wish->specific_business,
            reason: $wish->reason,
            editsLeft: $this->allowance->editsLeft($submitterHash),
            nextEditAt: $this->allowance->nextEditAt($submitterHash)?->toIso8601String(),
        );
    }
}
