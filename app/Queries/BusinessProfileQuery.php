<?php

namespace App\Queries;

use App\Data\BusinessAdminFields;
use App\Data\BusinessProfile;
use App\Enums\PermitStatus;
use App\Models\Business;
use App\Models\BusinessDocument;
use App\Models\BusinessPermit;
use App\Models\BusinessYear;
use App\Models\User;
use App\Services\OperatingMilestone;
use App\Services\RenewalStreak;
use Illuminate\Support\Facades\DB;

final class BusinessProfileQuery
{
    public function get(Business $business, User $viewer): BusinessProfile
    {
        $business->load(['businessType.category', 'barangay.municipality', 'permits', 'documents', 'years:id,business_id,year,status']);
        // Admin fields and documents: Admins, and Staff of the business's town (P8, D40).
        $seesAdmin = $viewer->can('edit-town', $business->barangay->municipality);
        $place = DB::table('businesses')->where('id', $business->id)
            ->selectRaw('ST_X(location) as lng, ST_Y(location) as lat')->first();
        $latest = $business->permits->sortByDesc('year')->first();
        $milestone = DB::scalar('select '.OperatingMilestone::SQL.' from businesses b where b.id = ?', [...OperatingMilestone::bindings(), $business->id]);
        $people = User::query()->whereKey(array_filter([$business->submitted_by, $business->reviewed_by]))->pluck('name', 'id');

        return new BusinessProfile(
            publicId: $business->public_id,
            name: $business->business_name,
            type: $business->businessType->name,
            typeCode: $business->businessType->code,
            category: ['code' => $business->businessType->category->code, 'name' => $business->businessType->category->name, 'color' => (int) $business->businessType->category->color],
            barangay: $business->barangay->name,
            barangayCode: $business->barangay->psgc_code,
            town: $business->barangay->municipality->name,
            townCode: $business->barangay->municipality->psgc_code,
            description: $business->description,
            yearEstablished: $business->year_established,
            isStorefront: $business->is_storefront,
            businessPhone: $business->business_phone,
            businessEmail: $business->business_email,
            birVerifiedOn: $business->bir_verified_on?->toDateString(),
            permitStatus: (string) DB::scalar('select '.PermitStatus::SQL
                .' from (select max(valid_until) as valid_until from business_permits where business_id = ?) permit',
                [...PermitStatus::bindings(), $business->id]),
            permitValidUntil: $latest?->valid_until->toDateString(),
            streak: (int) DB::scalar('select '.RenewalStreak::SQL.' from businesses b where b.id = ?', [...RenewalStreak::bindings(), $business->id]),
            milestone: $milestone === null ? null : (int) $milestone,
            location: $seesAdmin || $business->is_storefront ? ['lng' => (float) $place->lng, 'lat' => (float) $place->lat] : null,
            recordStatus: $business->record_status->value,
            submittedBy: $people[$business->submitted_by] ?? null,
            submittedAt: $business->submitted_at?->toIso8601String(),
            reviewedBy: $people[$business->reviewed_by] ?? null,
            reviewedAt: $business->reviewed_at?->toIso8601String(),
            admin: ! $seesAdmin ? null : new BusinessAdminFields(
                ownerName: $business->owner_name,
                ownerContact: $business->contact_number,
                streetAddress: $business->street_address,
                ownership: str($business->ownership_type->value)->replace('_', ' ')->title()->toString(),
                size: ucfirst($business->size_category->value),
                employees: $business->employee_count,
                registrationBody: $business->registration_body?->label(),
                registrationNumber: $business->registration_number,
                registrationValidUntil: $business->registration_valid_until?->toDateString(),
                // The nine-digit TIN keeps its last three digits; the branch code stays.
                tin: $business->maskedTin(),
                rdoCode: $business->rdo_code,
                reviewNote: $business->review_note,
            ),
            permits: array_values($business->permits->sortByDesc('year')->values()->map(fn (BusinessPermit $permit) => [
                'year' => $permit->year, 'number' => $seesAdmin ? $permit->permit_number : null,
                'validFrom' => $permit->valid_from->toDateString(), 'validUntil' => $permit->valid_until->toDateString(),
            ])->all()),
            documents: ! $seesAdmin ? [] : array_values($business->documents->sortBy(fn (BusinessDocument $document) => $document->kind->label())->values()
                ->map(fn (BusinessDocument $document) => [
                    'publicId' => $document->public_id, 'kind' => $document->kind->value, 'label' => $document->kind->label(),
                    'name' => $document->original_name, 'issuedOn' => $document->issued_on?->toDateString(),
                    'expiresOn' => $document->expires_on?->toDateString(),
                ])->all()),
            history: array_values($business->years->sortBy('year')
                ->map(fn (BusinessYear $year) => ['year' => $year->year, 'status' => $year->status->value])->all()),
            can: ['edit' => $viewer->can('edit-business', $business), 'review' => $viewer->can('review-business', $business)],
            reviewNoteMax: (int) config('cagayan.registry.review_note_max'),
        );
    }
}
