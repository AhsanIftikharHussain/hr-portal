<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Policy;
use App\Models\PolicyCategory;
use App\PolicyStatus;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PolicyController extends Controller
{
    public function index(Request $request): View
    {
        $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'policy_category_id' => ['nullable', 'integer', Rule::exists('policy_categories', 'id')],
        ]);

        $policies = Policy::query()
            ->with('category:id,name')
            ->where('status', PolicyStatus::Published)
            ->when($request->filled('search'), function (Builder $query) use ($request): void {
                $search = '%'.$request->string('search')->trim()->toString().'%';
                $query->where(fn (Builder $searchQuery) => $searchQuery
                    ->where('title', 'like', $search)
                    ->orWhere('summary', 'like', $search));
            })
            ->when($request->integer('policy_category_id'), fn (Builder $query, int $categoryId) => $query->where('policy_category_id', $categoryId))
            ->orderByDesc('effective_date')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('employee.policies.index', [
            'policies' => $policies,
            'categories' => PolicyCategory::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function show(Policy $policy): View
    {
        $publishedPolicy = Policy::query()
            ->with('category:id,name')
            ->where('status', PolicyStatus::Published)
            ->whereKey($policy->id)
            ->firstOrFail();

        return view('employee.policies.show', ['policy' => $publishedPolicy]);
    }
}
