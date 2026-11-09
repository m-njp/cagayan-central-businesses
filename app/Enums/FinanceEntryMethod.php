<?php

namespace App\Enums;

enum FinanceEntryMethod: string
{
    case Parsed = 'parsed';
    case Manual = 'manual';
}
