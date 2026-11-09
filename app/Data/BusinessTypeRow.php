<?php

namespace App\Data;

final readonly class BusinessTypeRow
{
    /**
     * @param  string  $category  Business Category code
     * @param  ?int  $registered  Active businesses in the latest data year, null when no business data is shown
     */
    public function __construct(
        public string $code,
        public string $name,
        public string $category,
        public string $industry,
        public string $industryCode,
        public ?int $registered,
    ) {}
}
