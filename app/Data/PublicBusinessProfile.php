<?php

namespace App\Data;

/** A public business profile: no owner, address, registration numbers, documents or sales (P8). */
final readonly class PublicBusinessProfile
{
    /**
     * @param  array{code: string, name: string}  $category
     * @param  int  $streak  M33 Renewal Streak in years
     * @param  int|null  $milestone  M34 Operating Milestone in years
     * @param  array{lng: float, lat: float}|null  $location  Storefronts only
     * @param  list<array{id: string, name: string, category: array{code: string, name: string}}>  $nearby
     * @param  list<array{id: string, point: array{0: float, 1: float}}>  $neighbors  Storefronts in the same barangay, for the map
     */
    public function __construct(
        public string $publicId,
        public string $name,
        public string $type,
        public array $category,
        public string $barangay,
        public string $barangayCode,
        public string $town,
        public string $townCode,
        public string $description,
        public int $yearEstablished,
        public ?string $businessPhone,
        public ?string $businessEmail,
        public ?string $birVerifiedOn,
        public string $permitStatus,
        public ?string $permitValidUntil,
        public int $streak,
        public ?int $milestone,
        public ?array $location,
        public array $nearby,
        public array $neighbors,
    ) {}
}
