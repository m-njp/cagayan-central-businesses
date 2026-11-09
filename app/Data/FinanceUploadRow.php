<?php

namespace App\Data;

/** One report uploaded for this town and year, and how reading it went. */
final readonly class FinanceUploadRow
{
    public function __construct(
        public string $id,
        public string $name,
        public string $kind,
        public string $status,
        public string $statusLabel,
        public int $figures,
        public ?string $message,
        public string $uploadedAt,
    ) {}
}
