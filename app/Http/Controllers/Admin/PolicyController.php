<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePolicyRequest;
use App\Http\Requests\UpdatePolicyRequest;
use App\Models\Policy;
use App\Models\PolicyCategory;
use App\PolicyStatus;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class PolicyController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Policy::class);
        $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'policy_category_id' => ['nullable', 'integer', Rule::exists('policy_categories', 'id')],
            'status' => ['nullable', Rule::enum(PolicyStatus::class)],
        ]);

        $policies = Policy::query()
            ->with(['category:id,name', 'updater:id,name'])
            ->when($request->filled('search'), function (Builder $query) use ($request): void {
                $search = '%'.$request->string('search')->trim()->toString().'%';
                $query->where(function (Builder $searchQuery) use ($search): void {
                    $searchQuery->where('title', 'like', $search)->orWhere('summary', 'like', $search);
                });
            })
            ->when($request->integer('policy_category_id'), fn (Builder $query, int $categoryId) => $query->where('policy_category_id', $categoryId))
            ->when($request->filled('status'), fn (Builder $query) => $query->where('status', $request->string('status')))
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.policies.index', [
            'policies' => $policies,
            'categories' => PolicyCategory::query()->orderBy('name')->get(['id', 'name']),
            'statuses' => PolicyStatus::cases(),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', Policy::class);

        return view('admin.policies.create', $this->formData() + ['policy' => new Policy]);
    }

    public function store(StorePolicyRequest $request): RedirectResponse
    {
        $policy = Policy::query()->create([
            ...$request->safe()->only(['policy_category_id', 'title', 'summary', 'content', 'effective_date']),
            'status' => PolicyStatus::Draft,
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]);

        return redirect()->route('admin.policies.show', $policy)->with('status', 'Draft policy created.');
    }

    public function show(Policy $policy): View
    {
        Gate::authorize('view', $policy);
        $policy->load(['category', 'creator:id,name', 'updater:id,name']);

        return view('admin.policies.show', ['policy' => $policy]);
    }

    public function edit(Policy $policy): View
    {
        Gate::authorize('update', $policy);
        $policy->load('category');

        return view('admin.policies.edit', $this->formData($policy) + ['policy' => $policy]);
    }

    public function update(UpdatePolicyRequest $request, Policy $policy): RedirectResponse
    {
        $requestedStatus = PolicyStatus::from($request->string('status')->toString());
        $publishedAt = $policy->published_at;

        if ($policy->status === PolicyStatus::Draft && $requestedStatus === PolicyStatus::Published) {
            $publishedAt = now();
        }

        $policy->update([
            ...$request->safe()->only(['policy_category_id', 'title', 'summary', 'content', 'status', 'effective_date']),
            'published_at' => $publishedAt,
            'updated_by' => $request->user()->id,
        ]);

        return redirect()->route('admin.policies.show', $policy)->with('status', 'Policy updated.');
    }

    /** @return array<string, mixed> */
    private function formData(?Policy $policy = null): array
    {
        return [
            'categories' => PolicyCategory::query()
                ->where('is_active', true)
                ->when($policy, fn (Builder $query) => $query->orWhereKey($policy->policy_category_id))
                ->orderBy('name')
                ->get(['id', 'name', 'is_active']),
            'statuses' => PolicyStatus::cases(),
        ];
    }
}
