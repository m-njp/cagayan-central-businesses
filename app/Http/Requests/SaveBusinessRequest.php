<?php

namespace App\Http\Requests;

use App\Enums\DocumentKind;
use App\Enums\OwnershipType;
use App\Enums\RegistrationBody;
use App\Enums\SizeCategory;
use App\Models\Business;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/** Creates a registry record, or updates the one in the route (D40). Staff only, within their towns. */
final class SaveBusinessRequest extends FormRequest
{
    private const string PLAIN = 'not_regex:/[\p{Cc}\p{Cf}<>]/u';

    public function authorize(): bool
    {
        $business = $this->route('business');

        return $business instanceof Business
            ? $this->user()?->can('edit-business', $business) ?? false
            : $this->user()?->can('add-business') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $limits = config('cagayan.registry');
        $text = fn (int $max) => ['string', 'max:'.$max, self::PLAIN];

        return [
            'submit' => ['required', 'boolean'],
            'business_name' => ['required', ...$text((int) config('cagayan.inputs.business_name_max'))],
            'business_type' => ['required', 'string', Rule::exists('business_types', 'code')],
            'description' => ['required', ...$text($limits['description_max'])],
            'year_established' => ['required', 'integer', 'min:1900', 'max:'.now()->year],
            'is_storefront' => ['required', 'boolean'],
            'barangay' => ['required', 'string', Rule::exists('barangays', 'psgc_code')
                ->whereIn('municipality_id', $this->user()?->assignedTowns()->pluck('municipalities.id')->all() ?? [])],
            'street_address' => ['required', ...$text($limits['address_max'])],
            'lng' => ['required', 'numeric', 'between:-180,180'],
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'business_phone' => ['nullable', 'string', 'max:20', 'regex:'.$limits['phone_pattern']],
            'business_email' => ['nullable', 'email', 'max:'.$limits['email_max']],
            'owner_name' => ['required', ...$text($limits['owner_name_max'])],
            'contact_number' => ['required', 'string', 'max:20', 'regex:'.$limits['phone_pattern']],
            'ownership_type' => ['required', Rule::enum(OwnershipType::class)],
            'size_category' => ['required', Rule::enum(SizeCategory::class)],
            'employee_count' => ['required', 'integer', 'min:0', 'max:100000'],
            'registration_body' => ['required', Rule::enum(RegistrationBody::class)],
            'registration_number' => ['required', ...$text($limits['registration_number_max'])],
            'registration_valid_until' => ['nullable', 'date'],
            // Editing shows the TIN masked, so a blank field keeps the stored one.
            'tin' => [$this->route('business') instanceof Business ? 'nullable' : 'required', 'string', 'regex:'.$limits['tin_pattern']],
            'rdo_code' => ['required', 'string', 'regex:'.$limits['rdo_pattern']],
            'permit_number' => ['nullable', 'required_with:permit_valid_until', ...$text($limits['permit_number_max'])],
            'permit_valid_from' => ['nullable', 'required_with:permit_number', 'date'],
            'permit_valid_until' => ['nullable', 'required_with:permit_number', 'date', 'after_or_equal:permit_valid_from'],
            'documents' => ['array', 'max:'.$limits['documents_max']],
            'documents.*.kind' => ['required', Rule::enum(DocumentKind::class)],
            'documents.*.file' => ['required', 'file', 'mimes:'.implode(',', $limits['document_mimes']), 'max:'.$limits['document_max_kb']],
            'documents.*.issued_on' => ['nullable', 'date'],
            'documents.*.expires_on' => ['nullable', 'date'],
        ];
    }

    /** @return list<callable(Validator): void> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->hasAny(['barangay', 'lng', 'lat'])) {
                return;
            }
            $inside = DB::table('barangays')->where('psgc_code', $this->input('barangay'))
                ->whereRaw('ST_Covers(boundary, ST_SetSRID(ST_MakePoint(?, ?), 4326))', [(float) $this->input('lng'), (float) $this->input('lat')])
                ->exists();
            if (! $inside) {
                $validator->errors()->add('lng', 'Pin is outside the selected barangay.');
            }
        }];
    }
}
