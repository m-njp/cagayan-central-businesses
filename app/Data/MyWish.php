<?php

namespace App\Data;

/**
 * The latest wish of the browser that sent the request. Its reason goes back
 * only to that browser so it can be edited. Other reasons stay admin only (P12).
 */
final readonly class MyWish
{
    public function __construct(
        public int $id,
        public string $townCode,
        public string $townName,
        public ?string $typeCode,
        public ?string $typeName,
        public ?string $specificBusiness,
        public ?string $reason,
        public int $editsLeft,
        public ?string $nextEditAt,
    ) {}
}
