<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', User::class);
        $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'role' => ['nullable', 'string', Rule::exists('roles', 'slug')],
            'account_status' => ['nullable', Rule::in(['active', 'inactive'])],
        ]);

        $search = $request->string('search')->trim()->toString();
        $users = User::query()
            ->with('roles:id,name,slug')
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('name', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%');
                });
            })
            ->when($request->filled('role'), fn (Builder $query) => $query->whereHas(
                'roles',
                fn (Builder $query) => $query->where('slug', $request->string('role')->toString()),
            ))
            ->when($request->string('account_status')->toString() === 'active', fn (Builder $query) => $query->where('is_active', true))
            ->when($request->string('account_status')->toString() === 'inactive', fn (Builder $query) => $query->where('is_active', false))
            ->orderBy('name')
            ->orderBy('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.users.index', [
            'users' => $users,
            'roleOptions' => Role::query()->orderBy('name')->pluck('name', 'slug'),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', User::class);

        return view('admin.users.create', [
            'user' => new User(['is_active' => true]),
            'roleOptions' => $this->assignableRoleOptions(),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($validated): void {
            $user = User::query()->create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => $validated['password'],
                'is_active' => $validated['is_active'],
            ]);

            $user->roles()->sync($this->roleIds($validated['roles']));
        });

        return redirect()->route('admin.users.index')->with('status', 'User account created.');
    }

    public function edit(User $user): View
    {
        Gate::authorize('update', $user);
        $user->load('roles:id,name,slug');

        return view('admin.users.edit', [
            'user' => $user,
            'roleOptions' => $this->assignableRoleOptions(),
            'selectedRoles' => $user->roles->pluck('slug')->all(),
            'isSuperAdminAccount' => $user->roles->contains('slug', Role::SUPER_ADMIN),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($user, $validated): void {
            $user->update([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'is_active' => $validated['is_active'],
            ]);

            if (! $user->hasRole(Role::SUPER_ADMIN)) {
                $user->roles()->sync($this->roleIds($validated['roles']));
            }
        });

        return redirect()->route('admin.users.index')->with('status', 'User account updated.');
    }

    /** @return array<string, string> */
    private function assignableRoleOptions(): array
    {
        return Role::query()
            ->whereIn('slug', Role::assignableSlugs())
            ->orderBy('name')
            ->pluck('name', 'slug')
            ->all();
    }

    /**
     * @param  array<int, string>  $roleSlugs
     * @return array<int, int>
     */
    private function roleIds(array $roleSlugs): array
    {
        return Role::query()
            ->whereIn('slug', $roleSlugs)
            ->pluck('id')
            ->all();
    }
}
