<?php

namespace App\Enums;

enum FinanceFigureStatus: string
{
    case Pending = 'pending';
    case Verified = 'verified';
    case Rejected = 'rejected';
    case Superseded = 'superseded';
}
