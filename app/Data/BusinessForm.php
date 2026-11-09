<?php

namespace App\Data;

/** Options and limits for the registry form, plus the current values when editing (D40). */
final readonly class BusinessForm
{
    /**
     * @param  array<string, mixed>|null  $values  Null for a new record
     * @param  list<array{code: string, name: string, types: list<SelectOption>}>  $categories
     * @param  list<SelectOption>  $towns  The staff member's assigned towns with barangay boundaries
     * @param  list<array{value: string, label: string, town: string}>  $barangays
     * @param  array<string, list<SelectOption>>  $choices  Ownership, size, registration body and document kinds
     * @param  list<array{label: string, name: string}>  $documents  Already uploaded
     * @param  array<string, int>  $limits
     */
    public function __construct(
        public ?string $publicId,
        public ?array $values,
        public ?string $reviewNote,
        public array $categories,
        public array $towns,
        public array $barangays,
        public array $choices,
        public array $documents,
        public array $limits,
    ) {}
}
