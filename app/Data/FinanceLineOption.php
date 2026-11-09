<?php

namespace App\Data;

final readonly class FinanceLineOption
{
    /** @param list<string> $bases */
    public function __construct(public string $value, public string $label, public array $bases, public string $group, public string $groupLabel) {}
}
