<?php

namespace App\Data;

/** One page of registered business names with the options their filters need (D39, D40). */
final readonly class BusinessDirectory
{
    /**
     * @param  list<BusinessDirectoryRow>  $rows
     * @param  list<array{code: string, name: string, color: int}>  $categories
     * @param  list<SelectOption>  $types
     * @param  list<SelectOption>  $industries
     * @param  list<SelectOption>  $statuses
     * @param  list<SelectOption>  $barangays
     * @param  array<string, int>  $recordCounts  Records per review state, for the tabs
     * @param  array<string, string|null>  $filters
     */
    public function __construct(
        public array $rows,
        public Pagination $pagination,
        public array $categories,
        public array $types,
        public array $industries,
        public array $statuses,
        public array $barangays,
        public array $recordCounts,
        public array $filters,
        public bool $canAdd,
    ) {}
}
