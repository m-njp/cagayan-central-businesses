<?php

namespace App\Enums;

/** Registry review state (D40): Staff prepare and submit, Admin approves or returns. Only approved records are public (P15). */
enum RecordStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case Returned = 'returned';
    case Approved = 'approved';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Submitted => 'Pending',
            self::Returned => 'Returned',
            self::Approved => 'Verified',
        };
    }
}
