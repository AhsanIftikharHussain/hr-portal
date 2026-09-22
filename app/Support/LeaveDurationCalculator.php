<?php

namespace App\Support;

use Carbon\CarbonInterface;

class LeaveDurationCalculator
{
    public function calendarDays(CarbonInterface $startDate, CarbonInterface $endDate): int
    {
        return $startDate->copy()->startOfDay()->diffInDays($endDate->copy()->startOfDay()) + 1;
    }
}
