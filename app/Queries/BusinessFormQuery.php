<?php

namespace App\Queries;

use App\Data\BusinessForm;
use App\Data\SelectOption;
use App\Enums\DocumentKind;
use App\Enums\OwnershipType;
use App\Enums\RegistrationBody;
use App\Enums\SizeCategory;
use App\Models\Business;
use App\Models\BusinessCategory;
use App\Models\BusinessDocument;
use App\Models\BusinessType;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use stdClass;

final class BusinessFormQuery
{
    public function get(User $staff, ?Business $business): BusinessForm
    {
        $barangays = DB::table('barangays as g')->join('municipalities as m', 'm.id', '=', 'g.municipality_id')
            ->join('municipality_user as mu', fn ($join) => $join->on('mu.municipality_id', '=', 'm.id')->where('mu.user_id', $staff->id))
            ->whereNotNull('g.boundary')->orderBy('m.name')->orderBy('g.name')
            ->get(['g.psgc_code', 'g.name', 'm.psgc_code as town_code', 'm.name as town']);
        $business?->load(['businessType', 'barangay', 'documents', 'permits']);
        $permit = $business?->permits->sortByDesc('year')->first();
        $limits = config('cagayan.registry');

        return new BusinessForm(
            publicId: $business?->public_id,
            values: $business === null ? null : [
                ...$business->only([
                    'business_name', 'description', 'year_established', 'is_storefront', 'street_address', 'business_phone',
                    'business_email', 'owner_name', 'contact_number', 'employee_count', 'registration_number', 'rdo_code',
                ]),
                'business_type' => $business->businessType->code,
                'category' => BusinessCategory::query()->whereKey($business->businessType->business_category_id)->value('code'),
                'barangay' => $business->barangay->psgc_code,
                // The full TIN never leaves the server; a blank field keeps the stored one (P8).
                'tin' => '',
                'tin_masked' => $business->maskedTin(),
                'ownership_type' => $business->ownership_type->value,
                'size_category' => $business->size_category->value,
                'registration_body' => $business->registration_body?->value,
                'registration_valid_until' => $business->registration_valid_until?->toDateString(),
                'permit_number' => $permit?->permit_number,
                'permit_valid_from' => $permit?->valid_from->toDateString(),
                'permit_valid_until' => $permit?->valid_until->toDateString(),
                ...(array) DB::table('businesses')->where('id', $business->id)->selectRaw('ST_X(location) as lng, ST_Y(location) as lat')->first(),
            ],
            reviewNote: $business?->review_note,
            categories: array_values(BusinessCategory::query()->with(['businessTypes' => fn ($query) => $query->orderBy('name')])->orderBy('sort_order')->get()
                ->map(fn (BusinessCategory $category) => [
                    'code' => $category->code, 'name' => $category->name,
                    'types' => array_values($category->businessTypes->map(fn (BusinessType $type) => new SelectOption($type->code, $type->name))->all()),
                ])->all()),
            towns: array_values($barangays->unique('town_code')->map(fn (stdClass $row) => new SelectOption((string) $row->town_code, (string) $row->town))->all()),
            barangays: array_values($barangays->map(fn (stdClass $row) => [
                'value' => (string) $row->psgc_code, 'label' => (string) $row->name, 'town' => (string) $row->town_code,
            ])->all()),
            choices: [
                'ownership' => array_map(fn (OwnershipType $type) => new SelectOption($type->value, str($type->value)->replace('_', ' ')->title()->toString()), OwnershipType::cases()),
                'size' => array_map(fn (SizeCategory $size) => new SelectOption($size->value, ucfirst($size->value)), SizeCategory::cases()),
                'registration' => array_map(fn (RegistrationBody $body) => new SelectOption($body->value, $body->label()), RegistrationBody::cases()),
                'documents' => array_map(fn (DocumentKind $kind) => new SelectOption($kind->value, $kind->label()), DocumentKind::cases()),
            ],
            documents: array_values($business?->documents->map(fn (BusinessDocument $document) => ['label' => $document->kind->label(), 'name' => $document->original_name])->all() ?? []),
            limits: [
                'businessNameMax' => (int) config('cagayan.inputs.business_name_max'),
                'descriptionMax' => $limits['description_max'],
                'addressMax' => $limits['address_max'],
                'ownerNameMax' => $limits['owner_name_max'],
                'emailMax' => $limits['email_max'],
                'registrationNumberMax' => $limits['registration_number_max'],
                'permitNumberMax' => $limits['permit_number_max'],
                'documentsMax' => $limits['documents_max'],
                'documentMaxKb' => $limits['document_max_kb'],
            ],
        );
    }
}
