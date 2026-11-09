<?php

namespace App\Enums;

/** Documents follow the Tuguegarao new permit list (mvp-scope.md, section 4) plus the BIR COR and Seal Badge. */
enum DocumentKind: string
{
    case BusinessPermit = 'business_permit';
    case BirCor = 'bir_cor';
    case BirSealBadge = 'bir_seal_badge';
    case Registration = 'registration';
    case BarangayClearance = 'barangay_clearance';
    case ZoningClearance = 'zoning_clearance';
    case SanitaryPermit = 'sanitary_permit';
    case FireSafety = 'fire_safety';
    case OccupancyPermit = 'occupancy_permit';
    case CenroCertificate = 'cenro_certificate';
    case Photo = 'photo';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::BusinessPermit => 'Business Permit',
            self::BirCor => 'BIR Certificate of Registration',
            self::BirSealBadge => 'BIR Registration Seal Badge',
            self::Registration => 'DTI SEC or CDA Registration',
            self::BarangayClearance => 'Barangay Clearance',
            self::ZoningClearance => 'Zoning Clearance',
            self::SanitaryPermit => 'Sanitary Permit',
            self::FireSafety => 'Fire Safety Inspection Certificate',
            self::OccupancyPermit => 'Occupancy Permit',
            self::CenroCertificate => 'CENRO Certificate',
            self::Photo => 'Establishment Photo',
            self::Other => 'Other Document',
        };
    }
}
