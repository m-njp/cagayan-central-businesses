<?php

namespace App\Data;

/**
 * A sales figure after the privacy guard (privacy-rules.md, "The guard"). Public figures carry a band, never a value (P7).
 */
final readonly class GuardedFigure
{
    public function __construct(
        public string $state,
        public ?string $value = null,
        public ?string $band = null,
        public ?string $reason = null,
    ) {}

    public static function hidden(string $reason): self
    {
        return new self('hidden', reason: $reason);
    }

    public function isHidden(): bool
    {
        return $this->state === 'hidden';
    }
}
