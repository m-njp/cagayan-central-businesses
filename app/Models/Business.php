<?php

namespace App\Models;

use App\Enums\OwnershipType;
use App\Enums\RecordStatus;
use App\Enums\RegistrationBody;
use App\Enums\SizeCategory;
use Database\Factories\BusinessFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * One registered business. Business phone and email are public; owner, owner contact, address, ownership, size,
 * employees, registration numbers and TIN are admin fields (P8). Only approved records are public (P15).
 *
 * @property int $id
 * @property string $public_id
 * @property string $business_name
 * @property int $business_type_id
 * @property string $description
 * @property int $year_established
 * @property int $barangay_id
 * @property string $owner_name
 * @property string $contact_number
 * @property OwnershipType $ownership_type
 * @property SizeCategory $size_category
 * @property int $employee_count
 * @property bool $is_simulated
 * @property string|null $source
 * @property string|null $street_address
 * @property string|null $business_phone
 * @property string|null $business_email
 * @property bool $is_storefront
 * @property RegistrationBody|null $registration_body
 * @property string|null $registration_number
 * @property Carbon|null $registration_valid_until
 * @property string|null $tin
 * @property string|null $rdo_code
 * @property RecordStatus $record_status
 * @property int|null $submitted_by
 * @property Carbon|null $submitted_at
 * @property int|null $reviewed_by
 * @property Carbon|null $reviewed_at
 * @property string|null $review_note
 * @property Carbon|null $bir_verified_on
 */
#[Fillable([
    'public_id', 'business_name', 'business_type_id', 'description', 'year_established', 'barangay_id', 'location',
    'owner_name', 'contact_number', 'ownership_type', 'size_category', 'employee_count', 'is_simulated', 'source',
    'street_address', 'business_phone', 'business_email', 'is_storefront', 'registration_body', 'registration_number',
    'registration_valid_until', 'tin', 'rdo_code',
])]
#[Hidden(['location'])]
class Business extends Model
{
    /** @use HasFactory<BusinessFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<BusinessType, $this>
     */
    public function businessType(): BelongsTo
    {
        return $this->belongsTo(BusinessType::class);
    }

    /**
     * @return BelongsTo<Barangay, $this>
     */
    public function barangay(): BelongsTo
    {
        return $this->belongsTo(Barangay::class);
    }

    /**
     * @return HasMany<BusinessYear, $this>
     */
    public function years(): HasMany
    {
        return $this->hasMany(BusinessYear::class);
    }

    /** The TIN as Capitol screens show it: the first six digits hidden, the last three and the branch code kept (P8). */
    public function maskedTin(): ?string
    {
        return $this->tin === null ? null : preg_replace('/^\d{3}-\d{3}-/', '***-***-', $this->tin);
    }

    /**
     * @return HasMany<BusinessPermit, $this>
     */
    public function permits(): HasMany
    {
        return $this->hasMany(BusinessPermit::class);
    }

    /**
     * @return HasMany<BusinessDocument, $this>
     */
    public function documents(): HasMany
    {
        return $this->hasMany(BusinessDocument::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'year_established' => 'integer',
            'ownership_type' => OwnershipType::class,
            'size_category' => SizeCategory::class,
            'employee_count' => 'integer',
            'is_simulated' => 'boolean',
            'is_storefront' => 'boolean',
            'registration_body' => RegistrationBody::class,
            'registration_valid_until' => 'date',
            'record_status' => RecordStatus::class,
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'bir_verified_on' => 'date',
        ];
    }
}
