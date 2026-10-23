<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Holiday;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class HolidayController extends Controller
{
    public function index(Request $request): View
    {
        $request->validate(['year' => ['nullable', 'integer', 'between:2000,2100']]);
        $year = $request->integer('year') ?: CarbonImmutable::now(config('app.business_timezone'))->year;

        return view('employee.holidays.index', [
            'year' => $year,
            'holidaysByMonth' => Holiday::query()
                ->where('is_active', true)
                ->whereYear('holiday_date', $year)
                ->orderBy('holiday_date')
                ->orderBy('id')
                ->get()
                ->groupBy(fn (Holiday $holiday): string => $holiday->holiday_date->format('F')),
        ]);
    }
}
