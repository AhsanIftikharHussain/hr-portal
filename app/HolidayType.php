<?php

namespace App;

enum HolidayType: string
{
    case PublicHoliday = 'public-holiday';
    case CompanyHoliday = 'company-holiday';
    case OptionalHoliday = 'optional-holiday';
    case SpecialClosure = 'special-closure';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::PublicHoliday => 'Public Holiday',
            self::CompanyHoliday => 'Company Holiday',
            self::OptionalHoliday => 'Optional Holiday',
            self::SpecialClosure => 'Special Closure',
            self::Other => 'Other',
        };
    }
}
