<?php

namespace App\Data;

/** The full Capitol record (C13). Admin fields and documents are null and empty for Staff of another town. Opening it writes a record view log first (P9). No sales of any kind (P1). */
final readonly class BusinessProfile
{
    /**
     * @param  array{code: string, name: string, color: int}  $category
     * @param  int  $streak  M33 Renewal Streak in years
     * @param  int|null  $milestone  M34 Operating Milestone in years
     * @param  array{lng: float, lat: float}|null  $location
     * @param  list<array{year: int, number: string|null, validFrom: string, validUntil: string}>  $permits
     * @param  list<array{publicId: string, kind: string, label: string, name: string, issuedOn: ?string, expiresOn: ?string}>  $documents
     * @param  list<array{year: int, status: string}>  $history
     * @param  array{edit: bool, review: bool}  $can
     */
    public function __construct(
        public string $publicId,
        public string $name,
        public string $type,
        public string $typeCode,
        public array $category,
        public string $barangay,
        public string $barangayCode,
        public string $town,
        public string $townCode,
        public string $description,
        public int $yearEstablished,
        public bool $isStorefront,
        public ?string $businessPhone,
        public ?string $businessEmail,
        public ?string $birVerifiedOn,
        public string $permitStatus,
        public ?string $permitValidUntil,
        public int $streak,
        public ?int $milestone,
        public ?array $location,
        public string $recordStatus,
        public ?string $submittedBy,
        public ?string $submittedAt,
        public ?string $reviewedBy,
        public ?string $reviewedAt,
        public ?BusinessAdminFields $admin,
        public array $permits,
        public array $documents,
        public array $history,
        public array $can,
        public int $reviewNoteMax,
    ) {}
}
