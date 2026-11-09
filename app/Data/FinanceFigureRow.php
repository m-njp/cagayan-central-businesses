<?php

namespace App\Data;

/** A ledger figure as the Capitol review screen shows it. The reviewer's display name is shown; the entrant's identity is not sent. */
final readonly class FinanceFigureRow
{
    public function __construct(
        public string $id,
        public string $line,
        public string $label,
        public int $fiscalYear,
        public string $basis,
        public string $value,
        public ?string $variant,
        public string $source,
        public ?string $location,
        public string $status,
        public bool $manual,
        public bool $enteredByYou,
        public ?string $reviewedOn,
        public ?string $reviewedBy,
        public ?string $note,
        public string $group,
        public string $groupLabel,
        public ?string $sourceUrl,
    ) {}
}
