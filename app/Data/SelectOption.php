<?php

namespace App\Data;

final readonly class SelectOption
{
    public function __construct(
        public string $value,
        public string $label,
    ) {}
}
