<?php

namespace App\Data;

/** Public-class fields plus the record's review state: no owner, contact, size, employees or sales. */
final readonly class BusinessDirectoryRow
{
    /**
     * @param  string  $category  Business Category code
     * @param  ?string  $status  Latest year status: new, renewed, not_renewed or closed
     * @param  string  $recordStatus  draft, submitted, returned or approved (D40)
     * @param  string  $permitStatus  M32: valid, expiring, expired or none
     */
    public function __construct(
        public string $publicId,
        public string $name,
        public string $type,
        public string $category,
        public string $barangay,
        public int $yearEstablished,
        public ?string $status,
        public string $recordStatus,
        public string $permitStatus,
    ) {}
}
