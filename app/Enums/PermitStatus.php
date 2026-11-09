<?php

namespace App\Enums;

/** M32 Permit Status, computed in SQL from the latest permit's valid_until. */
enum PermitStatus: string
{
    case Valid = 'valid';
    case Expiring = 'expiring';
    case Expired = 'expired';
    case None = 'none';

    public function label(): string
    {
        return match ($this) {
            self::Valid => 'Permit Valid',
            self::Expiring => 'Permit Expiring',
            self::Expired => 'Permit Expired',
            self::None => 'No Permit on File',
        };
    }

    /** A SQL case expression over the latest permit, joined as `permit`; bind self::bindings(). */
    public const string SQL = "case when permit.valid_until is null then 'none' when permit.valid_until < current_date then 'expired'
        when permit.valid_until <= current_date + ?::int then 'expiring' else 'valid' end";

    /** @return list<int> */
    public static function bindings(): array
    {
        return [(int) config('cagayan.registry.permit_expiring_days')];
    }
}
