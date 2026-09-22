<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Holiday;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class HolidayCalendarController extends Controller
{
    public function __invoke(Request $request): View
    {
        Gate::authorize('viewAny', Holiday::class);
        $request->validate(['year' => ['nullable', 'integer', 'between:2000,2100']]);
        $year = $request->integer('year') ?: now()->year;

        $holidaysByMonth = Holiday::query()
            ->where('is_active', true)
            ->whereYear('holiday_date', $year)
            ->orderBy('holiday_date')
            ->orderBy('name')
            ->get()
            ->groupBy(fn (Holiday $holiday): int => $holiday->holiday_date->month);

        return view('admin.holidays.calendar', [
            'year' => $year,
            'holidaysByMonth' => $holidaysByMonth,
        ]);
    }
}
