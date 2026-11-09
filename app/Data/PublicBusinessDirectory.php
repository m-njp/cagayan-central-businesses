<?php

namespace App\Data;

/** The public Businesses filters. A town's businesses arrive separately, once per town (frontend.md, "Local Selection Contract"). */
final readonly class PublicBusinessDirectory
{
    /**
     * @param  list<SelectOption>  $towns  Towns with barangay boundaries
     * @param  list<array{code: string, name: string, color: int}>  $categories
     */
    public function __construct(
        public array $towns,
        public array $categories,
    ) {}
}
