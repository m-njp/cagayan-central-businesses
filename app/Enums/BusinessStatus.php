<?php

namespace App\Enums;

enum BusinessStatus: string
{
    case New = 'new';
    case Renewed = 'renewed';
    case NotRenewed = 'not_renewed';
    case Closed = 'closed';

    /** Active in a year means new or renewed (database.md, business_years status rules). */
    public function isActive(): bool
    {
        return $this === self::New || $this === self::Renewed;
    }
}
