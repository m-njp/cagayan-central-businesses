<?php

namespace App\Data;

final readonly class FinanceUploadTask
{
    /** @param array<string, mixed> $progress */
    public function __construct(
        public string $id,
        public string $name,
        public string $status,
        public int $saved,
        public ?string $message,
        public array $progress,
        public int $pollMs,
    ) {}
}
