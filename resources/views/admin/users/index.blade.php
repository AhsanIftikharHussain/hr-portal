<x-layouts.admin title="User Management">
    <div class="flex flex-col gap-6">
        <x-page-header title="User Management" description="Provision accounts, assign roles, and control access without deleting account history.">
            <x-slot:actions>
                <a href="{{ route('admin.users.create') }}" class="rounded-lg bg-portal-900 px-4 py-2.5 text-sm font-semibold text-white">Add User</a>
            </x-slot:actions>
        </x-page-header>

        <x-card>
            <form method="GET" action="{{ route('admin.users.index') }}" class="grid gap-4 md:grid-cols-3">
                <x-form-input label="Search" name="search" :value="request('search')" placeholder="Name or email" maxlength="100" />
                <x-form-select label="Role" name="role" :value="request('role')" :options="$roleOptions" placeholder="All roles" />
                <x-form-select label="Status" name="account_status" :value="request('account_status')" :options="['active' => 'Active', 'inactive' => 'Inactive']" placeholder="All statuses" />
                <div class="flex items-end gap-3 md:col-span-3">
                    <x-button>Filter</x-button>
                    <a href="{{ route('admin.users.index') }}" class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700">Clear</a>
                </div>
            </form>
        </x-card>

        <x-card class="overflow-hidden p-0">
            @if ($users->isEmpty())
                <x-empty-state title="No users found" description="Add a user or adjust the current filters." class="m-5" />
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                        <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-5 py-3">User</th>
                                <th class="px-5 py-3">Roles</th>
                                <th class="px-5 py-3">Status</th>
                                <th class="px-5 py-3">Created</th>
                                <th class="px-5 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($users as $user)
                                <tr>
                                    <td class="px-5 py-4">
                                        <span class="block font-semibold text-slate-950">{{ $user->name }}</span>
                                        <span class="block text-xs text-slate-500">{{ $user->email }}</span>
                                    </td>
                                    <td class="px-5 py-4">
                                        <div class="flex flex-wrap gap-1.5">
                                            @forelse ($user->roles as $role)
                                                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">{{ $role->name }}</span>
                                            @empty
                                                <span class="text-xs text-amber-700">No role assigned</span>
                                            @endforelse
                                        </div>
                                    </td>
                                    <td class="px-5 py-4">
                                        <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $user->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200 text-slate-700' }}">
                                            {{ $user->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-4 text-slate-600">{{ $user->created_at->format('d M Y') }}</td>
                                    <td class="px-5 py-4">
                                        <div class="flex justify-end gap-3 whitespace-nowrap">
                                            <a href="{{ route('admin.users.edit', $user) }}" class="font-semibold text-slate-700">Edit</a>
                                            <a href="{{ route('admin.users.password.edit', $user) }}" class="font-semibold text-slate-700">Reset Password</a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-slate-200 px-5 py-4">{{ $users->links() }}</div>
            @endif
        </x-card>
    </div>
</x-layouts.admin>
