<?php

namespace App;

enum AttendanceStatus: string
{
    case Present = 'present';
    case Absent = 'absent';
    case Leave = 'leave';
    case HalfDay = 'half-day';
    case Late = 'late';

    public function label(): string
    {
        return match ($this) {
            self::Present => 'Present',
            self::Absent => 'Absent',
            self::Leave => 'Leave',
            self::HalfDay => 'Half Day',
            self::Late => 'Late',
        };
    }
}
