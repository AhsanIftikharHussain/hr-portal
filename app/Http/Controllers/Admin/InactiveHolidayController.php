<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Holiday;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class InactiveHolidayController extends Controller
{
    public function store(Holiday $holiday): RedirectResponse
    {
        Gate::authorize('update', $holiday);
        $holiday->update(['is_active' => false]);

        return back()->with('status', 'Holiday deactivated. Historical data was preserved.');
    }

    public function destroy(Holiday $holiday): RedirectResponse
    {
        Gate::authorize('update', $holiday);
        $holiday->update(['is_active' => true]);

        return back()->with('status', 'Holiday reactivated.');
    }
}
