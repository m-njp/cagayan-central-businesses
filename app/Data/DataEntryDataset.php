<?php

namespace App\Data;

/** One dataset on the Data Entry hub: where it lives, what waits for review, and how to add to it. */
final readonly class DataEntryDataset
{
    /** @param list<string> $shownOn */
    public function __construct(
        public string $id,
        public string $label,
        public array $shownOn,
        public string $href,
        public ?int $pending,
        public ?string $lastChange,
        public ?string $action,
        public ?string $actionHref,
    ) {}
}
