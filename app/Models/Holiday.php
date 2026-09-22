<?php

namespace App\Models;

use App\HolidayDayPortion;
use App\HolidayType;
use Database\Factories\HolidayFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'holiday_date', 'type', 'day_portion', 'description', 'is_active'])]
class Holiday extends Model
{
    /** @use HasFactory<HolidayFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'holiday_date' => 'date',
            'type' => HolidayType::class,
            'day_portion' => HolidayDayPortion::class,
            'is_active' => 'boolean',
        ];
    }
}
