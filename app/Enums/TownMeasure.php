<?php

namespace App\Enums;

enum TownMeasure: string
{
    case Population = 'population';
    case LandArea = 'land_area_km2';
    case BarangayCount = 'barangay_count';
    case IncomeClass = 'income_class';
    case PovertyIncidence = 'poverty_incidence';
}
