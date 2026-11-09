<?php

namespace App\Data;

/** Every Business Category and Business Type with registered counts (D39). Capped, so sorting, filtering and paging are local. */
final readonly class BusinessCategories
{
    /**
     * @param  list<BusinessTypeRow>  $types
     * @param  list<BusinessCategoryRow>  $categories
     * @param  list<SelectOption>  $industries
     */
    public function __construct(
        public array $types,
        public array $categories,
        public array $industries,
        public ?int $year,
        public int $typeNameMax,
        public int $categoryNameMax,
        public int $colors,
        public int $pageSize,
    ) {}
}
