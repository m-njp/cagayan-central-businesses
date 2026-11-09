<?php

namespace App\Enums;

enum RegistrationBody: string
{
    case Dti = 'dti';
    case Sec = 'sec';
    case Cda = 'cda';

    public function label(): string
    {
        return strtoupper($this->value);
    }
}
