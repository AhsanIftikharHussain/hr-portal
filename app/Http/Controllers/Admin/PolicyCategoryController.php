<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePolicyCategoryRequest;
use App\Http\Requests\UpdatePolicyCategoryRequest;
use App\Models\PolicyCategory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class PolicyCategoryController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', PolicyCategory::class);

        return view('admin.policy-categories.index', [
            'categories' => PolicyCategory::query()->withCount('policies')->orderBy('name')->paginate(15),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', PolicyCategory::class);

        return view('admin.policy-categories.create', ['category' => new PolicyCategory]);
    }

    public function store(StorePolicyCategoryRequest $request): RedirectResponse
    {
        PolicyCategory::query()->create([
            'name' => $request->string('name')->trim()->toString(),
            'description' => $request->string('description')->trim()->toString() ?: null,
            'is_active' => true,
        ]);

        return redirect()->route('admin.policy-categories.index')->with('status', 'Policy category created.');
    }

    public function edit(PolicyCategory $policyCategory): View
    {
        Gate::authorize('update', $policyCategory);

        return view('admin.policy-categories.edit', ['category' => $policyCategory]);
    }

    public function update(UpdatePolicyCategoryRequest $request, PolicyCategory $policyCategory): RedirectResponse
    {
        $policyCategory->update([
            'name' => $request->string('name')->trim()->toString(),
            'description' => $request->string('description')->trim()->toString() ?: null,
        ]);

        return redirect()->route('admin.policy-categories.index')->with('status', 'Policy category updated.');
    }
}
