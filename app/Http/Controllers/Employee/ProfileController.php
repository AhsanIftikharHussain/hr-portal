<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function show(Request $request): View
    {
        return view('employee.profile.show', [
            'employee' => $request->user()->employee()
                ->with(['department:id,name', 'jobTitle:id,name', 'reportingManager:id,full_name,deleted_at'])
                ->first(),
        ]);
    }
}
