<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLeaveTypeRequest;
use App\Http\Requests\UpdateLeaveTypeRequest;
use App\Models\LeaveType;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class LeaveTypeController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', LeaveType::class);

        return view('admin.leave-types.index', [
            'leaveTypes' => LeaveType::query()->withCount('leaveRequests')->orderBy('name')->paginate(15),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', LeaveType::class);

        return view('admin.leave-types.create');
    }

    public function store(StoreLeaveTypeRequest $request): RedirectResponse
    {
        LeaveType::query()->create([
            'name' => $request->string('name')->trim()->toString(),
            'code' => $request->string('code')->trim()->lower()->toString(),
            'is_active' => true,
            'requires_attachment' => $request->boolean('requires_attachment'),
            'is_paid' => $request->boolean('is_paid'),
        ]);

        return redirect()->route('admin.leave-types.index')->with('status', 'Leave type created.');
    }

    public function edit(LeaveType $leaveType): View
    {
        Gate::authorize('update', $leaveType);

        return view('admin.leave-types.edit', ['leaveType' => $leaveType]);
    }

    public function update(UpdateLeaveTypeRequest $request, LeaveType $leaveType): RedirectResponse
    {
        $leaveType->update([
            'name' => $request->string('name')->trim()->toString(),
            'code' => $request->string('code')->trim()->lower()->toString(),
            'requires_attachment' => $request->boolean('requires_attachment'),
            'is_paid' => $request->boolean('is_paid'),
        ]);

        return redirect()->route('admin.leave-types.index')->with('status', 'Leave type updated.');
    }
}
