<?php

namespace App;

enum ContractStatus: string
{
    case Draft = 'draft';
    case Upcoming = 'upcoming';
    case Active = 'active';
    case Expired = 'expired';
    case Terminated = 'terminated';
    case Superseded = 'superseded';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Upcoming => 'Upcoming',
            self::Active => 'Active',
            self::Expired => 'Expired',
            self::Terminated => 'Terminated',
            self::Superseded => 'Superseded',
        };
    }

    /** @return array<int, self> */
    public static function expiringStatuses(): array
    {
        return [self::Active, self::Upcoming];
    }
}
