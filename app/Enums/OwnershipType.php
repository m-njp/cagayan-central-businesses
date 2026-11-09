<?php

namespace App\Enums;

enum OwnershipType: string
{
    case SoleProprietorship = 'sole_proprietorship';
    case Partnership = 'partnership';
    case Corporation = 'corporation';
    case Cooperative = 'cooperative';
}
