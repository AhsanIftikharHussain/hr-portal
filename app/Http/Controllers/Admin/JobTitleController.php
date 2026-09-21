<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreJobTitleRequest;
use App\Http\Requests\UpdateJobTitleRequest;
use App\Models\JobTitle;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class JobTitleController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', JobTitle::class);

        return view('admin.job-titles.index', [
            'jobTitles' => JobTitle::query()->withCount('employees')->orderBy('name')->paginate(15),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', JobTitle::class);

        return view('admin.job-titles.create');
    }

    public function store(StoreJobTitleRequest $request): RedirectResponse
    {
        JobTitle::query()->create(['name' => $request->string('name')->trim()->toString(), 'is_active' => true]);

        return redirect()->route('admin.job-titles.index')->with('status', 'Designation created.');
    }

    public function edit(JobTitle $jobTitle): View
    {
        Gate::authorize('update', $jobTitle);

        return view('admin.job-titles.edit', ['jobTitle' => $jobTitle]);
    }

    public function update(UpdateJobTitleRequest $request, JobTitle $jobTitle): RedirectResponse
    {
        $jobTitle->update(['name' => $request->string('name')->trim()->toString()]);

        return redirect()->route('admin.job-titles.index')->with('status', 'Designation updated.');
    }
}
