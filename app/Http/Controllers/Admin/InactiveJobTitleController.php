<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\JobTitle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class InactiveJobTitleController extends Controller
{
    public function store(JobTitle $jobTitle): RedirectResponse
    {
        Gate::authorize('update', $jobTitle);
        $jobTitle->update(['is_active' => false]);

        return back()->with('status', 'Designation deactivated. Existing employee assignments were preserved.');
    }

    public function destroy(JobTitle $jobTitle): RedirectResponse
    {
        Gate::authorize('update', $jobTitle);
        $jobTitle->update(['is_active' => true]);

        return back()->with('status', 'Designation reactivated.');
    }
}
