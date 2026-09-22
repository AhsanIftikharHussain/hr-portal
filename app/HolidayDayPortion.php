<?php

namespace App;

enum HolidayDayPortion: string
{
    case FullDay = 'full-day';
    case FirstHalf = 'first-half';
    case SecondHalf = 'second-half';

    public function label(): string
    {
        return match ($this) {
            self::FullDay => 'Full Day',
            self::FirstHalf => 'First Half',
            self::SecondHalf => 'Second Half',
        };
    }
}
