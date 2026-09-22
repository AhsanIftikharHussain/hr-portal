<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PolicyCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class InactivePolicyCategoryController extends Controller
{
    public function store(PolicyCategory $policyCategory): RedirectResponse
    {
        Gate::authorize('update', $policyCategory);
        $policyCategory->update(['is_active' => false]);

        return back()->with('status', 'Policy category deactivated. Existing policies were preserved.');
    }

    public function destroy(PolicyCategory $policyCategory): RedirectResponse
    {
        Gate::authorize('update', $policyCategory);
        $policyCategory->update(['is_active' => true]);

        return back()->with('status', 'Policy category reactivated.');
    }
}
