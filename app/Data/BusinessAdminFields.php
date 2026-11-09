<?php

namespace App\Data;

/** Admin-class fields of one business (P8). Only the Capitol profile carries this object; the TIN arrives masked. */
final readonly class BusinessAdminFields
{
    public function __construct(
        public string $ownerName,
        public string $ownerContact,
        public ?string $streetAddress,
        public string $ownership,
        public string $size,
        public int $employees,
        public ?string $registrationBody,
        public ?string $registrationNumber,
        public ?string $registrationValidUntil,
        public ?string $tin,
        public ?string $rdoCode,
        public ?string $reviewNote,
    ) {}
}
