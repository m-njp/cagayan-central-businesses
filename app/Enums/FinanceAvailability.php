<?php

namespace App\Enums;

enum FinanceAvailability: string
{
    case Available = 'available';
    case NotPublished = 'not_published';
}
